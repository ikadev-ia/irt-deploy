from django.contrib import admin
from .models import Categorie, Medicament, Panier, Pharmacie

@admin.register(Categorie)
class CategorieAdmin(admin.ModelAdmin):
    list_display = ['nom', 'slug']
    prepopulated_fields = {'slug': ('nom',)}

@admin.register(Medicament)
class MedicamentAdmin(admin.ModelAdmin):
    list_display = ['nom', 'categorie', 'prix', 'stock', 'sur_ordonnance', 'est_populaire', 'est_disponible']
    list_filter = ['categorie', 'sur_ordonnance', 'est_populaire', 'est_disponible']
    search_fields = ['nom', 'description', 'composition']
    prepopulated_fields = {'slug': ('nom',)}
    list_editable = ['prix', 'stock', 'est_populaire', 'est_disponible']
    fields = ['nom', 'slug', 'categorie', 'description', 'composition', 'posologie',
              'prix', 'stock', 'image', 'image_url', 'sur_ordonnance', 'est_populaire', 'est_disponible']

@admin.register(Panier)
class PanierAdmin(admin.ModelAdmin):
    list_display = ['session_key', 'medicament', 'quantite', 'date_ajout']

@admin.register(Pharmacie)
class PharmacieAdmin(admin.ModelAdmin):
    list_display = ['nom', 'quartier', 'adresse', 'telephone', 'est_active']
    list_filter = ['est_active', 'quartier']
    search_fields = ['nom', 'quartier', 'adresse']
    list_editable = ['est_active']
    fields = ['nom', 'adresse', 'quartier', 'telephone', 'email', 'horaires', 'image', 'image_url', 'est_active']
