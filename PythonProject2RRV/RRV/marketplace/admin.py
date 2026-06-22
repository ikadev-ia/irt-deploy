from django.contrib import admin
from .models import Product, Category, Favorite, Comment

# Enregistrement des modèles pour l'interface d'administration Django
admin.site.register(Category)
admin.site.register(Product)
admin.site.register(Favorite)
admin.site.register(Comment)

# Le modèle 'Like' a été supprimé car nous utilisons maintenant
# un ManyToManyField directement dans le modèle Product.
# admin.site.register(Like)