from django.db import models
from django.conf import settings # Import pour utiliser l'User personnalisé

class Review(models.Model):
    # Remplacement de User par settings.AUTH_USER_MODEL pour le vendeur
    seller = models.ForeignKey(
        settings.AUTH_USER_MODEL,
        on_delete=models.CASCADE,
        related_name='seller_reviews'
    )

    # Remplacement de User par settings.AUTH_USER_MODEL pour celui qui donne l'avis
    reviewer = models.ForeignKey(
        settings.AUTH_USER_MODEL,
        on_delete=models.CASCADE,
        related_name='given_reviews' # Ajouté pour éviter les conflits et mieux s'y retrouver
    )

    rating = models.IntegerField(
        default=5
    )

    comment = models.TextField()

    created_at = models.DateTimeField(
        auto_now_add=True
    )

    def __str__(self):
        return f"{self.reviewer.username} -> {self.seller.username} ({self.rating}/5)"