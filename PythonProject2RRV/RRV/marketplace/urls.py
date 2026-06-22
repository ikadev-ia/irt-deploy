from django.urls import path
from . import views

app_name = 'marketplace'

urlpatterns = [
    # Accueil
    path('', views.home, name='home'),

    # Actions sur les produits
    path('produit/ajouter/', views.create_product, name='create_product'),
    path('produit/modifier/<slug:slug>/', views.edit_product, name='edit_product'),

    # Interactions (Placées ici pour être traitées avant les slugs si besoin)
    path('product/<int:product_id>/like/', views.toggle_like, name='toggle_like'),
    path('product/<int:product_id>/comment/', views.add_comment, name='add_comment'),

    # Détail du produit
    path('produit/<slug:slug>/', views.product_detail, name='product_detail'),

    # Navigation et Filtres
    path('category/<slug:slug>/', views.category_products, name='category_products'),
    path('search/', views.search_products, name='search_products'),

    # Dashboard et Succès
    path('dashboard/', views.dashboard, name='dashboard'),
    path('payment-success/', views.payment_success, name='payment_success'),
]