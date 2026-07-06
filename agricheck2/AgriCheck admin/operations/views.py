import json
from decimal import Decimal

from django.conf import settings
from django.contrib import messages
from django.contrib.auth.decorators import login_required
from django.contrib.auth.hashers import check_password, make_password
from django.contrib.auth.mixins import LoginRequiredMixin
from django.core import signing
from django.core.exceptions import PermissionDenied
from django.db.models import Q, Sum
from django.forms import modelform_factory
from django.http import JsonResponse
from django.shortcuts import get_object_or_404, redirect
from django.urls import reverse
from django.utils import timezone
from django.views.decorators.csrf import csrf_exempt
from django.views.generic import TemplateView, View



from .ai_services import (
    DroneAiConfigurationError,
    DroneAiError,
    apply_diagnosis_to_analysis,
    diagnose_uploaded_file,
    run_drone_analysis,
)
from .entities import ENTITY_CONFIG, money
from .models import (
    AiAnalysis,
    CompanyClient,
    Drone,
    FieldMission,
    ImportantAlert,
    Invoice,
    MobileNotification,
    MobileUser,
    Parcel,
    Report,
    Treatment,
)


TOKEN_SALT = "agricheck-mobile-token"


class AdminRequiredMixin(LoginRequiredMixin):
    def dispatch(self, request, *args, **kwargs):
        if request.user.is_authenticated and not (request.user.is_staff or request.user.is_superuser):
            raise PermissionDenied("Acces reserve a l'equipe Agricheck.")
        return super().dispatch(request, *args, **kwargs)


class DashboardView(AdminRequiredMixin, TemplateView):
    template_name = "operations/dashboard.html"

    def get_context_data(self, **kwargs):
        context = super().get_context_data(**kwargs)
        today = timezone.localdate()
        invoices = Invoice.objects.all()
        amount_paid_month = (
            invoices.filter(issued_at__year=today.year, issued_at__month=today.month).aggregate(total=Sum("amount_paid"))[
                "total"
            ]
            or Decimal("0")
        )
        amount_paid_year = invoices.filter(issued_at__year=today.year).aggregate(total=Sum("amount_paid"))["total"] or Decimal("0")
        unpaid_invoices = [invoice for invoice in invoices if invoice.amount_unpaid > 0]

        context.update(
            {
                "active_page": "dashboard",
                "mobile_clients": MobileUser.objects.count(),
                "company_clients": CompanyClient.objects.count(),
                "total_clients": MobileUser.objects.count() + CompanyClient.objects.count(),
                "total_drones": Drone.objects.count(),
                "total_missions": FieldMission.objects.count(),
                "total_analyses": AiAnalysis.objects.count(),
                "total_reports": Report.objects.count(),
                "monthly_revenue": money(amount_paid_month),
                "annual_revenue": money(amount_paid_year),
                "unpaid_invoice_count": len(unpaid_invoices),
                "unpaid_total": money(sum((invoice.amount_unpaid for invoice in unpaid_invoices), Decimal("0"))),
                "high_risk_count": AiAnalysis.objects.filter(risk_level__in=[AiAnalysis.RiskLevel.HIGH, AiAnalysis.RiskLevel.CRITICAL]).count(),
                "open_alerts": ImportantAlert.objects.filter(is_resolved=False)[:6],
                "recent_missions": FieldMission.objects.select_related("company", "parcel", "drone", "pilot")[:5],
                "recent_analyses": AiAnalysis.objects.select_related("mobile_user", "company", "disease")[:5],
                "recent_reports": Report.objects.select_related("company", "parcel")[:5],
                "mobile_project_path": settings.AGRICHECK_MOBILE_PROJECT_PATH,
                "client_project_path": settings.AGRICHECK_CLIENT_PROJECT_PATH,
                "ai_proxy_ready": bool(getattr(settings, "PLANT_ID_API_KEY", "")),
            }
        )
        return context


