from django.contrib import admin
from .models import Livraison

@admin.register(Livraison)
class LivraisonAdmin(admin.ModelAdmin):
    list_display = ['commande', 'livreur', 'statut', 'heure_depart', 'heure_arrivee', 'latitude', 'longitude']
    list_filter = ['statut']
    list_editable = ['livreur', 'statut']
    search_fields = ['commande__numero', 'livreur__username']
    readonly_fields = ['derniere_maj_position']

    def get_queryset(self, request):
        return super().get_queryset(request).select_related('commande', 'livreur')
