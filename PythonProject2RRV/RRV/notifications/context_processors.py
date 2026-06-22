from .models import Notification

def unread_notifications_count(request):
    if request.user.is_authenticated:
        # On compte uniquement les notifications qui ne sont pas encore marquées comme lues
        count = Notification.objects.filter(recipient=request.user, is_read=False).count()
        return {'unread_count': count}
    return {'unread_count': 0}