class EntityListView(AdminRequiredMixin, TemplateView):
    template_name = "operations/entity_list.html"

    def get_config(self):
        return get_object_or_404_config(self.kwargs["slug"])

    def get_context_data(self, **kwargs):
        context = super().get_context_data(**kwargs)
        slug = self.kwargs["slug"]
        config = self.get_config()
        query = self.request.GET.get("q", "").strip()
        selected_filter = self.request.GET.get("filter", "").strip()

        objects = config["model"].objects.all()
        if config.get("base_filter"):
            objects = objects.filter(**config["base_filter"])
        if config.get("select_related"):
            objects = objects.select_related(*config["select_related"])
        if query:
            search_query = Q()
            for field in config.get("search", []):
                search_query |= Q(**{f"{field}__icontains": query})
            objects = objects.filter(search_query)
        if selected_filter and config.get("filter_field"):
            if config["filter_field"] == "is_active":
                objects = objects.filter(is_active=selected_filter == "1")
            else:
                objects = objects.filter(**{config["filter_field"]: selected_filter})

        rows = []
        for item in objects:
            row = {
                "object": item,
                "cells": [renderer(item) for _, renderer in config["columns"]],
                "edit_url": reverse("operations:entity_update", kwargs={"slug": slug, "pk": item.pk}),
            }
            if slug == "analyses":
                row["analysis_url"] = reverse("operations:run_drone_analysis", kwargs={"pk": item.pk})
                row["has_ai_result"] = bool(item.disease_label)
            rows.append(row)

        context.update(
            {
                "active_page": slug,
                "config": config,
                "slug": slug,
                "query": query,
                "selected_filter": selected_filter,
                "rows": rows,
                "headers": [label for label, _renderer in config["columns"]],
                "create_url": reverse("operations:entity_create", kwargs={"slug": slug}),
                "parcels_url": reverse("operations:entity_list", kwargs={"slug": "parcels"}) if slug == "companies" else "",
            }
        )
        return context


class EntityFormView(AdminRequiredMixin, View):
    template_name = "operations/entity_form.html"
    action = "create"

    def get(self, request, slug, pk=None):
        config = get_object_or_404_config(slug)
        instance = get_object_or_404(config["model"], pk=pk) if pk else None
        form = build_form(config, request.POST or None, request.FILES or None, instance=instance)
        return render_form(request, self.template_name, config, slug, form, instance, self.action)

    def post(self, request, slug, pk=None):
        config = get_object_or_404_config(slug)
        instance = get_object_or_404(config["model"], pk=pk) if pk else None
        form = build_form(config, request.POST, request.FILES, instance=instance)
        if form.is_valid():
            obj = form.save(commit=False)
            if slug == "analyses":
                obj.source = AiAnalysis.Source.DRONE
                if obj.mission:
                    obj.company = obj.company or obj.mission.company
                    obj.parcel = obj.parcel or obj.mission.parcel
            obj.save()
            form.save_m2m()
            messages.success(request, "Enregistrement Agricheck sauvegarde.")
            return redirect("operations:entity_list", slug=slug)
        return render_form(request, self.template_name, config, slug, form, instance, self.action)


class EntityUpdateView(EntityFormView):
    action = "update"


def get_object_or_404_config(slug):
    config = ENTITY_CONFIG.get(slug)
    if not config:
        raise PermissionDenied("Module Agricheck inconnu.")
    return config


def build_form(config, data=None, files=None, instance=None):
    form_class = modelform_factory(config["model"], fields=config["fields"])
    return form_class(data=data, files=files, instance=instance)


def render_form(request, template_name, config, slug, form, instance, action):
    from django.shortcuts import render

    analysis_preview = ""
    if slug == "reports" and instance and instance.analysis:
        analysis_preview = instance.analysis.result_summary

    return render(
        request,
        template_name,
        {
            "active_page": slug,
            "config": config,
            "slug": slug,
            "form": form,
            "instance": instance,
            "action": action,
            "list_url": reverse("operations:entity_list", kwargs={"slug": slug}),
            "analysis_preview": analysis_preview,
        },
    )


@login_required
def run_drone_analysis_view(request, pk):
    if not (request.user.is_staff or request.user.is_superuser):
        raise PermissionDenied("Acces reserve a l'equipe Agricheck.")
    if request.method != "POST":
        return redirect("operations:entity_list", slug="analyses")

    analysis = get_object_or_404(AiAnalysis, pk=pk, source=AiAnalysis.Source.DRONE)
    try:
        run_drone_analysis(analysis)
        refresh_linked_reports(analysis)
    except DroneAiConfigurationError as exc:
        messages.error(request, str(exc))
    except DroneAiError as exc:
        messages.error(request, str(exc))
    else:
        messages.success(request, "Analyse IA terminee. La fiche et les rapports lies ont ete mis a jour.")
    return redirect("operations:entity_list", slug="analyses")


