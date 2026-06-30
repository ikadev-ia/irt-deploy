from django.db import models
from django.conf import settings
from django.utils import timezone
from commandes.models import Commande


class Livraison(models.Model):
    STATUT_CHOICES = [
        ('assignee', 'Assignée au livreur'),
        ('en_route', 'En route'),
        ('arrive', 'Arrivé'),
        ('livree', 'Livrée'),
    ]

    commande = models.OneToOneField(
        Commande, on_delete=models.CASCADE, related_name='livraison'
    )
    livreur = models.ForeignKey(
        settings.AUTH_USER_MODEL,
        on_delete=models.SET_NULL,
        null=True, blank=True,
        related_name='livraisons',
        limit_choices_to={'role': 'livreur'}
    )
    statut = models.CharField(max_length=20, choices=STATUT_CHOICES, default='assignee')
    heure_depart = models.DateTimeField(null=True, blank=True)
    heure_arrivee = models.DateTimeField(null=True, blank=True)
    notes_livreur = models.TextField(blank=True)

    # Position GPS du livreur (mise à jour en temps réel)
    latitude = models.FloatField(null=True, blank=True)
    longitude = models.FloatField(null=True, blank=True)
    derniere_maj_position = models.DateTimeField(null=True, blank=True)

    class Meta:
        verbose_name = 'Livraison'
        verbose_name_plural = 'Livraisons'

    def __str__(self):
        return f"Livraison commande #{self.commande.numero}"

    def mettre_en_route(self):
        self.statut = 'en_route'
        self.heure_depart = timezone.now()
        self.save()

    def marquer_livree(self):
        self.statut = 'livree'
        self.heure_arrivee = timezone.now()
        self.save()
        self.commande.statut = 'livree'
        self.commande.save()
