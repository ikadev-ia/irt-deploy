from django.contrib import admin
from django.contrib.auth.admin import UserAdmin as DjangoUserAdmin

from .models import (
    Analysis,
    Company,
    Disease,
    Drone,
    DroneImage,
    DroneMission,
    Invoice,
    Notification,
    Parcel,
    Report,
    Treatment,
    User,
)


@admin.register(User)
class UserAdmin(DjangoUserAdmin):
    list_display = ("username", "email", "role", "company", "is_staff", "is_active")
    list_filter = ("role", "is_staff", "is_active", "company")
    search_fields = ("username", "email", "company__name")
    fieldsets = DjangoUserAdmin.fieldsets + (
        ("Agricheck Client", {"fields": ("role", "company", "phone")}),
    )
    add_fieldsets = DjangoUserAdmin.add_fieldsets + (
        ("Agricheck Client", {"fields": ("email", "role", "company", "phone")}),
    )


@admin.register(Company)
class CompanyAdmin(admin.ModelAdmin):
    list_display = (
        "name",
        "manager_name",
        "email",
        "phone",
        "hectares",
        "subscription_type",
        "subscription_price_xof",
        "travel_included",
        "travel_limit_km",
        "payment_method",
        "registration_status",
    )
    list_filter = ("subscription_type", "travel_included", "payment_method", "registration_status")
    search_fields = ("name", "manager_name", "email")


@admin.register(Parcel)
class ParcelAdmin(admin.ModelAdmin):
    list_display = ("name", "company", "surface_hectares", "crop", "general_state", "gps_label")
    list_filter = ("company", "crop", "general_state")
    search_fields = ("name", "company__name", "crop")


@admin.register(Drone)
class DroneAdmin(admin.ModelAdmin):
    list_display = ("name", "model", "serial_number", "status")
    list_filter = ("status",)
    search_fields = ("name", "model", "serial_number")


class DroneImageInline(admin.TabularInline):
    model = DroneImage
    extra = 0


@admin.register(DroneMission)
class DroneMissionAdmin(admin.ModelAdmin):
    list_display = ("parcel", "company", "drone", "status", "mission_date")
    list_filter = ("status", "company", "drone")
    search_fields = ("parcel__name", "company__name", "drone__name")
    inlines = [DroneImageInline]


@admin.register(DroneImage)
class DroneImageAdmin(admin.ModelAdmin):
    list_display = ("mission", "caption", "captured_at", "gps_latitude", "gps_longitude")
    list_filter = ("mission__company", "captured_at")
    search_fields = ("caption", "mission__parcel__name")


@admin.register(Disease)
class DiseaseAdmin(admin.ModelAdmin):
    list_display = ("name", "crop", "risk_level")
    list_filter = ("risk_level", "crop")
    search_fields = ("name", "crop")


@admin.register(Analysis)
class AnalysisAdmin(admin.ModelAdmin):
    list_display = ("parcel", "company", "disease", "confidence", "risk_level", "ai_provider", "analyzed_at")
    list_filter = ("risk_level", "ai_provider", "company", "disease")
    search_fields = ("parcel__name", "company__name", "disease__name", "detected_crop")


@admin.register(Treatment)
class TreatmentAdmin(admin.ModelAdmin):
    list_display = ("disease", "frequency", "updated_at")
    search_fields = ("disease__name", "recommended_products")


@admin.register(Report)
class ReportAdmin(admin.ModelAdmin):
    list_display = ("title", "company", "parcel", "mission", "is_published", "published_at")
    list_filter = ("is_published", "company", "parcel")
    search_fields = ("title", "company__name", "parcel__name")


@admin.register(Invoice)
class InvoiceAdmin(admin.ModelAdmin):
    list_display = ("number", "company", "amount", "currency", "status", "issued_at", "due_date")
    list_filter = ("status", "company", "issued_at")
    search_fields = ("number", "company__name")


@admin.register(Notification)
class NotificationAdmin(admin.ModelAdmin):
    list_display = ("title", "company", "type", "priority", "is_read", "created_at")
    list_filter = ("type", "priority", "is_read", "company")
    search_fields = ("title", "company__name", "message")
