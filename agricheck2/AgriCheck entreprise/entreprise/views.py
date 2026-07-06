from django.contrib import messages
from django.contrib.auth import login
from django.contrib.auth.decorators import login_required
from django.conf import settings
from django.db.models import Sum
from django.shortcuts import get_object_or_404, redirect, render
from django.utils import timezone
from django.utils.dateparse import parse_datetime

from .admin_bridge import (
    fetch_admin_invoices,
    fetch_admin_reports,
    fetch_admin_treatments,
    find_admin_report,
    sync_company_registration,
)
from .forms import CompanyRegistrationForm
from .models import (
    Analysis,
    Invoice,
    Notification,
    Parcel,
    Report,
    Treatment,
)


def scope_context(request):
    if request.user.is_authenticated:
        return request.user.company
    return None


def scoped(qs, request, selected_company=None, company_field="company"):
    company = selected_company or getattr(request.user, "company", None)
    if company:
        return qs.filter(**{f"{company_field}_id": company.id})
    return qs.none()


def common_context(request, active_page):
    selected_company = scope_context(request)
    unread_notifications = scoped(Notification.objects.filter(is_read=False), request, selected_company).count()
    return {
        "active_page": active_page,
        "selected_company": selected_company,
        "unread_notifications": unread_notifications,
    }


def percent_rows(rows, total):
    if not total:
        return []
    return [
        {
            "label": label,
            "count": count,
            "percent": round((count / total) * 100, 1),
        }
        for label, count in rows
    ]


def home(request):
    return render(request, "entreprise/home.html")


def about(request):
    return render(request, "entreprise/about.html")


def register(request):
    if request.method == "POST":
        form = CompanyRegistrationForm(request.POST)
        if form.is_valid():
            user = form.save()
            Notification.objects.create(
                company=user.company,
                user=user,
                type=Notification.Type.ACCOUNT_CREATED,
                title="Inscription client enregistree",
                message=(
                    "Votre espace client Agricheck est cree. Notre equipe peut maintenant "
                    "configurer vos parcelles, resultats Agricheck et rapports."
                ),
                priority=Notification.Priority.INFO,
            )
            sync_company_registration(user)
            login(request, user, backend=settings.AUTHENTICATION_BACKENDS[0])
            messages.success(request, "Votre inscription est enregistree.")
            return redirect("entreprise:dashboard")
    else:
        form = CompanyRegistrationForm()

    return render(request, "registration/register.html", {"form": form})


@login_required
def dashboard(request):
    context = common_context(request, "dashboard")
    selected_company = context["selected_company"]

    parcels_qs = scoped(Parcel.objects.all(), request, selected_company)
    analyses_qs = scoped(Analysis.objects.select_related("parcel", "disease", "mission"), request, selected_company)
    reports_qs = scoped(Report.objects.select_related("mission", "parcel"), request, selected_company)
    published_reports_qs = reports_qs.filter(is_published=True)
    admin_reports = fetch_admin_reports(request.user.company)
    admin_invoices = fetch_admin_invoices(request.user.company)
    admin_treatments = fetch_admin_treatments(request.user.company)
    local_invoice_count = scoped(Invoice.objects.all(), request, selected_company).count()

    total_surface = parcels_qs.aggregate(total=Sum("surface_hectares"))["total"] or 0
    disease_ids = analyses_qs.exclude(disease__isnull=True).values_list("disease_id", flat=True).distinct()
    local_treatment_count = Treatment.objects.filter(disease_id__in=disease_ids).count()

    context.update(
        {
            "dashboard_company": request.user.company,
            "parcel_count": parcels_qs.count(),
            "report_count": published_reports_qs.count() + len(admin_reports),
            "treatment_count": local_treatment_count + len(admin_treatments),
            "invoice_count": local_invoice_count + len(admin_invoices),
            "total_surface": total_surface,
            "latest_report": published_reports_qs.first(),
            "latest_admin_report": admin_reports[0] if admin_reports else None,
            "latest_admin_invoice": admin_invoices[0] if admin_invoices else None,
        }
    )
    return render(request, "entreprise/dashboard.html", context)


@login_required
def parcels(request):
    context = common_context(request, "parcels")
    selected_company = context["selected_company"]
    parcels_qs = scoped(Parcel.objects.select_related("company"), request, selected_company)

    context.update({"parcels": parcels_qs})
    return render(request, "entreprise/parcels.html", context)


@login_required
def parcel_detail(request, parcel_id):
    context = common_context(request, "parcels")
    selected_company = context["selected_company"]
    parcel = get_object_or_404(scoped(Parcel.objects.all(), request, selected_company), pk=parcel_id)
    reports_qs = parcel.reports.select_related("mission", "analysis", "analysis__disease").filter(
        company=selected_company,
        is_published=True,
    )
    context.update(
        {
            "parcel": parcel,
            "latest_reports": reports_qs[:5],
        }
    )
    return render(request, "entreprise/parcel_detail.html", context)


@login_required
def reports(request):
    context = common_context(request, "reports")
    selected_company = context["selected_company"]
    reports_qs = scoped(
        Report.objects.select_related("company", "mission", "parcel", "analysis", "analysis__disease").prefetch_related(
            "mission__images"
        ),
        request,
        selected_company,
    )
    reports_qs = reports_qs.filter(is_published=True)

    context.update({"reports": reports_qs, "admin_reports": fetch_admin_reports(selected_company)})
    return render(request, "entreprise/reports.html", context)


