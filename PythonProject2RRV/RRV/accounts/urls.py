from django.urls import path
from django.contrib.auth import views as auth_views
from . import views # Import indispensable pour accéder à profile_view et edit_profile
from .views import register_view

urlpatterns = [
    # Inscription
    path(
        'register/',
        register_view,
        name='register'
    ),

    # Connexion
    path(
        'login/',
        auth_views.LoginView.as_view(
            template_name='accounts/login.html'
        ),
        name='login'
    ),

    # Déconnexion
    path(
        'logout/',
        auth_views.LogoutView.as_view(),
        name='logout'
    ),

    # --- AJOUT : Route pour le Profil ---
    # Accessible via /profile/ grâce à l'inclusion dans config/urls.py
    path('', views.profile_view, name='profile'),

    # --- AJOUT : Route pour la Modification du Profil ---
    # Accessible via /profile/edit/ ou /accounts/edit/ selon ta config
    path('edit/', views.edit_profile, name='edit_profile'),
]