from django.urls import path
from . import views

urlpatterns = [
    path('', views.mes_commandes, name='mes_commandes'),
    path('checkout/', views.checkout, name='checkout'),
    path('<int:pk>/', views.detail_commande, name='detail_commande'),
    path('<int:pk>/annuler/', views.annuler_commande, name='annuler_commande'),
    path('<int:pk>/recu/', views.recu_web, name='recu_web'),
    path('<int:pk>/recu/pdf/', views.recu_pdf, name='recu_pdf'),
]
