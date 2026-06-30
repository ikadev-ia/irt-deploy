from django.contrib import admin
from .models import Commande, LigneCommande

class LigneCommandeInline(admin.TabularInline):
    model = LigneCommande
    extra = 0
    readonly_fields = ['sous_total']

@admin.register(Commande)
class CommandeAdmin(admin.ModelAdmin):
    list_display = ['numero', 'client', 'statut', 'total', 'mode_paiement', 'date_creation']
    list_filter = ['statut', 'mode_paiement']
    search_fields = ['numero', 'client__username', 'client__first_name']
    list_editable = ['statut']
    inlines = [LigneCommandeInline]
    readonly_fields = ['numero', 'date_creation', 'date_modification']
