from django.urls import path

from . import views


app_name = "entreprise"

urlpatterns = [
    path("", views.home, name="home"),
    path("a-propos-agricheck/", views.about, name="about"),
    path("dashboard/", views.dashboard, name="dashboard"),
    path("inscription/", views.register, name="register"),
    path("parcelles/", views.parcels, name="parcels"),
    path("parcelles/<int:parcel_id>/", views.parcel_detail, name="parcel_detail"),
    path("rapports/", views.reports, name="reports"),
    path("rapports-admin/<int:report_id>/", views.admin_report_detail, name="admin_report_detail"),
    path("rapports/<int:report_id>/", views.report_detail, name="report_detail"),
    path("traitements-recommandes/", views.treatments, name="treatments"),
    path("historique/", views.history, name="history"),
    path("notifications/", views.notifications, name="notifications"),
    path("facturation/", views.billing, name="billing"),
    path("profil-client/", views.profile, name="profile"),
]
