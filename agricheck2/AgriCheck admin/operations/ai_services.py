import base64
import json
import mimetypes
import os
import ssl
import urllib.error
import urllib.parse
import urllib.request
import uuid
from decimal import Decimal

from django.conf import settings
from django.utils import timezone

from .models import AiAnalysis, Disease, Treatment

try:
    import certifi
except ImportError:  # pragma: no cover
    certifi = None


class DroneAiError(Exception):
    pass


class DroneAiConfigurationError(DroneAiError):
    pass


class DroneAiRemoteError(DroneAiError):
    pass


AgricheckAiError = DroneAiError
AgricheckAiConfigurationError = DroneAiConfigurationError
AgricheckAiRemoteError = DroneAiRemoteError

DEFAULT_LATITUDE = 12.6392
DEFAULT_LONGITUDE = -8.0029


def run_drone_analysis(analysis):
    if analysis.source != AiAnalysis.Source.DRONE:
        raise DroneAiConfigurationError("Cette action est reservee aux captures drone.")

    filename, image_bytes = _read_analysis_image(analysis)
    payload = diagnose_image(filename, image_bytes)
    apply_diagnosis_to_analysis(analysis, payload)
    return analysis


def apply_diagnosis_to_analysis(analysis, payload):
    health = payload["health"]
    plant = payload["plant"]
    disease = _sync_disease(health, plant, payload["riskLevel"])
    _sync_treatment(disease, health)

    analysis.detected_plant = payload["plantName"]
    analysis.disease = disease
    analysis.detected_disease = payload["diseaseName"]
    analysis.confidence = _percent_decimal(payload["diseaseConfidence"])
    analysis.risk_level = _risk_value(payload["riskLevel"])
    analysis.provider = payload["provider"]
    analysis.raw_ai_response = payload
    analysis.analyzed_at = timezone.now()
    analysis.save(
        update_fields=[
            "detected_plant",
            "disease",
            "detected_disease",
            "confidence",
            "risk_level",
            "provider",
            "raw_ai_response",
            "analyzed_at",
        ]
    )
    return analysis


def diagnose_uploaded_file(uploaded_file, latitude=DEFAULT_LATITUDE, longitude=DEFAULT_LONGITUDE):
    if uploaded_file is None:
        raise DroneAiConfigurationError("Aucune image recue pour le diagnostic IA.")
    chunks = uploaded_file.chunks() if hasattr(uploaded_file, "chunks") else [uploaded_file.read()]
    image_bytes = b"".join(chunks)
    filename = getattr(uploaded_file, "name", "") or "agricheck-drone.jpg"
    return diagnose_image(filename, image_bytes, latitude=latitude, longitude=longitude)


