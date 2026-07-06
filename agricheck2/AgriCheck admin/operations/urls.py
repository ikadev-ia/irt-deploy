from django.urls import path

from . import views


app_name = "operations"

urlpatterns = [
    path("", views.DashboardView.as_view(), name="dashboard"),
    path("module/<slug:slug>/", views.EntityListView.as_view(), name="entity_list"),
    path("module/<slug:slug>/ajouter/", views.EntityFormView.as_view(), name="entity_create"),
    path("module/<slug:slug>/<int:pk>/modifier/", views.EntityUpdateView.as_view(), name="entity_update"),
    path("module/analyses/<int:pk>/analyser/", views.run_drone_analysis_view, name="run_drone_analysis"),
    path("api/app/auth/register/", views.api_mobile_register, name="api_mobile_register"),
    path("api/app/auth/login/", views.api_mobile_login, name="api_mobile_login"),
    path("api/app/auth/password-reset/", views.api_mobile_password_reset, name="api_mobile_password_reset"),
    path("api/app/advice/", views.api_mobile_advice, name="api_mobile_advice"),
    path("api/app/notifications/", views.api_mobile_notifications, name="api_mobile_notifications"),
    path("api/app/analyses/", views.api_mobile_analysis_sync, name="api_mobile_analysis_sync"),
    path("api/app/diagnostics/", views.api_mobile_diagnostics, name="api_mobile_diagnostics"),
    path("api/drone/captures/", views.api_drone_capture, name="api_drone_capture"),
    path("api/client/companies/register/", views.api_client_company_register, name="api_client_company_register"),
    path("api/client/reports/", views.api_client_published_reports, name="api_client_published_reports"),
]
