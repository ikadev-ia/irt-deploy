from django.contrib.auth.models import AbstractUser
from django.db import models
from django.utils import timezone
from datetime import timedelta

class User(AbstractUser):
    """
    Modèle utilisateur personnalisé pour Revina.
    Centralise les informations d'authentification, de profil et d'activité.
    """

    # Photo de profil
    photo = models.ImageField(
        upload_to='profiles/',
        blank=True,
        null=True,
        default='profiles/default.jpg'
    )

    # Informations de contact et localisation
    phone = models.CharField(
        max_length=20,
        blank=True,
        null=True,
        verbose_name="Numéro de téléphone"
    )

    city = models.CharField(
        max_length=100,
        default='Bamako',
        verbose_name="Ville"
    )

    # Description personnelle
    bio = models.TextField(
        max_length=500,
        blank=True,
        verbose_name="Biographie"
    )

    # --- NOUVEAU : Suivi de l'état en ligne ---
    last_activity = models.DateTimeField(default=timezone.now)

    def is_online(self):
        """
        Vérifie si l'utilisateur a été actif au cours des 5 dernières minutes.
        """
        return self.last_activity > timezone.now() - timedelta(minutes=5)

    def __str__(self):
        return self.username