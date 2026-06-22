from django.shortcuts import render, get_object_or_404, redirect
from django.contrib.auth.decorators import login_required
from .models import Notification


@login_required
def notifications_list(request):
    """
    Affiche la liste des notifications de l'utilisateur connecté
    et les marque automatiquement comme lues.
    """
    # 1. On récupère les notifications via la related_name 'notifications' définie dans le modèle
    # Assurez-vous que dans votre modèle, 'related_name' pointe bien vers 'notifications'
    notifications = request.user.notifications.all().order_by('-created_at')

    # 2. On marque les notifications non lues comme lues
    notifications.filter(is_read=False).update(is_read=True)

    return render(request, 'notifications/notifications_list.html', {
        'notifications': notifications
    })


@login_required
def delete_notification(request, notification_id):
    """
    Supprime une notification spécifique appartenant à l'utilisateur connecté.
    """
    # On filtre avec 'recipient' au lieu de 'user' pour correspondre au modèle
    notification = get_object_or_404(Notification, id=notification_id, recipient=request.user)

    # Suppression de la notification
    notification.delete()

    # Redirection vers la liste des notifications
    return redirect('notifications:notifications_list')