def refresh_linked_reports(analysis):
    for report in analysis.reports.select_related("analysis", "analysis__disease").all():
        report.ai_result = analysis.result_summary
        report.recommended_treatment = treatment_text_for_analysis(analysis)
        report.save()


def treatment_text_for_analysis(analysis):
    if not analysis.disease:
        return ""
    treatment = analysis.disease.treatments.first()
    if not treatment:
        return ""
    parts = [
        treatment.product_recommended,
        treatment.dosage,
        treatment.frequency,
        treatment.prevention,
    ]
    return "\n".join(part for part in parts if part)


def json_body(request):
    if not request.body:
        return {}
    try:
        return json.loads(request.body.decode("utf-8"))
    except json.JSONDecodeError:
        return {}


def mobile_user_payload(user):
    return {
        "id": str(user.pk),
        "fullName": user.full_name,
        "full_name": user.full_name,
        "phone": user.phone,
        "email": user.email or "",
        "createdAt": user.created_at.isoformat(),
        "created_at": user.created_at.isoformat(),
        "avatarUrl": user.avatar_url,
    }


def make_mobile_token(user):
    return signing.dumps({"mobile_user_id": user.pk}, salt=TOKEN_SALT)


def mobile_user_from_request(request):
    header = request.headers.get("Authorization", "")
    if not header.lower().startswith("bearer "):
        return None
    token = header.split(" ", 1)[1].strip()
    try:
        data = signing.loads(token, salt=TOKEN_SALT, max_age=60 * 60 * 24 * 30)
    except signing.BadSignature:
        return None
    return MobileUser.objects.filter(pk=data.get("mobile_user_id"), is_active=True).first()


@csrf_exempt
def api_mobile_register(request):
    if request.method != "POST":
        return JsonResponse({"detail": "Methode non autorisee."}, status=405)
    payload = json_body(request)
    full_name = (payload.get("fullName") or payload.get("full_name") or payload.get("name") or "").strip()
    phone = (payload.get("phone") or payload.get("telephone") or "").strip()
    email = (payload.get("email") or "").strip() or None
    password = payload.get("password") or ""

    if not full_name or not phone or not password:
        return JsonResponse({"detail": "Nom, telephone et mot de passe sont obligatoires."}, status=400)
    if MobileUser.objects.filter(phone=phone).exists():
        return JsonResponse({"detail": "Ce telephone est deja inscrit."}, status=400)
    if email and MobileUser.objects.filter(email=email).exists():
        return JsonResponse({"detail": "Cet email est deja inscrit."}, status=400)

    user = MobileUser.objects.create(
        full_name=full_name,
        phone=phone,
        email=email,
        password_hash=make_password(password),
    )
    return JsonResponse({"token": make_mobile_token(user), "user": mobile_user_payload(user)}, status=201)


@csrf_exempt
def api_mobile_login(request):
    if request.method != "POST":
        return JsonResponse({"detail": "Methode non autorisee."}, status=405)
    payload = json_body(request)
    identifier = (payload.get("identifier") or payload.get("email_or_phone") or "").strip()
    password = payload.get("password") or ""
    user = MobileUser.objects.filter(Q(phone=identifier) | Q(email=identifier), is_active=True).first()
    if not user or not check_password(password, user.password_hash):
        return JsonResponse({"detail": "Identifiants Agricheck incorrects."}, status=401)
    return JsonResponse({"token": make_mobile_token(user), "user": mobile_user_payload(user)})


@csrf_exempt
def api_mobile_password_reset(request):
    if request.method != "POST":
        return JsonResponse({"detail": "Methode non autorisee."}, status=405)
    return JsonResponse({"message": "Demande recue. L'equipe Agricheck peut traiter la reinitialisation."})


