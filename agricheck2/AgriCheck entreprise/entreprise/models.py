from django.conf import settings
from django.contrib.auth.models import AbstractUser
from django.db import models
from django.utils import timezone


class Company(models.Model):
    class SubscriptionPlan(models.TextChoices):
        STARTER = "starter", "Suivi initial client"
        GROWTH = "growth", "Croissance Drone + IA"
        LARGE = "large", "Grandes exploitations"

    SUBSCRIPTION_PLAN_DETAILS = {
        SubscriptionPlan.STARTER.value: {
            "price_xof": 10000,
            "travel_limit_km": 30,
            "summary": "Pour un client qui debute son suivi Agricheck 1 fois.",
        },
        SubscriptionPlan.GROWTH.value: {
            "price_xof": 250000,
            "travel_limit_km": 50,
            "summary": "Pour une exploitation en croissance avec suivi drone regulier 3 fois.",
        },
        SubscriptionPlan.LARGE.value: {
            "price_xof": 450000,
            "travel_limit_km": 80,
            "summary": "Pour les grandes surfaces et le suivi de plusieurs parcelles 5 fois.",
        },
    }

    class RegistrationStatus(models.TextChoices):
        PENDING = "pending", "En attente de configuration"
        ACTIVE = "active", "Suivi actif"
        SUSPENDED = "suspended", "Suspendu"

    class PaymentMethod(models.TextChoices):
        BANK_TRANSFER = "bank_transfer", "Virement bancaire"
        MOBILE_MONEY = "mobile_money", "Mobile Money"
        CARD = "card", "Carte bancaire"
        CHECK = "check", "Cheque client"

    name = models.CharField("nom exploitation", max_length=180)
    manager_name = models.CharField("responsable", max_length=180, blank=True)
    phone = models.CharField("telephone", max_length=40, blank=True)
    email = models.EmailField("email", blank=True)
    address = models.TextField("adresse", blank=True)
    hectares = models.DecimalField("nombre d'hectares", max_digits=12, decimal_places=2, default=0)
    subscription_type = models.CharField(
        "type d'abonnement",
        max_length=120,
        choices=SubscriptionPlan.choices,
        default=SubscriptionPlan.STARTER,
        blank=True,
    )
    subscription_price_xof = models.PositiveIntegerField("prix mensuel", default=75000)
    travel_included = models.BooleanField("deplacement inclus", default=True)
    travel_limit_km = models.PositiveIntegerField("kilometres inclus", default=30)
    payment_method = models.CharField(
        "moyen de paiement",
        max_length=40,
        choices=PaymentMethod.choices,
        blank=True,
    )
    registration_status = models.CharField(
        "statut inscription",
        max_length=30,
        choices=RegistrationStatus.choices,
        default=RegistrationStatus.PENDING,
    )
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        db_table = "companies"
        ordering = ["name"]
        verbose_name = "client"
        verbose_name_plural = "clients"

    def __str__(self):
        return self.name

    @property
    def subscription_label(self):
        if not self.subscription_type:
            return ""
        return self.get_subscription_type_display()

    @property
    def subscription_price_display(self):
        if not self.subscription_price_xof:
            return "Prix a confirmer"
        amount = f"{self.subscription_price_xof:,}".replace(",", " ")
        return f"{amount} FCFA / mois"

    @property
    def travel_included_display(self):
        if not self.travel_included:
            return "Deplacement non inclus"
        if self.travel_limit_km:
            return f"Deplacement inclus jusqu'a {self.travel_limit_km} km"
        return "Deplacement inclus"


class User(AbstractUser):
    class Role(models.TextChoices):
        CLIENT = "client", "Client Agricheck"
        ADMIN = "admin", "Admin Agricheck"

    email = models.EmailField("email", unique=True)
    role = models.CharField(max_length=30, choices=Role.choices, default=Role.CLIENT)
    company = models.ForeignKey(
        Company,
        on_delete=models.SET_NULL,
        null=True,
        blank=True,
        related_name="users",
    )
    phone = models.CharField("telephone", max_length=40, blank=True)

    class Meta:
        db_table = "users"
        verbose_name = "utilisateur"
        verbose_name_plural = "utilisateurs"

    @property
    def is_agricheck_admin(self):
        return self.is_superuser or self.is_staff or self.role == self.Role.ADMIN