def diagnose_image(filename, image_bytes, latitude=DEFAULT_LATITUDE, longitude=DEFAULT_LONGITUDE):
    if not image_bytes:
        raise DroneAiConfigurationError("Ajoutez une image drone avant de lancer l'analyse IA.")

    plantnet_key = _real_secret(settings.PLANTNET_API_KEY)
    plant_id_key = _real_secret(getattr(settings, "PLANT_ID_API_KEY", "") or getattr(settings, "KINDWISE_API_KEY", ""))
    crop_health_key = _real_secret(getattr(settings, "CROP_HEALTH_API_KEY", ""))
    if not plant_id_key:
        raise DroneAiConfigurationError(
            "Diagnostic IA reel non configure. Ajoutez PLANT_ID_API_KEY dans le fichier .env, "
            "puis redemarrez Agricheck Admin. PLANTNET_API_KEY et CROP_HEALTH_API_KEY sont optionnelles."
        )

    diagnosis = _assess_with_plant_id(filename, image_bytes, plant_id_key, latitude, longitude)
    plant = diagnosis["plant"]
    health = diagnosis["health"]
    source_parts = ["Plant.id"]
    source_payload = {"plant_id": diagnosis.get("raw")}

    if _needs_plantnet_fallback(plant, health) and plantnet_key:
        try:
            fallback_plant = _identify_with_plantnet(filename, image_bytes, plantnet_key)
        except DroneAiRemoteError as exc:
            source_payload["plantnet_error"] = str(exc)
        else:
            if _to_float(fallback_plant.get("confidence")) >= _to_float(plant.get("confidence")):
                plant = fallback_plant
            source_parts.append("Pl@ntNet secours")

    if crop_health_key:
        try:
            crop_health = _assess_with_crop_health(filename, image_bytes, crop_health_key, latitude, longitude)
        except DroneAiRemoteError as exc:
            source_payload["crop_health_error"] = str(exc)
        else:
            health = _merge_health(health, crop_health)
            source_payload["crop_health"] = crop_health
            source_parts.append("Crop.Health")

    if not _has_relevant_result(plant, health):
        raise DroneAiRemoteError(
            "Plant.id n'a pas retourne de diagnostic pertinent pour cette image. "
            "Reprenez une photo plus nette de la feuille ou de la partie malade."
        )

    risk_label = _risk_label(health.get("isHealthy"), health.get("confidence"))
    now = timezone.now()
    return {
        "id": str(now.timestamp()).replace(".", ""),
        "createdAt": now.isoformat(),
        "imageName": filename,
        "plantName": plant.get("name") or "Plante identifiee",
        "scientificName": plant.get("scientificName") or "",
        "plantConfidence": _to_float(plant.get("confidence")),
        "family": plant.get("family") or "",
        "diseaseName": health.get("name") or "Diagnostic non disponible",
        "diseaseConfidence": _to_float(health.get("confidence")),
        "riskLevel": risk_label,
        "isHealthy": bool(health.get("isHealthy")),
        "plantDescription": "",
        "symptoms": health.get("symptoms") or [],
        "causes": health.get("causes") or [],
        "biologicalTreatments": health.get("biologicalTreatments") or [],
        "chemicalTreatments": health.get("chemicalTreatments") or [],
        "prevention": health.get("prevention") or [],
        "dosage": health.get("dosage") or "",
        "frequency": _frequency_for(bool(health.get("isHealthy")), _to_float(health.get("confidence"))),
        "urgencyLevel": _urgency_for(bool(health.get("isHealthy")), _to_float(health.get("confidence"))),
        "latitude": latitude,
        "longitude": longitude,
        "locationLabel": f"GPS {latitude:.4f}, {longitude:.4f}",
        "source": " + ".join(source_parts),
        "provider": AiAnalysis.Provider.PLANT_ID,
        "plant": plant,
        "health": health,
        "sources": source_payload,
    }


def _read_analysis_image(analysis):
    if analysis.image:
        try:
            analysis.image.open("rb")
            try:
                data = analysis.image.read()
            finally:
                analysis.image.close()
        except FileNotFoundError as exc:
            raise DroneAiConfigurationError("Le fichier image drone est introuvable sur le disque.") from exc
        if data:
            return os.path.basename(analysis.image.name), data

    if analysis.image_url:
        try:
            with urllib.request.urlopen(analysis.image_url, timeout=45) as response:
                data = response.read(15 * 1024 * 1024 + 1)
        except (urllib.error.URLError, TimeoutError) as exc:
            raise DroneAiRemoteError("Impossible de telecharger l'image externe envoyee par le drone.") from exc
        if len(data) > 15 * 1024 * 1024:
            raise DroneAiConfigurationError("L'image drone depasse 15 Mo.")
        filename = os.path.basename(urllib.parse.urlparse(analysis.image_url).path) or "agricheck-drone.jpg"
        return filename, data

    raise DroneAiConfigurationError("Ajoutez une image drone avant de lancer l'analyse IA.")


