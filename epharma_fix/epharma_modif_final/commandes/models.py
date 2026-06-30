from django.db import models
from django.conf import settings
from catalogue.models import Medicament

class Commande(models.Model):
    STATUT_CHOICES = [
        ('en_attente', 'En attente'),
        ('confirmee', 'Confirmée'),
        ('preparee', 'Préparée'),
        ('en_route', 'En route'),
        ('livree', 'Livrée'),
        ('annulee', 'Annulée'),
    ]
    PAIEMENT_CHOICES = [
        ('orange_money', 'Orange Money'),
        ('wave', 'Wave'),
        ('carte', 'Carte bancaire'),
        ('especes', 'Espèces à la livraison'),
    ]

    client = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name='commandes')
    numero = models.CharField(max_length=20, unique=True, blank=True)
    statut = models.CharField(max_length=20, choices=STATUT_CHOICES, default='en_attente')
    adresse_livraison = models.TextField()
    telephone = models.CharField(max_length=20)
    mode_paiement = models.CharField(max_length=20, choices=PAIEMENT_CHOICES, default='especes')
    notes = models.TextField(blank=True)
    total = models.DecimalField(max_digits=10, decimal_places=0, default=0)
    date_creation = models.DateTimeField(auto_now_add=True)
    date_modification = models.DateTimeField(auto_now=True)
    ordonnance = models.ImageField(upload_to='ordonnances/', blank=True, null=True)

    class Meta:
        verbose_name = 'Commande'
        verbose_name_plural = 'Commandes'
        ordering = ['-date_creation']

    def __str__(self):
        return f"Commande #{self.numero} - {self.client}"

    def save(self, *args, **kwargs):
        if not self.numero:
            import random
            self.numero = str(random.randint(1000, 9999))
        super().save(*args, **kwargs)

    @property
    def statut_badge(self):
        badges = {
            'en_attente': 'warning',
            'confirmee': 'info',
            'preparee': 'primary',
            'en_route': 'success',
            'livree': 'secondary',
            'annulee': 'danger',
        }
        return badges.get(self.statut, 'secondary')

    @property
    def progression(self):
        etapes = ['en_attente', 'confirmee', 'preparee', 'en_route', 'livree']
        if self.statut in etapes:
            return etapes.index(self.statut) + 1
        return 0

class LigneCommande(models.Model):
    commande = models.ForeignKey(Commande, on_delete=models.CASCADE, related_name='lignes')
    medicament = models.ForeignKey(Medicament, on_delete=models.CASCADE)
    quantite = models.PositiveIntegerField()
    prix_unitaire = models.DecimalField(max_digits=10, decimal_places=0)

    def __str__(self):
        return f"{self.medicament.nom} x{self.quantite}"

    @property
    def sous_total(self):
        return self.prix_unitaire * self.quantite