class Parcel(models.Model):
    class State(models.TextChoices):
        HEALTHY = "healthy", "Bon"
        WATCH = "watch", "Sous surveillance"
        RISK = "risk", "Risque"
        CRITICAL = "critical", "Critique"

    company = models.ForeignKey(Company, on_delete=models.CASCADE, related_name="parcels")
    name = models.CharField("nom parcelle", max_length=180)
    surface_hectares = models.DecimalField("surface", max_digits=12, decimal_places=2)
    crop = models.CharField("culture", max_length=140)
    gps_latitude = models.DecimalField(max_digits=10, decimal_places=7, null=True, blank=True)
    gps_longitude = models.DecimalField(max_digits=10, decimal_places=7, null=True, blank=True)
    general_state = models.CharField(
        "etat general",
        max_length=30,
        choices=State.choices,
        default=State.WATCH,
    )
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        db_table = "parcels"
        ordering = ["company__name", "name"]
        verbose_name = "parcelle"
        verbose_name_plural = "parcelles"

    def __str__(self):
        return self.name

    @property
    def gps_label(self):
        if self.gps_latitude is None or self.gps_longitude is None:
            return ""
        return f"{self.gps_latitude}, {self.gps_longitude}"

    @property
    def client_state_label(self):
        if self.general_state == self.State.HEALTHY:
            return "Bon etat"
        if self.general_state == self.State.WATCH:
            return "Surveillance recommandee"
        return "Intervention urgente"

    @property
    def client_state_class(self):
        if self.general_state == self.State.HEALTHY:
            return "state-good"
        if self.general_state == self.State.WATCH:
            return "state-watch"
        return "state-urgent"


class Drone(models.Model):
    class Status(models.TextChoices):
        AVAILABLE = "available", "Disponible"
        IN_MISSION = "in_mission", "En mission"
        MAINTENANCE = "maintenance", "Maintenance"

    name = models.CharField("drone utilise", max_length=140)
    serial_number = models.CharField(max_length=120, unique=True)
    model = models.CharField(max_length=140, blank=True)
    status = models.CharField(max_length=30, choices=Status.choices, default=Status.AVAILABLE)
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        db_table = "drones"
        ordering = ["name"]
        verbose_name = "drone"
        verbose_name_plural = "drones"

    def __str__(self):
        return self.name


class DroneMission(models.Model):
    class Status(models.TextChoices):
        PLANNED = "planned", "Mission planifiee"
        IN_PROGRESS = "in_progress", "Mission en cours"
        COMPLETED = "completed", "Mission terminee"
        CANCELLED = "cancelled", "Mission annulee"

    company = models.ForeignKey(Company, on_delete=models.CASCADE, related_name="drone_missions")
    parcel = models.ForeignKey(Parcel, on_delete=models.CASCADE, related_name="drone_missions")
    drone = models.ForeignKey(Drone, on_delete=models.PROTECT, related_name="missions")
    status = models.CharField(max_length=30, choices=Status.choices, default=Status.PLANNED)
    mission_date = models.DateTimeField("date mission")
    notes = models.TextField(blank=True)
    created_by = models.ForeignKey(
        settings.AUTH_USER_MODEL,
        on_delete=models.SET_NULL,
        null=True,
        blank=True,
        related_name="created_missions",
    )
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        db_table = "drone_missions"
        ordering = ["-mission_date"]
        verbose_name = "mission drone"
        verbose_name_plural = "missions drone"

    def __str__(self):
        return f"{self.parcel} - {self.get_status_display()}"


