from django.db import models


class ShopProduct(models.Model):
    # Définition des options pour l'état de l'article
    CONDITION_CHOICES = [
        ('neuf', 'Neuf'),
        ('excellent', 'Excellent état'),
        ('bon', 'Bon état'),
    ]

    name = models.CharField(max_length=200)
    description = models.TextField()
    price = models.DecimalField(max_digits=10, decimal_places=0)  # Adapté pour les FCFA
    image = models.ImageField(upload_to='shop_products/')

    # Nouveau champ pour qualifier l'état de l'article
    condition = models.CharField(
        max_length=20,
        choices=CONDITION_CHOICES,
        default='excellent'
    )

    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return self.name