from django.contrib import admin

from .models import (
    AiAnalysis,
    CompanyClient,
    Disease,
    Drone,
    Employee,
    FieldMission,
    ImportantAlert,
    Invoice,
    MobileNotification,
    MobileUser,
    Parcel,
    Report,
    Treatment,
)


@admin.register(MobileUser)
class MobileUserAdmin(admin.ModelAdmin):
    list_display = ("full_name", "phone", "email", "scan_count", "disease_count", "is_active", "created_at")
    list_filter = ("is_active", "created_at")
    search_fields = ("full_name", "phone", "email")


@admin.register(CompanyClient)
class CompanyClientAdmin(admin.ModelAdmin):
    list_display = ("name", "manager_name", "phone", "email", "hectares", "subscription", "status")
    list_filter = ("subscription", "status")
    search_fields = ("name", "manager_name", "phone", "email")


@admin.register(Parcel)
class ParcelAdmin(admin.ModelAdmin):
    list_display = ("name", "company", "surface_hectares", "crop", "general_state")
    list_filter = ("company", "crop", "general_state")
    search_fields = ("name", "company__name", "crop")


@admin.register(Drone)
class DroneAdmin(admin.ModelAdmin):
    list_display = ("name", "model", "serial_number", "status", "flight_count", "maintenance_date")
    list_filter = ("status", "maintenance_date")
    search_fields = ("name", "model", "serial_number")


@admin.register(Employee)
class EmployeeAdmin(admin.ModelAdmin):
    list_display = ("full_name", "role", "phone", "email", "status")
    list_filter = ("role", "status")
    search_fields = ("full_name", "phone", "email")


@admin.register(FieldMission)
class FieldMissionAdmin(admin.ModelAdmin):
    list_display = ("company", "parcel", "drone", "pilot", "mission_date", "status")
    list_filter = ("status", "company", "drone", "pilot")
    search_fields = ("company__name", "parcel__name", "drone__name", "pilot__full_name")


@admin.register(Disease)
class DiseaseAdmin(admin.ModelAdmin):
    list_display = ("name", "crop", "risk_level")
    list_filter = ("risk_level", "crop")
    search_fields = ("name", "crop", "symptoms")


@admin.register(AiAnalysis)
class AiAnalysisAdmin(admin.ModelAdmin):
    list_display = (
        "source",
        "client_label",
        "detected_plant",
        "disease_label",
        "confidence",
        "risk_level",
        "provider",
        "analyzed_at",
    )
    list_filter = ("source", "risk_level", "provider", "company")
    search_fields = (
        "mobile_user__full_name",
        "company__name",
        "detected_plant",
        "detected_disease",
        "disease__name",
    )


@admin.register(Treatment)
class TreatmentAdmin(admin.ModelAdmin):
    list_display = ("disease", "dosage", "frequency", "updated_at")
    search_fields = ("disease__name", "product_recommended", "prevention")


@admin.register(Report)
class ReportAdmin(admin.ModelAdmin):
    list_display = ("title", "company", "parcel", "status", "published_at", "created_at")
    list_filter = ("status", "company")
    search_fields = ("title", "company__name", "parcel__name", "ai_result")


@admin.register(Invoice)
class InvoiceAdmin(admin.ModelAdmin):
    list_display = ("number", "company", "amount_total", "amount_paid", "amount_unpaid", "status", "issued_at")
    list_filter = ("status", "issued_at", "company")
    search_fields = ("number", "company__name", "subscription")


@admin.register(ImportantAlert)
class ImportantAlertAdmin(admin.ModelAdmin):
    list_display = ("title", "priority", "is_resolved", "created_at")
    list_filter = ("priority", "is_resolved")
    search_fields = ("title", "message")


@admin.register(MobileNotification)
class MobileNotificationAdmin(admin.ModelAdmin):
    list_display = ("title", "mobile_user", "type", "is_read", "created_at")
    list_filter = ("type", "is_read", "created_at")
    search_fields = ("title", "message", "mobile_user__full_name")