class DroneImage(models.Model):
    mission = models.ForeignKey(DroneMission, on_delete=models.CASCADE, related_name="images")
    image = models.FileField(upload_to="drone-images/")
    caption = models.CharField(max_length=180, blank=True)
    captured_at = models.DateTimeField(default=timezone.now)
    gps_latitude = models.DecimalField(max_digits=10, decimal_places=7, null=True, blank=True)
    gps_longitude = models.DecimalField(max_digits=10, decimal_places=7, null=True, blank=True)

    class Meta:
        db_table = "drone_images"
        ordering = ["-captured_at"]
        verbose_name = "image drone"
        verbose_name_plural = "images drone"

    def __str__(self):
        return self.caption or f"Image {self.pk}"


class Disease(models.Model):
    class RiskLevel(models.TextChoices):
        LOW = "low", "Faible"
        MEDIUM = "medium", "Modere"
        HIGH = "high", "Eleve"
        CRITICAL = "critical", "Critique"

    name = models.CharField("maladie", max_length=180, unique=True)
    crop = models.CharField("culture", max_length=140, blank=True)
    description = models.TextField(blank=True)
    symptoms = models.TextField("symptomes", blank=True)
    causes = models.TextField(blank=True)
    risk_level = models.CharField(max_length=30, choices=RiskLevel.choices, default=RiskLevel.MEDIUM)

    class Meta:
        db_table = "diseases"
        ordering = ["name"]
        verbose_name = "maladie"
        verbose_name_plural = "maladies"

    def __str__(self):
        return self.name


class Analysis(models.Model):
    class RiskLevel(models.TextChoices):
        LOW = "low", "Faible"
        MEDIUM = "medium", "Modere"
        HIGH = "high", "Eleve"
        CRITICAL = "critical", "Critique"

    class AiProvider(models.TextChoices):
        PLANTNET = "plantnet", "Pl@ntNet"
        PLANT_ID = "plant_id", "Plant.id"
        CROP_HEALTH = "crop_health", "Crop.Health"
        PLANT_VILLAGE = "plant_village", "PlantVillage"
        PLANT_DOC = "plant_doc", "PlantDoc"

    company = models.ForeignKey(Company, on_delete=models.CASCADE, related_name="analyses")
    parcel = models.ForeignKey(Parcel, on_delete=models.CASCADE, related_name="analyses")
    mission = models.ForeignKey(DroneMission, on_delete=models.CASCADE, related_name="analyses")
    image = models.ForeignKey(DroneImage, on_delete=models.SET_NULL, null=True, blank=True, related_name="analyses")
    detected_crop = models.CharField("culture detectee", max_length=140, blank=True)
    disease = models.ForeignKey(Disease, on_delete=models.SET_NULL, null=True, blank=True, related_name="analyses")
    confidence = models.DecimalField("confiance IA", max_digits=5, decimal_places=2, null=True, blank=True)
    risk_level = models.CharField("niveau de risque", max_length=30, choices=RiskLevel.choices, default=RiskLevel.MEDIUM)
    ai_provider = models.CharField(max_length=40, choices=AiProvider.choices, default=AiProvider.PLANTNET)
    raw_result = models.JSONField(blank=True, null=True)
    analyzed_at = models.DateTimeField("date analyse", default=timezone.now)

    class Meta:
        db_table = "analyses"
        ordering = ["-analyzed_at"]
        verbose_name = "resultat Agricheck"
        verbose_name_plural = "resultats Agricheck"

    def __str__(self):
        disease = self.disease.name if self.disease else "Aucune maladie renseignee"
        return f"{self.parcel} - {disease}"


class Treatment(models.Model):
    disease = models.ForeignKey(Disease, on_delete=models.CASCADE, related_name="treatments")
    natural_treatments = models.TextField("traitements naturels", blank=True)
    recommended_products = models.TextField("produits recommandes", blank=True)
    dosage = models.TextField(blank=True)
    frequency = models.CharField("frequence", max_length=180, blank=True)
    prevention = models.TextField(blank=True)
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        db_table = "treatments"
        ordering = ["disease__name"]
        verbose_name = "traitement recommande"
        verbose_name_plural = "traitements recommandes"

    def __str__(self):
        return f"Traitement - {self.disease}"


