from django.db import models
from django.utils.text import slugify

class Categorie(models.Model):
    nom = models.CharField(max_length=100)
    slug = models.SlugField(unique=True, blank=True)
    icone = models.CharField(max_length=50, default='fa-pills')
    description = models.TextField(blank=True)

    class Meta:
        verbose_name = 'Catégorie'
        verbose_name_plural = 'Catégories'
        ordering = ['nom']

    def __str__(self):
        return self.nom

    def save(self, *args, **kwargs):
        if not self.slug:
            self.slug = slugify(self.nom)
        super().save(*args, **kwargs)


class Medicament(models.Model):
    nom = models.CharField(max_length=200)
    slug = models.SlugField(unique=True, blank=True)
    categorie = models.ForeignKey(Categorie, on_delete=models.SET_NULL, null=True, related_name='medicaments')
    description = models.TextField()
    composition = models.TextField(blank=True, verbose_name='Composition / Principes actifs')
    posologie = models.TextField(blank=True)
    prix = models.DecimalField(max_digits=10, decimal_places=0)
    stock = models.PositiveIntegerField(default=0)
    image = models.ImageField(upload_to='medicaments/', blank=True, null=True)
    image_url = models.URLField(blank=True, null=True, verbose_name='URL image externe')
    sur_ordonnance = models.BooleanField(default=False)
    est_populaire = models.BooleanField(default=False)
    est_disponible = models.BooleanField(default=True)
    date_ajout = models.DateTimeField(auto_now_add=True)

    # --- AMO ---
    couvert_amo = models.BooleanField(
        default=False,
        verbose_name='Couvert par AMO'
    )
    taux_amo = models.PositiveIntegerField(
        default=0,
        verbose_name='Taux de prise en charge AMO (%)',
        help_text='Entrez un pourcentage entre 0 et 100'
    )

    class Meta:
        verbose_name = 'Médicament'
        verbose_name_plural = 'Médicaments'
        ordering = ['-est_populaire', 'nom']

    def __str__(self):
        return self.nom

    def save(self, *args, **kwargs):
        if not self.slug:
            self.slug = slugify(self.nom)
        super().save(*args, **kwargs)

    @property
    def en_stock(self):
        return self.stock > 0

    @property
    def get_image(self):
        if self.image:
            return self.image.url
        if self.image_url:
            return self.image_url
        return None

    @property
    def prix_apres_amo(self):
        """Prix après déduction de la prise en charge AMO."""
        if self.couvert_amo and self.taux_amo > 0:
            reduction = int(self.prix * self.taux_amo / 100)
            return int(self.prix) - reduction
        return int(self.prix)


class Pharmacie(models.Model):
    nom = models.CharField(max_length=200)
    adresse = models.CharField(max_length=300)
    quartier = models.CharField(max_length=100)
    telephone = models.CharField(max_length=20, blank=True)
    email = models.EmailField(blank=True)
    horaires = models.CharField(max_length=200, default='Lun-Sam : 8h-20h')
    image = models.ImageField(upload_to='pharmacies/', blank=True, null=True)
    image_url = models.URLField(blank=True, null=True, verbose_name='URL image externe')
    est_active = models.BooleanField(default=True)
    date_ajout = models.DateTimeField(auto_now_add=True)

    class Meta:
        verbose_name = 'Pharmacie'
        verbose_name_plural = 'Pharmacies'
        ordering = ['nom']

    def __str__(self):
        return f"{self.nom} — {self.quartier}"

    @property
    def get_image(self):
        if self.image:
            return self.image.url
        if self.image_url:
            return self.image_url
        return None


class Panier(models.Model):
    session_key = models.CharField(max_length=40)
    medicament = models.ForeignKey(Medicament, on_delete=models.CASCADE)
    quantite = models.PositiveIntegerField(default=1)
    date_ajout = models.DateTimeField(auto_now_add=True)

    class Meta:
        unique_together = ['session_key', 'medicament']

    def __str__(self):
        return f"Panier: {self.medicament.nom} x{self.quantite}"

    @property
    def sous_total(self):
        return self.medicament.prix * self.quantite


class CarteAMO(models.Model):
    """Carte Assurance Maladie Obligatoire du Mali."""
    utilisateur = models.OneToOneField(
        'accounts.Utilisateur',
        on_delete=models.CASCADE,
        related_name='carte_amo'
    )
    numero_carte = models.CharField(
        max_length=50,
        verbose_name='Numéro de carte AMO'
    )
    nom_assure = models.CharField(
        max_length=200,
        verbose_name="Nom complet de l'assuré"
    )
    date_expiration = models.DateField(
        verbose_name="Date d'expiration"
    )
    est_valide = models.BooleanField(default=True)
    date_ajout = models.DateTimeField(auto_now_add=True)
    date_modification = models.DateTimeField(auto_now=True)

    class Meta:
        verbose_name = 'Carte AMO'
        verbose_name_plural = 'Cartes AMO'

    def __str__(self):
        return f"AMO — {self.nom_assure} ({self.numero_carte})"
