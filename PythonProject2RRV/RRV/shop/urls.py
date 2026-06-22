from django.urls import path
from . import views

# Le namespace 'shop' permet de bien organiser les liens
app_name = 'shop'

urlpatterns = [
    # Page principale de la boutique
    path('', views.boutique_view, name='index'),

    # Page de détails d'un article spécifique (utilise la clé primaire 'pk')
    path('article/<int:pk>/', views.article_detail, name='article_detail'),
]