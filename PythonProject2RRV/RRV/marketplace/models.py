import uuid
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


# 2. MODÈLE PRODUIT (Marketplace)
class Product(models.Model):
    CONDITION_CHOICES = [
        ('neuf', 'Neuf'),
        ('excellent', 'Excellent'),
        ('bon', 'Bon'),
    ]

    TYPE_CHOICES = [
        ('MARKETPLACE', 'Marketplace'),
        ('BOUTIQUE', 'Boutique'),
    ]

    product_type = models.CharField(
        max_length=20,
        choices=TYPE_CHOICES,
        default='MARKETPLACE'
    )

    # Modification du related_name pour éviter le conflit avec le modèle central 'products'
    seller = models.ForeignKey(
        settings.AUTH_USER_MODEL,
        on_delete=models.CASCADE,
        related_name='marketplace_items'
    )

    title = models.CharField(max_length=255)
    slug = models.SlugField(unique=True, null=True, blank=True)
    description = models.TextField()
    price = models.DecimalField(max_digits=10, decimal_places=2)

    category = models.ForeignKey(
        Category,
        on_delete=models.SET_NULL,
        null=True,
        related_name='products'
    )

    condition = models.CharField(max_length=50, choices=CONDITION_CHOICES)
    image = models.ImageField(upload_to='products/')
    created_at = models.DateTimeField(auto_now_add=True)

    # Champ pour gérer les likes dynamiquement
    likes = models.ManyToManyField(
        settings.AUTH_USER_MODEL,
        related_name='liked_products',
        blank=True
    )

    def total_likes(self):
        """Retourne le nombre total de likes"""
        return self.likes.count()

    def total_comments(self):
        """Retourne le nombre total de commentaires"""
        return self.comments.count()

    def save(self, *args, **kwargs):
        if not self.slug:
            base_slug = slugify(self.title)
            if Product.objects.filter(slug=base_slug).exists():
                self.slug = f"{base_slug}-{str(uuid.uuid4())[:8]}"
            else:
                self.slug = base_slug
        super().save(*args, **kwargs)

    def __str__(self):
        return self.title


# 3. INTERACTIONS
class Favorite(models.Model):
    user = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE)
    product = models.ForeignKey(Product, on_delete=models.CASCADE)
    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return f"{self.user.username} ❤️ {self.product.title}"


# Modèle Commentaire
class Comment(models.Model):
    product = models.ForeignKey(
        Product,
        on_delete=models.CASCADE,
        related_name='comments'
    )
    user = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE)
    content = models.TextField()
    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return f"Commentaire de {self.user.username} sur {self.product.title}"