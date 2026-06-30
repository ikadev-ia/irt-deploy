from django.urls import path
from . import views

urlpatterns = [
    path('', views.accueil, name='accueil'),
    path('medicaments/', views.liste_medicaments, name='medicaments'),
    path('medicaments/<slug:slug>/', views.detail_medicament, name='detail_medicament'),
    path('panier/', views.voir_panier, name='panier'),
    path('panier/ajouter/<int:medicament_id>/', views.ajouter_panier, name='ajouter_panier'),
    path('panier/modifier/<int:item_id>/', views.modifier_panier, name='modifier_panier'),
    path('pharmacies/', views.liste_pharmacies, name='pharmacies'),
    path('amo/', views.amo_view, name='amo'),
]