def _identify_with_plantnet(filename, image_bytes, api_key):
    project = getattr(settings, "PLANTNET_PROJECT", "all") or "all"
    query = urllib.parse.urlencode(
        {
            "api-key": api_key,
            "include-related-images": "false",
            "no-reject": "false",
            "lang": "fr",
        }
    )
    url = f"https://my-api.plantnet.org/v2/identify/{project}?{query}"
    body, content_type = _multipart_body(
        fields={"organs": "leaf"},
        files={"images": (filename, _media_type(filename), image_bytes)},
    )
    payload = _post(url, body, {"Content-Type": content_type}, "PlantNet")
    results = payload.get("results")
    if not isinstance(results, list) or not results:
        raise DroneAiRemoteError("PlantNet n'a pas reconnu de plante exploitable sur cette image.")

    best = results[0] if isinstance(results[0], dict) else {}
    species = best.get("species") if isinstance(best.get("species"), dict) else {}
    common_names = species.get("commonNames")
    common_name = common_names[0] if isinstance(common_names, list) and common_names else payload.get("bestMatch")
    family = species.get("family") if isinstance(species.get("family"), dict) else {}
    return {
        "name": common_name or "Plante identifiee",
        "scientificName": (
            species.get("scientificNameWithoutAuthor")
            or species.get("scientificName")
            or payload.get("bestMatch")
            or ""
        ),
        "family": family.get("scientificNameWithoutAuthor") or family.get("scientificName") or "",
        "confidence": _to_float(best.get("score")),
    }


def _assess_with_plant_id(filename, image_bytes, api_key, latitude, longitude):
    payload = {
        "images": [base64.b64encode(image_bytes).decode("ascii")],
        "latitude": latitude,
        "longitude": longitude,
        "similar_images": False,
        "health": "all",
        "classification_level": "species",
        "language": "fr",
        "details": ["description", "common_names", "taxonomy", "treatment", "cause", "url"],
    }
    last_error = None
    for url in ("https://plant.id/api/v3/identification", "https://api.plant.id/v3/identification"):
        try:
            body = _post_json(url, payload, {"Api-Key": api_key}, "Plant.id")
            return _parse_plant_id_diagnosis(body)
        except DroneAiRemoteError as exc:
            last_error = exc
    raise last_error or DroneAiRemoteError("Plant.id a refuse le diagnostic maladie.")


def _assess_with_crop_health(filename, image_bytes, api_key, latitude, longitude):
    payload = {
        "images": [base64.b64encode(image_bytes).decode("ascii")],
        "latitude": latitude,
        "longitude": longitude,
        "similar_images": False,
        "language": "fr",
        "details": ["description", "common_names", "taxonomy", "treatment", "cause", "url"],
    }
    last_error = None
    for url in ("https://crop.kindwise.com/api/v1/identification", "https://api.crop.kindwise.com/v1/identification"):
        try:
            return _parse_health(
                _post_json(url, payload, {"Api-Key": api_key}, "Crop.Health"),
                "Crop.Health",
            )
        except DroneAiRemoteError as exc:
            last_error = exc
    raise last_error or DroneAiRemoteError("Crop.Health a refuse le diagnostic maladie.")


def _parse_health(body, provider):
    result = body.get("result") if isinstance(body.get("result"), dict) else body
    healthy_node = result.get("is_healthy") or result.get("isHealthy") or {}
    healthy_probability = _to_float(healthy_node.get("probability") if isinstance(healthy_node, dict) else None)
    binary = healthy_node.get("binary") if isinstance(healthy_node, dict) else None
    is_healthy = binary if isinstance(binary, bool) else healthy_probability >= 0.5
    disease_node = result.get("disease") or result.get("health") or result.get("crop_health") or {}
    suggestions = disease_node.get("suggestions") or disease_node.get("classes") or []

    if not suggestions:
        return {
            "name": "Plante probablement saine" if is_healthy else "Maladie non determinee",
            "confidence": healthy_probability if is_healthy else 0,
            "isHealthy": is_healthy,
            "provider": provider,
            "symptoms": [],
            "causes": [],
            "biologicalTreatments": [],
            "chemicalTreatments": [],
            "prevention": [],
            "dosage": "",
            "description": "",
        }

    best = suggestions[0] if isinstance(suggestions[0], dict) else {}
    details = best.get("details") if isinstance(best.get("details"), dict) else {}
    treatment = details.get("treatment") if isinstance(details.get("treatment"), dict) else {}
    probability = _to_float(best.get("probability") or best.get("confidence") or best.get("score"))
    healthy_result = bool(is_healthy and probability < 0.2)
    return {
        "name": (
            "Plante probablement saine"
            if healthy_result
            else best.get("name") or best.get("common_name") or best.get("scientific_name") or "Maladie detectee"
        ),
        "confidence": probability,
        "isHealthy": healthy_result,
        "provider": provider,
        "symptoms": _string_list(details.get("symptoms")),
        "causes": _string_list(details.get("cause")) or _string_list(details.get("causes")),
        "biologicalTreatments": _string_list(treatment.get("biological")),
        "chemicalTreatments": _string_list(treatment.get("chemical")),
        "prevention": _string_list(treatment.get("prevention")),
        "dosage": _localized_text(treatment.get("dosage")) or "",
        "description": _localized_text(details.get("description")) or _localized_text(best.get("description")) or "",
    }


