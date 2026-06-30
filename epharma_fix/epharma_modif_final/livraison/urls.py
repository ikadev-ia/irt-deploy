from django.urls import path
from . import views

urlpatterns = [
    # Client
    path('suivi/<int:pk>/', views.suivi, name='suivi_livraison'),
    path('api/position/<int:pk>/', views.api_position_livreur, name='api_position_livreur'),

    # Livreur
    path('livreur/', views.tableau_livreur, name='tableau_livreur'),
    path('livreur/statut/<int:pk>/', views.changer_statut, name='changer_statut'),
    path('livreur/position/<int:pk>/', views.api_maj_position, name='api_maj_position'),
]