def api_mobile_advice(request):
    crop = request.GET.get("crop", "").strip()
    treatments = Treatment.objects.select_related("disease")
    if crop:
        treatments = treatments.filter(disease__crop__icontains=crop)
    advice = [
        {
            "id": str(treatment.pk),
            "title": treatment.disease.name,
            "message": treatment.prevention or treatment.product_recommended,
            "category": "Traitement",
            "crop": treatment.disease.crop,
            "created_at": treatment.updated_at.isoformat(),
        }
        for treatment in treatments[:50]
    ]
    return JsonResponse({"advice": advice})


def api_mobile_notifications(request):
    user = mobile_user_from_request(request)
    if not user:
        return JsonResponse({"detail": "Session mobile invalide."}, status=401)
    notifications = [
        {
            "id": str(item.pk),
            "title": item.title,
            "message": item.message,
            "type": item.type,
            "is_read": item.is_read,
            "created_at": item.created_at.isoformat(),
        }
        for item in user.notifications.all()[:50]
    ]
    return JsonResponse({"notifications": notifications})


@csrf_exempt
def api_mobile_analysis_sync(request):
    user = mobile_user_from_request(request)
    if not user:
        return JsonResponse({"detail": "Session mobile invalide."}, status=401)
    if request.method != "POST":
        return JsonResponse({"detail": "Methode non autorisee."}, status=405)
    payload = json_body(request)
    analysis = AiAnalysis.objects.create(
        source=AiAnalysis.Source.MOBILE,
        mobile_user=user,
        image_url=payload.get("imagePath") or payload.get("image_url") or "",
        detected_plant=payload.get("plantName") or payload.get("plant_name") or "",
        detected_disease=payload.get("diseaseName") or payload.get("disease_name") or "",
        confidence=normalize_confidence(payload.get("diseaseConfidence") or payload.get("confidence")),
        risk_level=normalize_risk(payload.get("riskLevel") or payload.get("risk_level") or ""),
        provider=AiAnalysis.Provider.AGRICHECK,
        raw_ai_response=payload,
    )
    return JsonResponse({"id": str(analysis.pk), "created_at": analysis.analyzed_at.isoformat()}, status=201)


@csrf_exempt
def api_mobile_diagnostics(request):
    if request.method != "POST":
        return JsonResponse({"detail": "Methode non autorisee."}, status=405)
    user = mobile_user_from_request(request)
    if not user:
        return JsonResponse({"detail": "Session mobile invalide."}, status=401)
    uploaded_image = request.FILES.get("image")
    try:
        payload = diagnose_uploaded_file(
            uploaded_image,
            latitude=float_value(request.POST.get("latitude"), 12.6392),
            longitude=float_value(request.POST.get("longitude"), -8.0029),
        )
    except DroneAiConfigurationError as exc:
        return JsonResponse({"detail": str(exc)}, status=503)
    except DroneAiError as exc:
        return JsonResponse({"detail": str(exc)}, status=503)
    if hasattr(uploaded_image, "seek"):
        uploaded_image.seek(0)
    analysis = AiAnalysis.objects.create(
        source=AiAnalysis.Source.MOBILE,
        mobile_user=user,
        image=uploaded_image,
        provider=AiAnalysis.Provider.PLANT_ID,
        raw_ai_response=payload,
    )
    apply_diagnosis_to_analysis(analysis, payload)
    payload.update(
        {
            "analysisId": str(analysis.pk),
            "analysis_id": str(analysis.pk),
            "saved": True,
            "savedAt": analysis.analyzed_at.isoformat(),
            "saved_at": analysis.analyzed_at.isoformat(),
            "image_url": analysis.image.url if analysis.image else "",
        }
    )
    analysis.raw_ai_response = payload
    analysis.save(update_fields=["raw_ai_response"])
    return JsonResponse(payload)