def _parse_plant_id_diagnosis(body):
    result = body.get("result") if isinstance(body.get("result"), dict) else body
    plant = _parse_plant_from_result(result, body)
    health = _parse_health(body, "Plant.id")
    return {"plant": plant, "health": health, "raw": body}


def _parse_plant_from_result(result, body):
    classification = (
        result.get("classification")
        or result.get("plant")
        or result.get("plant_details")
        or {}
    )
    suggestions = []
    if isinstance(classification, dict):
        suggestions = classification.get("suggestions") or classification.get("classes") or []
    if not suggestions:
        suggestions = result.get("suggestions") or body.get("suggestions") or []
    best = suggestions[0] if isinstance(suggestions, list) and suggestions and isinstance(suggestions[0], dict) else {}
    details = best.get("details") if isinstance(best.get("details"), dict) else {}
    common_names = details.get("common_names") or best.get("common_names") or []
    common_name = common_names[0] if isinstance(common_names, list) and common_names else ""
    scientific_name = (
        best.get("name")
        or best.get("scientific_name")
        or details.get("scientific_name")
        or ""
    )
    taxonomy = details.get("taxonomy") if isinstance(details.get("taxonomy"), dict) else {}
    family = taxonomy.get("family") or details.get("family") or ""
    return {
        "name": common_name or scientific_name or "Plante identifiee",
        "scientificName": scientific_name,
        "family": family,
        "confidence": _to_float(best.get("probability") or best.get("confidence") or best.get("score")),
        "description": _localized_text(details.get("description")) or "",
    }


def _needs_plantnet_fallback(plant, health):
    plant_name = (plant.get("name") or "").strip().lower()
    plant_confidence = _to_float(plant.get("confidence"))
    disease_name = (health.get("name") or "").strip().lower()
    disease_confidence = _to_float(health.get("confidence"))
    generic_plant = plant_name in {"", "plante identifiee", "plante non identifiee"}
    generic_disease = disease_name in {"", "maladie non determinee", "diagnostic non disponible"}
    return generic_plant or (plant_confidence < 0.2 and (generic_disease or disease_confidence < 0.2))


def _has_relevant_result(plant, health):
    plant_name = (plant.get("name") or "").strip().lower()
    disease_name = (health.get("name") or "").strip().lower()
    if plant_name and plant_name not in {"plante identifiee", "plante non identifiee"}:
        return True
    if disease_name and disease_name not in {"maladie non determinee", "diagnostic non disponible"}:
        return True
    return bool(health.get("isHealthy"))


def _merge_health(primary, enrichment):
    merged = dict(primary)
    if _health_is_weak(primary) and not _health_is_weak(enrichment):
        merged["name"] = enrichment.get("name") or merged.get("name")
        merged["confidence"] = enrichment.get("confidence") or merged.get("confidence")
        merged["isHealthy"] = enrichment.get("isHealthy")
    for field in ("symptoms", "causes", "biologicalTreatments", "chemicalTreatments", "prevention"):
        merged[field] = _merge_string_lists(primary.get(field), enrichment.get(field))
    for field in ("dosage", "description"):
        if not merged.get(field) and enrichment.get(field):
            merged[field] = enrichment[field]
    merged["provider"] = "Plant.id + Crop.Health"
    return merged


