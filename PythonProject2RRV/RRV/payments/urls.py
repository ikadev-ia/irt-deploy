# payments/urls.py
from django.urls import path
from . import views

app_name = 'payments'

urlpatterns = [
    # Route pour initialiser le paiement d'un seul article (ex: bouton 'Acheter maintenant')
    path('checkout/<int:item_id>/', views.checkout, name='checkout'),

    # Route pour valider tout le panier
    path('checkout/', views.checkout_cart, name='checkout_cart'),

    # Route pour confirmer le paiement (utilisée par le formulaire de paiement)
    path('confirm/', views.confirmer_paiement, name='confirmer_paiement'),
]