@csrf_exempt
def api_drone_capture(request):
    if request.method != "POST":
        return JsonResponse({"detail": "Methode non autorisee."}, status=405)

    expected_key = getattr(settings, "DRONE_API_KEY", "").strip()
    if expected_key:
        provided_key = request.headers.get("X-Drone-Key", "").strip()
        auth_header = request.headers.get("Authorization", "")
        if auth_header.lower().startswith("bearer "):
            provided_key = auth_header.split(" ", 1)[1].strip()
        if provided_key != expected_key:
            return JsonResponse({"detail": "Cle drone invalide."}, status=401)

    payload = json_body(request) if (request.content_type or "").startswith("application/json") else {}
    image = request.FILES.get("image")
    image_url = request.POST.get("image_url") or payload.get("image_url") or ""
    if not image and not image_url:
        return JsonResponse({"detail": "Envoyez une image ou image_url pour creer la capture drone."}, status=400)

    mission = optional_object(FieldMission, request.POST.get("mission_id") or payload.get("mission_id"))
    company = optional_object(CompanyClient, request.POST.get("company_id") or payload.get("company_id"))
    parcel = optional_object(Parcel, request.POST.get("parcel_id") or payload.get("parcel_id"))
    if mission:
        company = company or mission.company
        parcel = parcel or mission.parcel

    analysis = AiAnalysis.objects.create(
        source=AiAnalysis.Source.DRONE,
        company=company,
        parcel=parcel,
        mission=mission,
        image=image,
        image_url=image_url,
        provider=AiAnalysis.Provider.AGRICHECK,
        raw_ai_response={
            "received_from": "drone",
            "drone_id": request.POST.get("drone_id") or payload.get("drone_id") or "",
            "filename": getattr(image, "name", "") if image else "",
        },
    )
    return JsonResponse(
        {
            "id": str(analysis.pk),
            "status": "capture_received",
            "analysis_url": reverse("operations:entity_update", kwargs={"slug": "analyses", "pk": analysis.pk}),
        },
        status=201,
    )


@csrf_exempt
def api_client_company_register(request):
    if request.method != "POST":
        return JsonResponse({"detail": "Methode non autorisee."}, status=405)
    payload = json_body(request)
    company_name = (payload.get("company_name") or payload.get("name") or "").strip()
    manager_name = (payload.get("manager_name") or payload.get("responsable") or "").strip()
    phone = (payload.get("phone") or payload.get("telephone") or "").strip()
    email = (payload.get("email") or "").strip().lower()
    subscription = payload.get("subscription_type") or payload.get("subscription") or CompanyClient.SubscriptionPlan.STARTER
    payment_method = payload.get("payment_method") or ""

    if not company_name or not manager_name or not email:
        return JsonResponse({"detail": "Entreprise, responsable et email sont obligatoires."}, status=400)
    if subscription not in CompanyClient.SubscriptionPlan.values:
        subscription = CompanyClient.SubscriptionPlan.STARTER
    if payment_method and payment_method not in CompanyClient.PaymentMethod.values:
        payment_method = ""
    try:
        subscription_price = int(
            payload.get("subscription_price_xof")
            or CompanyClient.SUBSCRIPTION_PRICES.get(subscription, 75000)
        )
    except (TypeError, ValueError):
        subscription_price = CompanyClient.SUBSCRIPTION_PRICES.get(subscription, 75000)

    lookup = Q(email__iexact=email)
    if phone:
        lookup |= Q(phone=phone)
    company = CompanyClient.objects.filter(lookup).first()
    if company is None:
        company = CompanyClient(email=email)

    company.name = company_name
    company.manager_name = manager_name
    company.phone = phone
    company.address = payload.get("address") or ""
    company.hectares = payload.get("hectares") or 0
    company.subscription = subscription
    company.subscription_price_xof = subscription_price
    company.payment_method = payment_method
    company.external_client_id = str(payload.get("client_id") or payload.get("id") or "")
    company.status = CompanyClient.Status.PENDING
    company.save()

    invoice_number = f"ADM-{company.pk}-{timezone.localdate():%Y%m}"
    invoice, _created = Invoice.objects.get_or_create(
        company=company,
        number=invoice_number,
        defaults={
            "subscription": company.get_subscription_display(),
            "amount_total": Decimal(company.subscription_price_xof),
            "amount_paid": 0,
            "status": Invoice.Status.PENDING,
        },
    )
    invoice.subscription = company.get_subscription_display()
    invoice.amount_total = Decimal(company.subscription_price_xof)
    invoice.status = Invoice.Status.PENDING if invoice.amount_unpaid else Invoice.Status.PAID
    invoice.save()

    return JsonResponse(
        {
            "id": str(company.pk),
            "company_id": str(company.pk),
            "name": company.name,
            "manager_name": company.manager_name,
            "email": company.email,
            "phone": company.phone,
            "subscription": company.subscription,
            "payment_method": company.payment_method,
            "invoice": {
                "id": str(invoice.pk),
                "number": invoice.number,
                "amount_total": str(invoice.amount_total),
                "amount_paid": str(invoice.amount_paid),
                "amount_unpaid": str(invoice.amount_unpaid),
                "status": invoice.status,
            },
        },
        status=201,
    )


