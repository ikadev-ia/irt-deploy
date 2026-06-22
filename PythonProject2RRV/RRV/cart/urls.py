from django.urls import path
from . import views

app_name = 'cart'

urlpatterns = [
    # Page du panier
    path('', views.cart_detail, name='cart_detail'),

    # Ajout d'un article (via AJAX)
    path('add/<int:product_id>/<str:product_type>/', views.add_to_cart, name='add_to_cart'),

    # Suppression d'un article
    path('remove/<int:item_id>/', views.remove_from_cart, name='remove_from_cart'),

    # Validation de la sélection pour le paiement
    path('checkout/', views.checkout_selected, name='checkout_selected'),
]