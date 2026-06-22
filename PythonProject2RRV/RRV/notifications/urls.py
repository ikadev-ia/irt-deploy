from django.urls import path
from . import views

# Namespace pour l'application
app_name = 'notifications'

urlpatterns = [
    # URL de la liste : /notifications/
    path('', views.notifications_list, name='notifications_list'),

    # URL de suppression : /notifications/delete/<id>/
    path('delete/<int:notification_id>/', views.delete_notification, name='delete'),
]