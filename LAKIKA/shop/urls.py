from django.urls import path
from . import views

urlpatterns = [
    path('', views.index, name='index'),
    path('panier/', views.panier, name='panier'),
    path('inscription/', views.inscription, name='inscription'),
    path('login/', views.connexion, name='login'),
    path('logout/', views.deconnexion, name='logout'),
    path('paiement/', views.paiement,name='paiement'),
    path('apropos/', views.apropos, name='apropos'),
    path('contact/', views.contact, name='contact'),
    path('aide/', views.aide, name='aide'),
    path('cookies/',views.cookies,name='cookies'),

path('confidentialite/', views.confidentialite, name='confidentialite'),
path('parametres/', views.parametres, name='parametres'),
]