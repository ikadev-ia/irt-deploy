from django.db import models
from django.conf import settings
from django.contrib.contenttypes.fields import GenericForeignKey
from django.contrib.contenttypes.models import ContentType


class Cart(models.Model):
    user = models.OneToOneField(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name='cart')
    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return f"Panier de {self.user.username}"

    @property
    def total_price(self):
        return sum(item.total_item_price for item in self.items.all())


class CartItem(models.Model):
    cart = models.ForeignKey(Cart, on_delete=models.CASCADE, related_name='items')

    # Configuration pour accepter des produits de n'importe quel modèle
    content_type = models.ForeignKey(ContentType, on_delete=models.CASCADE)
    object_id = models.PositiveIntegerField()

    # Correction : Utilisation du nom standard 'content_object'
    content_object = GenericForeignKey('content_type', 'object_id')

    quantity = models.PositiveIntegerField(default=1)

    def __str__(self):
        # Utilisation de content_object pour accéder au produit
        prod = self.content_object
        if prod:
            return f"{self.quantity} x {getattr(prod, 'title', getattr(prod, 'name', 'Produit inconnu'))}"
        return f"{self.quantity} x Produit supprimé"

    @property
    def total_item_price(self):
        # Utilisation de content_object au lieu de product
        return self.content_object.price * self.quantity if self.content_object else 0