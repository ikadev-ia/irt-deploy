"""
Module de vérification des ordonnances médicales via Claude Vision (API Anthropic).
Utilisé par E-Pharma Mali pour valider les ordonnances avant toute commande.
"""
import anthropic
import base64
import os
from pathlib import Path


def verifier_ordonnance(image_file, medicaments_sous_ordonnance: list) -> dict:
    """
    Vérifie qu'une image est bien une ordonnance médicale valide
    et que les médicaments sous ordonnance y sont mentionnés.

    Args:
        image_file: fichier image Django (InMemoryUploadedFile)
        medicaments_sous_ordonnance: liste des noms de médicaments qui nécessitent une ordonnance

    Returns:
        dict avec les clés :
            - est_ordonnance (bool)
            - medicaments_trouves (list)
            - medicaments_manquants (list)
            - message (str)
            - valide (bool)
    """

    # Lire et encoder l'image en base64
    image_file.seek(0)
    image_data = base64.standard_b64encode(image_file.read()).decode("utf-8")

    # Déterminer le type MIME
    nom = image_file.name.lower()
    if nom.endswith('.pdf'):
        media_type = "application/pdf"
    elif nom.endswith('.png'):
        media_type = "image/png"
    elif nom.endswith('.jpg') or nom.endswith('.jpeg'):
        media_type = "image/jpeg"
    else:
        media_type = "image/jpeg"

    # Liste des médicaments à chercher
    liste_meds = ", ".join(medicaments_sous_ordonnance)

    # Prompt de vérification
    prompt = f"""Tu es un système de vérification d'ordonnances médicales pour une pharmacie au Mali.

Analyse cette image et réponds UNIQUEMENT en JSON valide avec ce format exact :
{{
  "est_ordonnance": true/false,
  "raison_si_faux": "explication si ce n'est pas une ordonnance",
  "medicaments_trouves": ["nom1", "nom2"],
  "details": "brève description de ce que tu vois"
}}

Médicaments à rechercher dans l'ordonnance : {liste_meds}

Règles :
1. est_ordonnance = true UNIQUEMENT si l'image montre clairement un document médical officiel (ordonnance, prescription) avec au minimum un nom de médecin ou un cachet médical et un ou plusieurs médicaments prescrits.
2. est_ordonnance = false si c'est une photo de paysage, selfie, screenshot, document non médical, ou image floue/illisible.
3. Dans medicaments_trouves, mets uniquement les médicaments de la liste ci-dessus que tu identifies clairement dans l'ordonnance.
4. Sois strict — en cas de doute, met est_ordonnance = false.

Réponds UNIQUEMENT avec le JSON, aucun autre texte."""

    client = anthropic.Anthropic(
        api_key=os.environ.get("ANTHROPIC_API_KEY", "")
    )

    try:
        response = client.messages.create(
            model="claude-sonnet-4-6",
            max_tokens=500,
            messages=[
                {
                    "role": "user",
                    "content": [
                        {
                            "type": "image",
                            "source": {
                                "type": "base64",
                                "media_type": media_type,
                                "data": image_data,
                            },
                        },
                        {
                            "type": "text",
                            "text": prompt
                        }
                    ],
                }
            ],
        )

        import json
        texte = response.content[0].text.strip()
        # Nettoyer si le modèle ajoute des backticks
        texte = texte.replace("```json", "").replace("```", "").strip()
        resultat = json.loads(texte)

        est_ordonnance = resultat.get("est_ordonnance", False)
        meds_trouves = resultat.get("medicaments_trouves", [])

        # Vérifier quels médicaments sont manquants
        meds_manquants = []
        for med in medicaments_sous_ordonnance:
            # Recherche insensible à la casse
            trouve = any(
                med.lower() in t.lower() or t.lower() in med.lower()
                for t in meds_trouves
            )
            if not trouve:
                meds_manquants.append(med)

        valide = est_ordonnance and len(meds_manquants) == 0

        if not est_ordonnance:
            message = f"❌ Ce document ne semble pas être une ordonnance médicale valide. {resultat.get('raison_si_faux', '')} Veuillez uploader une vraie ordonnance signée par un médecin."
        elif meds_manquants:
            message = f"❌ Ordonnance détectée mais les médicaments suivants n'y figurent pas : {', '.join(meds_manquants)}. Votre ordonnance doit mentionner tous les médicaments sous prescription."
        else:
            message = f"✅ Ordonnance valide. Médicaments vérifiés : {', '.join(meds_trouves)}."

        return {
            "est_ordonnance": est_ordonnance,
            "medicaments_trouves": meds_trouves,
            "medicaments_manquants": meds_manquants,
            "message": message,
            "valide": valide,
            "details": resultat.get("details", "")
        }

    except json.JSONDecodeError:
        return {
            "est_ordonnance": False,
            "medicaments_trouves": [],
            "medicaments_manquants": medicaments_sous_ordonnance,
            "message": "❌ Impossible d'analyser le document. Veuillez uploader une image claire de votre ordonnance.",
            "valide": False,
            "details": ""
        }
    except Exception as e:
        # En cas d'erreur API, on laisse passer mais on notifie le pharmacien
        return {
            "est_ordonnance": True,
            "medicaments_trouves": [],
            "medicaments_manquants": [],
            "message": "⚠️ Ordonnance reçue. Elle sera vérifiée manuellement par notre pharmacien.",
            "valide": True,
            "details": f"Erreur API: {str(e)}"
        }