def _health_is_weak(health):
    name = (health.get("name") or "").strip().lower()
    confidence = _to_float(health.get("confidence"))
    return name in {"", "maladie non determinee", "diagnostic non disponible"} or confidence < 0.2


def _merge_string_lists(first, second):
    values = []
    for item in (first or []) + (second or []):
        text = str(item).strip()
        if text and text not in values:
            values.append(text)
    return values


def _post_json(url, payload, headers, label):
    data = json.dumps(payload).encode("utf-8")
    request_headers = {"Content-Type": "application/json", **headers}
    return _post(url, data, request_headers, label)


def _post(url, data, headers, label):
    request = urllib.request.Request(url, data=data, headers=headers, method="POST")
    try:
        with urllib.request.urlopen(request, timeout=45, context=_ssl_context()) as response:
            response_body = response.read().decode("utf-8")
    except urllib.error.HTTPError as exc:
        detail = exc.read().decode("utf-8", errors="ignore")[:220]
        if exc.code == 401:
            raise DroneAiConfigurationError(
                f"{label} a refuse la cle API. Collez la cle complete et active dans le fichier .env, puis redemarrez Agricheck Admin."
            ) from exc
        raise DroneAiRemoteError(f"{label} a refuse l'analyse IA ({exc.code}). {detail}") from exc
    except (urllib.error.URLError, TimeoutError) as exc:
        raise DroneAiRemoteError(f"{label} est indisponible. Verifiez la connexion Internet du PC serveur.") from exc

    try:
        return json.loads(response_body)
    except json.JSONDecodeError as exc:
        raise DroneAiRemoteError(f"{label} a renvoye une reponse illisible.") from exc


def _ssl_context():
    if certifi:
        return ssl.create_default_context(cafile=certifi.where())
    return ssl.create_default_context()


def _multipart_body(fields, files):
    boundary = f"----Agricheck{uuid.uuid4().hex}"
    chunks = []
    for name, value in fields.items():
        chunks.extend(
            [
                f"--{boundary}\r\n".encode("utf-8"),
                f'Content-Disposition: form-data; name="{name}"\r\n\r\n'.encode("utf-8"),
                str(value).encode("utf-8"),
                b"\r\n",
            ]
        )
    for name, (filename, content_type, content) in files.items():
        chunks.extend(
            [
                f"--{boundary}\r\n".encode("utf-8"),
                (
                    f'Content-Disposition: form-data; name="{name}"; filename="{filename}"\r\n'
                    f"Content-Type: {content_type}\r\n\r\n"
                ).encode("utf-8"),
                content,
                b"\r\n",
            ]
        )
    chunks.append(f"--{boundary}--\r\n".encode("utf-8"))
    return b"".join(chunks), f"multipart/form-data; boundary={boundary}"


def _sync_disease(health, plant, risk_label):
    disease_name = health.get("name") or "Maladie detectee"
    disease, _created = Disease.objects.get_or_create(
        name=disease_name,
        defaults={
            "crop": plant.get("name") or "",
            "symptoms": "\n".join(health.get("symptoms") or []),
            "causes": "\n".join(health.get("causes") or []),
            "risk_level": _disease_risk_value(risk_label),
        },
    )
    changed = False
    if not disease.crop and plant.get("name"):
        disease.crop = plant["name"]
        changed = True
    if not disease.symptoms and health.get("symptoms"):
        disease.symptoms = "\n".join(health["symptoms"])
        changed = True
    if not disease.causes and health.get("causes"):
        disease.causes = "\n".join(health["causes"])
        changed = True
    disease_risk = _disease_risk_value(risk_label)
    if disease.risk_level != disease_risk:
        disease.risk_level = disease_risk
        changed = True
    if changed:
        disease.save(update_fields=["crop", "symptoms", "causes", "risk_level"])
    return disease


