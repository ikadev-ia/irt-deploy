from django.db import models
from django.conf import settings

class Conversation(models.Model):
    """
    Regroupe les échanges entre deux utilisateurs.
    """
    participants = models.ManyToManyField(
        settings.AUTH_USER_MODEL,
        related_name='user_conversations' # Unique pour éviter les conflits
    )
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True) # Utile pour trier par activité

    def __str__(self):
        # Affiche les participants dans l'interface admin (ex: "Discussion entre : Malick, Moussa")
        usernames = ", ".join([u.username for u in self.participants.all()])
        return f"Discussion entre : {usernames}" if usernames else f"Conversation {self.id}"

class Message(models.Model):
    """
    Chaque message individuel envoyé au sein d'une conversation.
    """
    conversation = models.ForeignKey(
        Conversation,
        on_delete=models.CASCADE,
        related_name='messages'
    )
    sender = models.ForeignKey(
        settings.AUTH_USER_MODEL,
        on_delete=models.CASCADE,
        related_name='sent_messages'
    )
    content = models.TextField()
    timestamp = models.DateTimeField(auto_now_add=True)
    is_read = models.BooleanField(default=False)

    class Meta:
        ordering = ['timestamp'] # Affiche les messages dans l'ordre chronologique

    def __str__(self):
        return f"De {self.sender.username} le {self.timestamp.strftime('%d/%m à %H:%M')}"