from django.db import models
from django.conf import settings

class Notification(models.Model):
    TYPES = (
        ('message', 'Nouveau Message'),
        ('achat', 'Vente effectuée'),
        ('comment', 'Nouveau Commentaire'), # Corrigé pour correspondre à tes views
        ('like', 'Nouveau Like'),
    )

    # L'utilisateur qui reçoit l'alerte
    recipient = models.ForeignKey(
        settings.AUTH_USER_MODEL,
        on_delete=models.CASCADE,
        related_name='notifications'
    )

    # Le type d'action
    notification_type = models.CharField(max_length=20, choices=TYPES)

    # Le texte descriptif
    text = models.TextField()

    # Statut de lecture
    is_read = models.BooleanField(default=False)

    # Date de création
    created_at = models.DateTimeField(auto_now_add=True)

    # CORRECTION : Changement de IntegerField à CharField pour accepter les Slugs
    link_id = models.CharField(max_length=255, null=True, blank=True)

    def __str__(self):
        return f"{self.get_notification_type_display()} pour {self.recipient.username}"

    class Meta:
        ordering = ['-created_at']