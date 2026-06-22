from django.db import models
from django.conf import settings
from django.utils.text import slugify


# 1. MODÈLE CATÉGORIE
class Category(models.Model):
    name = models.CharField(max_length=100)
    slug = models.SlugField(unique=True, null=True, blank=True)

    def save(self, *args, **kwargs):
        if not self.slug:
            self.slug = slugify(self.name)
        super().save(*args, **kwargs)

    class Meta:
        verbose_name_plural = "Categories"

    def __str__(self):
        return self.name


# 2. MODÈLE PRODUIT UNIFIÉ
class Product(models.Model):
    # Informations de base
    name = models.CharField(max_length=200, verbose_name="Nom du produit")

    # Ajout de null=True pour permettre la migration sur des données existantes
    slug = models.SlugField(unique=True, null=True, blank=True, verbose_name="Slug")

    description = models.TextField(verbose_name="Description")
    price = models.DecimalField(max_digits=10, decimal_places=0, verbose_name="Prix (FCFA)")
    image = models.ImageField(upload_to='products/', verbose_name="Image")

    # Lien vers la catégorie
    category = models.ForeignKey(
        Category,
        on_delete=models.SET_NULL,
        null=True,
        blank=True,
        related_name='products'
    )

    # Pour lier à un utilisateur (Marketplace)
    owner = models.ForeignKey(
        settings.AUTH_USER_MODEL,
        on_delete=models.CASCADE,
        related_name='my_products',
        null=True,
        blank=True
    )

    # Pour distinguer Boutique vs Marketplace
    TYPE_CHOICES = [
        ('BOUTIQUE', 'Article Boutique'),
        ('MARKETPLACE', 'Annonce Marketplace'),
    ]
    product_type = models.CharField(max_length=20, choices=TYPE_CHOICES, default='BOUTIQUE')

    created_at = models.DateTimeField(auto_now_add=True)

    def save(self, *args, **kwargs):
        """Génère automatiquement le slug si manquant."""
        if not self.slug:
            base_slug = slugify(self.name)
            self.slug = base_slug
            counter = 1
            # Vérifie les collisions même lors de la création
            while Product.objects.filter(slug=self.slug).exists():
                self.slug = f"{base_slug}-{counter}"
                counter += 1
        super().save(*args, **kwargs)

    class Meta:
        ordering = ['-created_at']

    def __str__(self):
        return f"{self.name} - {self.price} FCFA"