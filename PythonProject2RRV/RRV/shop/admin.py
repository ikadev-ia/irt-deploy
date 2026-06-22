from django.contrib import admin
from .models import ShopProduct

@admin.register(ShopProduct)
class ShopProductAdmin(admin.ModelAdmin):
    list_display = ('name', 'price', 'created_at') # Colonnes visibles dans l'admin
    search_fields = ('name',) # Barre de recherche pour tes articles