class Report(models.Model):
    company = models.ForeignKey(Company, on_delete=models.CASCADE, related_name="reports")
    mission = models.ForeignKey(DroneMission, on_delete=models.CASCADE, related_name="reports")
    parcel = models.ForeignKey(Parcel, on_delete=models.CASCADE, related_name="reports")
    analysis = models.ForeignKey(Analysis, on_delete=models.SET_NULL, null=True, blank=True, related_name="reports")
    title = models.CharField(max_length=180)
    summary = models.TextField("resultat IA", blank=True)
    affected_zones = models.TextField("zones touchees", blank=True)
    heatmap = models.FileField("carte thermique", upload_to="reports/heatmaps/", blank=True)
    recommended_treatments = models.TextField("traitements recommandes", blank=True)
    pdf = models.FileField("rapport PDF", upload_to="reports/pdfs/", blank=True)
    is_published = models.BooleanField(default=False)
    published_at = models.DateTimeField(null=True, blank=True)
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        db_table = "reports"
        ordering = ["-published_at", "-created_at"]
        verbose_name = "rapport"
        verbose_name_plural = "rapports"

    def __str__(self):
        return self.title


class Invoice(models.Model):
    class Status(models.TextChoices):
        PENDING = "pending", "En attente"
        PAID = "paid", "Paye"
        OVERDUE = "overdue", "En retard"
        CANCELLED = "cancelled", "Annule"

    company = models.ForeignKey(Company, on_delete=models.CASCADE, related_name="invoices")
    number = models.CharField("facture", max_length=80, unique=True)
    amount = models.DecimalField("montant", max_digits=12, decimal_places=2)
    currency = models.CharField(max_length=10, default="XOF")
    status = models.CharField("statut paiement", max_length=30, choices=Status.choices, default=Status.PENDING)
    issued_at = models.DateField("date emission", default=timezone.localdate)
    due_date = models.DateField("date echeance", null=True, blank=True)
    paid_at = models.DateField("date paiement", null=True, blank=True)
    pdf = models.FileField("facture PDF", upload_to="invoices/", blank=True)

    class Meta:
        db_table = "invoices"
        ordering = ["-issued_at"]
        verbose_name = "facture"
        verbose_name_plural = "factures"

    def __str__(self):
        return self.number


class Notification(models.Model):
    class Type(models.TextChoices):
        ACCOUNT_CREATED = "account_created", "Inscription enregistree"
        MISSION_COMPLETED = "mission_completed", "Nouvelle mission terminee"
        ANALYSIS_AVAILABLE = "analysis_available", "Nouvelle analyse disponible"
        REPORT_PUBLISHED = "report_published", "Rapport publie"
        HIGH_RISK = "high_risk", "Risque eleve detecte"
        URGENT_TREATMENT = "urgent_treatment", "Traitement recommande"
        TREATMENT_RECOMMENDED = "treatment_recommended", "Traitement recommande"
        INVOICE_AVAILABLE = "invoice_available", "Facture disponible"

    class Priority(models.TextChoices):
        INFO = "info", "Information"
        WARNING = "warning", "Attention"
        URGENT = "urgent", "Urgent"

    company = models.ForeignKey(Company, on_delete=models.CASCADE, related_name="notifications")
    user = models.ForeignKey(
        settings.AUTH_USER_MODEL,
        on_delete=models.SET_NULL,
        null=True,
        blank=True,
        related_name="notifications",
    )
    type = models.CharField(max_length=40, choices=Type.choices)
    title = models.CharField(max_length=180)
    message = models.TextField(blank=True)
    priority = models.CharField(max_length=30, choices=Priority.choices, default=Priority.INFO)
    is_read = models.BooleanField(default=False)
    link = models.CharField(max_length=240, blank=True)
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        db_table = "notifications"
        ordering = ["-created_at"]
        verbose_name = "notification"
        verbose_name_plural = "notifications"

    def __str__(self):
        return self.title