@login_required
def report_detail(request, report_id):
    context = common_context(request, "reports")
    selected_company = context["selected_company"]
    report = get_object_or_404(
        scoped(
            Report.objects.select_related("company", "mission", "parcel", "analysis", "analysis__disease")
            .prefetch_related("mission__images")
            .filter(is_published=True),
            request,
            selected_company,
        ),
        pk=report_id,
    )
    context.update({"report": report})
    return render(request, "entreprise/report_detail.html", context)


@login_required
def admin_report_detail(request, report_id):
    context = common_context(request, "reports")
    report = find_admin_report(request.user.company, report_id)
    if not report:
        report = get_object_or_404(Report.objects.none(), pk=report_id)
    context.update({"admin_report": report})
    return render(request, "entreprise/admin_report_detail.html", context)


@login_required
def treatments(request):
    context = common_context(request, "treatments")
    selected_company = context["selected_company"]
    analyses_qs = scoped(Analysis.objects.select_related("disease"), request, selected_company)
    disease_ids = analyses_qs.exclude(disease__isnull=True).values_list("disease_id", flat=True).distinct()

    treatments_qs = Treatment.objects.select_related("disease").filter(disease_id__in=disease_ids)
    treatment_items = []
    for item in fetch_admin_treatments(selected_company):
        treatment_items.append(
            {
                "source": "Agricheck Admin",
                "disease": item.get("disease") or item.get("title") or "Traitement recommande",
                "risk_level": item.get("risk_level") or "medium",
                "risk_label": item.get("risk_label") or "A confirmer",
                "recommended_treatment": item.get("recommended_treatment") or "",
                "product": "",
                "dosage": "",
                "frequency": "",
                "prevention": "",
                "parcel": item.get("parcel") or "",
                "report_id": item.get("report_id") or item.get("id"),
            }
        )
    for treatment in treatments_qs:
        treatment_items.append(
            {
                "source": "Agricheck Client",
                "disease": treatment.disease.name,
                "risk_level": treatment.disease.risk_level,
                "risk_label": treatment.disease.get_risk_level_display(),
                "recommended_treatment": treatment.natural_treatments,
                "product": treatment.recommended_products,
                "dosage": treatment.dosage,
                "frequency": treatment.frequency,
                "prevention": treatment.prevention,
                "parcel": "",
                "report_id": "",
            }
        )

    context.update({"treatments": treatment_items})
    return render(request, "entreprise/treatments.html", context)


@login_required
def history(request):
    context = common_context(request, "history")
    selected_company = context["selected_company"]
    analyses_qs = scoped(Analysis.objects.select_related("parcel", "disease", "mission"), request, selected_company)
    reports_qs = scoped(
        Report.objects.select_related("parcel", "analysis", "analysis__disease").filter(is_published=True),
        request,
        selected_company,
    )
    disease_ids = analyses_qs.exclude(disease__isnull=True).values_list("disease_id", flat=True).distinct()
    treatments_by_disease = {
        treatment.disease_id: treatment
        for treatment in Treatment.objects.select_related("disease").filter(disease_id__in=disease_ids)
    }

    timeline_items = []
    for analysis in analyses_qs:
        if analysis.disease_id in treatments_by_disease:
            treatment = treatments_by_disease[analysis.disease_id]
            summary = treatment.natural_treatments or treatment.recommended_products or "Traitement ajoute par Agricheck."
            timeline_items.append(
                {
                    "date": treatment.updated_at,
                    "parcel": analysis.parcel.name,
                    "action": "Traitement recommande",
                    "summary": summary,
                    "kind": "treatment",
                }
            )
    for report in reports_qs:
        timeline_items.append(
            {
                "date": report.published_at or report.created_at,
                "parcel": report.parcel.name,
                "action": "Rapport Agricheck",
                "summary": report.summary or report.title,
                "kind": "report",
            }
        )
    for report in fetch_admin_reports(selected_company):
        published_at = parse_datetime(report.get("published_at") or "") or timezone.now()
        timeline_items.append(
            {
                "date": published_at,
                "parcel": report.get("parcel") or "Parcelle Agricheck",
                "action": "Rapport Agricheck",
                "summary": report.get("ai_result") or report.get("title") or "Rapport publie par Agricheck.",
                "kind": "report",
            }
        )

    timeline_items.sort(key=lambda item: item["date"], reverse=True)

    context.update({"timeline_items": timeline_items})
    return render(request, "entreprise/history.html", context)


@login_required
def notifications(request):
    context = common_context(request, "notifications")
    selected_company = context["selected_company"]
    notifications_qs = scoped(Notification.objects.select_related("company", "user"), request, selected_company)
    notifications_qs = notifications_qs.filter(
        type__in=[
            Notification.Type.ANALYSIS_AVAILABLE,
            Notification.Type.REPORT_PUBLISHED,
            Notification.Type.URGENT_TREATMENT,
            Notification.Type.TREATMENT_RECOMMENDED,
        ]
    )
    context.update({"notifications": notifications_qs})
    return render(request, "entreprise/notifications.html", context)


@login_required
def billing(request):
    context = common_context(request, "billing")
    selected_company = context["selected_company"]
    invoices_qs = scoped(Invoice.objects.select_related("company"), request, selected_company)
    subscription_company = request.user.company
    admin_invoices = fetch_admin_invoices(selected_company)
    context.update(
        {
            "invoices": invoices_qs,
            "admin_invoices": admin_invoices,
            "invoice_count": invoices_qs.count() + len(admin_invoices),
            "subscription_company": subscription_company,
        }
    )
    return render(request, "entreprise/billing.html", context)


@login_required
def profile(request):
    context = common_context(request, "profile")
    selected_company = context["selected_company"]
    company = request.user.company
    context.update({"company": company})
    return render(request, "entreprise/profile.html", context)