def api_client_published_reports(request):
    company_id = request.GET.get("company_id")
    company_email = request.GET.get("company_email", "").strip()
    company_phone = request.GET.get("company_phone", "").strip()
    company_name = request.GET.get("company_name", "").strip()
    reports = Report.objects.select_related("company", "parcel", "analysis", "analysis__disease").filter(status=Report.Status.PUBLISHED)
    invoices = Invoice.objects.select_related("company").all()
    if company_id:
        reports = reports.filter(company_id=company_id)
        invoices = invoices.filter(company_id=company_id)
    else:
        company_lookup = Q()
        if company_email:
            company_lookup |= Q(company__email__iexact=company_email)
        if company_phone:
            company_lookup |= Q(company__phone=company_phone)
        if company_name:
            company_lookup |= Q(company__name__iexact=company_name)
        if company_lookup:
            reports = reports.filter(company_lookup)
            invoices = invoices.filter(company_lookup)
        else:
            reports = reports.none()
            invoices = invoices.none()

    data = [
        {
            "id": str(report.pk),
            "company": report.company.name,
            "parcel": report.parcel.name if report.parcel else "",
            "title": report.title,
            "ai_result": report.analysis_result_display,
            "recommended_treatment": report.recommended_treatment_display,
            "detected_plant": report.analysis.detected_plant if report.analysis else "",
            "detected_disease": report.analysis.disease_label if report.analysis else "",
            "confidence": str(report.analysis.confidence) if report.analysis and report.analysis.confidence is not None else "",
            "risk_level": report.analysis.risk_level if report.analysis else "",
            "risk_label": report.analysis.get_risk_level_display() if report.analysis else "",
            "pdf_url": file_url(request, report.pdf),
            "published_at": report.published_at.isoformat() if report.published_at else "",
        }
        for report in reports[:100]
    ]
    invoice_data = [
        {
            "id": str(invoice.pk),
            "number": invoice.number,
            "company": invoice.company.name,
            "subscription": invoice.subscription,
            "amount_total": str(invoice.amount_total),
            "amount_paid": str(invoice.amount_paid),
            "amount_unpaid": str(invoice.amount_unpaid),
            "status": invoice.status,
            "status_label": invoice.get_status_display(),
            "issued_at": invoice.issued_at.isoformat() if invoice.issued_at else "",
            "due_date": invoice.due_date.isoformat() if invoice.due_date else "",
            "paid_at": invoice.paid_at.isoformat() if invoice.paid_at else "",
            "pdf_url": file_url(request, invoice.pdf),
        }
        for invoice in invoices[:100]
    ]
    treatment_data = []
    for report in reports[:100]:
        treatment_text = report.recommended_treatment_display
        if not treatment_text:
            continue
        treatment_data.append(
            {
                "id": str(report.pk),
                "report_id": str(report.pk),
                "title": report.title,
                "company": report.company.name,
                "parcel": report.parcel.name if report.parcel else "",
                "disease": report.analysis.disease_label if report.analysis else "",
                "risk_level": report.analysis.risk_level if report.analysis else "",
                "risk_label": report.analysis.get_risk_level_display() if report.analysis else "",
                "recommended_treatment": treatment_text,
                "published_at": report.published_at.isoformat() if report.published_at else "",
            }
        )
    return JsonResponse({"reports": data, "invoices": invoice_data, "treatments": treatment_data})


def file_url(request, file_field):
    if not file_field:
        return ""
    return request.build_absolute_uri(file_field.url)


def normalize_risk(value):
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


def normalize_confidence(value):
    if value in (None, ""):
        return None
    try:
        confidence = Decimal(str(value))
    except Exception:
        return None
    if confidence <= 1:
        confidence *= 100
    return confidence.quantize(Decimal("0.01"))


def float_value(value, default):
    try:
        return float(value)
    except (TypeError, ValueError):
        return default


def optional_object(model, pk):
    if not pk:
        return None
    try:
        return model.objects.filter(pk=pk).first()
    except (TypeError, ValueError):
        return None
