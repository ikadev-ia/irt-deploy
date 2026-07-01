from django.db import models
from django.contrib.auth.models import User

class Commande(models.Model):
    client = models.ForeignKey(User, on_delete=models.CASCADE)
    telephone = models.CharField(max_length=20)
    methode_paiement = models.CharField(max_length=50)
    adresse_livraison = models.CharField(max_length=255, blank=True, null=True)
    latitude = models.CharField(max_length=50, blank=True, null=True)
    longitude = models.CharField(max_length=50, blank=True, null=True)
    date = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return self.client.username

class Produit(models.Model):
    nom = models.CharField(max_length=100)
    prix = models.IntegerField()
    description = models.TextField()
    image = models.ImageField(upload_to='produits/', null=True, blank=True)

    def __str__(self):
        return self.nom