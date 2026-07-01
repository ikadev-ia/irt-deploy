from django.contrib import admin
from django.utils.html import format_html
from .models import Commande, Produit

@admin.register(Commande)
class CommandeAdmin(admin.ModelAdmin):
    list_display = ("client", "telephone", "methode_paiement", "adresse_livraison", "voir_sur_maps", "date")
    list_filter = ("methode_paiement", "date")
    search_fields = ("client__username", "telephone", "adresse_livraison")
    readonly_fields = ("carte_livraison", "date")

    fields = (
        "client",
        "telephone",
        "methode_paiement",
        "adresse_livraison",
        "latitude",
        "longitude",
        "carte_livraison",
        "date",
    )

    # Lien cliquable dans la liste des commandes
    def voir_sur_maps(self, obj):
        if obj.latitude and obj.longitude:
            url = f"https://www.google.com/maps?q={obj.latitude},{obj.longitude}"
            return format_html('<a href="{}" target="_blank">📍 Voir sur Maps</a>', url)
        return "—"
    voir_sur_maps.short_description = "Position"

    # Mini-carte intégrée (iframe) visible dans la page de modification de la commande
    def carte_livraison(self, obj):
        if obj.latitude and obj.longitude:
            return format_html(
                '<iframe width="100%" height="300" style="border:0" '
                'src="https://maps.google.com/maps?q={},{}&z=15&output=embed">'
                '</iframe>',
                obj.latitude, obj.longitude
            )
        return "Aucune position enregistrée"
    carte_livraison.short_description = "Carte de livraison"

admin.site.register(Produit)