def _sync_treatment(disease, health):
    product_parts = []
    biological = health.get("biologicalTreatments") or []
    chemical = health.get("chemicalTreatments") or []
    if biological:
        product_parts.append("Biologique : " + "; ".join(biological))
    if chemical:
        product_parts.append("Chimique : " + "; ".join(chemical))
    if not product_parts:
        product_parts.append("Traitement a confirmer selon le produit disponible localement.")

    defaults = {
        "product_recommended": "\n".join(product_parts),
        "dosage": health.get("dosage") or "Respecter l'etiquette du produit utilise.",
        "frequency": _frequency_for(bool(health.get("isHealthy")), _to_float(health.get("confidence"))),
        "prevention": "\n".join(health.get("prevention") or []),
    }
    treatment, created = Treatment.objects.get_or_create(disease=disease, defaults=defaults)
    if created:
        return treatment

    changed = False
    for field, value in defaults.items():
        if value and not getattr(treatment, field):
            setattr(treatment, field, value)
            changed = True
    if changed:
        treatment.save(update_fields=["product_recommended", "dosage", "frequency", "prevention"])
    return treatment


def _risk_label(is_healthy, confidence):
    confidence = _to_float(confidence)
    if is_healthy:
        return "Faible"
    if confidence >= 0.75:
        return "Eleve"
    if confidence >= 0.45:
        return "Moyen"
    return "A confirmer"


def _urgency_for(is_healthy, confidence):
    if is_healthy:
        return "Surveillance simple"
    if confidence >= 0.75:
        return "Intervention rapide"
    if confidence >= 0.45:
        return "A traiter sous observation"
    return "A confirmer avant traitement"


def _frequency_for(is_healthy, confidence):
    if is_healthy:
        return "Controle visuel chaque semaine"
    if confidence >= 0.75:
        return "Suivre le traitement selon etiquette, puis verifier apres 3 a 5 jours"
    return "Observer 48 a 72 heures et refaire une analyse si les symptomes evoluent"


def _risk_value(value):
    normalized = str(value).strip().lower()
    mapping = {
        "faible": AiAnalysis.RiskLevel.LOW,
        "low": AiAnalysis.RiskLevel.LOW,
        "moyen": AiAnalysis.RiskLevel.MEDIUM,
        "modere": AiAnalysis.RiskLevel.MEDIUM,
        "medium": AiAnalysis.RiskLevel.MEDIUM,
        "eleve": AiAnalysis.RiskLevel.HIGH,
        "high": AiAnalysis.RiskLevel.HIGH,
        "critique": AiAnalysis.RiskLevel.CRITICAL,
        "critical": AiAnalysis.RiskLevel.CRITICAL,
    }
    return mapping.get(normalized, AiAnalysis.RiskLevel.UNKNOWN)


def _disease_risk_value(value):
    normalized = _risk_value(value)
    mapping = {
        AiAnalysis.RiskLevel.LOW: Disease.RiskLevel.LOW,
        AiAnalysis.RiskLevel.MEDIUM: Disease.RiskLevel.MEDIUM,
        AiAnalysis.RiskLevel.HIGH: Disease.RiskLevel.HIGH,
        AiAnalysis.RiskLevel.CRITICAL: Disease.RiskLevel.CRITICAL,
    }
    return mapping.get(normalized, Disease.RiskLevel.MEDIUM)


def _percent_decimal(value):
    number = _to_float(value)
    if number <= 1:
        number *= 100
    return Decimal(str(round(number, 2)))


def _to_float(value):
    if isinstance(value, (int, float)):
        return float(value)
    try:
        return float(str(value))
    except (TypeError, ValueError):
        return 0.0


def _localized_text(value):
    if value is None:
        return None
    if isinstance(value, str):
        return value
    if isinstance(value, dict):
        for key in ("fr", "en"):
            if isinstance(value.get(key), str):
                return value[key]
        for item in value.values():
            if isinstance(item, str):
                return item
        return None
    return str(value)


def _string_list(value):
    if value is None:
        return []
    if isinstance(value, list):
        return [item for item in (_localized_text(item) for item in value) if item]
    text = _localized_text(value)
    return [text] if text else []


def _media_type(filename):
    return mimetypes.guess_type(filename)[0] or "image/jpeg"


def _real_secret(value):
    value = (value or "").strip()
    if not value or value.upper().startswith("REMPLACE_PAR"):
        return ""
    return value
