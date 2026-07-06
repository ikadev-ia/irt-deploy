from django.db import models
from django.urls import reverse
from django.utils import timezone


class MobileUser(models.Model):
    full_name = models.CharField("nom complet", max_length=180)
    phone = models.CharField("telephone", max_length=40, unique=True)
    email = models.EmailField("email", unique=True, null=True, blank=True)
    password_hash = models.CharField(max_length=255, blank=True)
    avatar_url = models.URLField(blank=True)
    is_active = models.BooleanField("actif", default=True)
    created_at = models.DateTimeField("date inscription", default=timezone.now)
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        db_table = "mobile_users"
        ordering = ["full_name"]
        verbose_name = "client mobile"
        verbose_name_plural = "clients mobile"

    def __str__(self):
        return self.full_name

    @property
    def scan_count(self):
        return self.analyses.filter(source=AiAnalysis.Source.MOBILE).count()

    @property
    def disease_count(self):
        return (
            self.analyses.filter(source=AiAnalysis.Source.MOBILE)
            .exclude(detected_disease="")
            .values("detected_disease")
            .distinct()
            .count()
        )


class CompanyClient(models.Model):
    class SubscriptionPlan(models.TextChoices):
        STARTER = "starter", "Suivi initial client"
        GROWTH = "growth", "Croissance Drone + IA"
        LARGE = "large", "Grandes exploitations"

    class Status(models.TextChoices):
        PENDING = "pending", "En attente"
        ACTIVE = "active", "Actif"
        SUSPENDED = "suspended", "Suspendu"

    class PaymentMethod(models.TextChoices):
        BANK_TRANSFER = "bank_transfer", "Virement bancaire"
        MOBILE_MONEY = "mobile_money", "Mobile Money"
        CARD = "card", "Carte bancaire"
        CHECK = "check", "Cheque client"

    SUBSCRIPTION_PRICES = {
        SubscriptionPlan.STARTER: 75000,
        SubscriptionPlan.GROWTH: 150000,
        SubscriptionPlan.LARGE: 300000,
    }

    name = models.CharField("nom entreprise", max_length=180)
    manager_name = models.CharField("responsable", max_length=180)
    phone = models.CharField("telephone", max_length=40, blank=True)
    email = models.EmailField("email", blank=True)
    address = models.TextField("adresse", blank=True)
    hectares = models.DecimalField("nombre d'hectares", max_digits=12, decimal_places=2, default=0)
    subscription = models.CharField(
        "abonnement",
        max_length=30,
        choices=SubscriptionPlan.choices,
        default=SubscriptionPlan.STARTER,
    )
    subscription_price_xof = models.PositiveIntegerField("prix mensuel", default=75000)
    payment_method = models.CharField(
        "moyen de paiement",
        max_length=40,
        choices=PaymentMethod.choices,
        blank=True,
    )
    status = models.CharField("statut", max_length=30, choices=Status.choices, default=Status.PENDING)
    external_client_id = models.CharField("identifiant client externe", max_length=80, blank=True)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        db_table = "company_clients"
        ordering = ["name"]
        verbose_name = "client entreprise"
        verbose_name_plural = "clients entreprises"

    def __str__(self):
        return self.name

    @property
    def unpaid_total(self):
        return sum(invoice.amount_unpaid for invoice in self.invoices.all())


class Parcel(models.Model):
    class State(models.TextChoices):
        HEALTHY = "healthy", "Bon"
        WATCH = "watch", "Sous surveillance"
        RISK = "risk", "Risque"
        CRITICAL = "critical", "Critique"

    company = models.ForeignKey(CompanyClient, on_delete=models.CASCADE, related_name="parcels")
    name = models.CharField("nom parcelle", max_length=180)
    surface_hectares = models.DecimalField("surface", max_digits=12, decimal_places=2)
    crop = models.CharField("culture", max_length=140)
    gps_latitude = models.DecimalField(max_digits=10, decimal_places=7, null=True, blank=True)
    gps_longitude = models.DecimalField(max_digits=10, decimal_places=7, null=True, blank=True)
    general_state = models.CharField("etat general", max_length=30, choices=State.choices, default=State.WATCH)

    class Meta:
        db_table = "parcels"
        ordering = ["company__name", "name"]
        verbose_name = "parcelle"
        verbose_name_plural = "parcelles"

    def __str__(self):
        return f"{self.company} - {self.name}"


class Drone(models.Model):
    class Status(models.TextChoices):
        AVAILABLE = "available", "Disponible"
        IN_MISSION = "in_mission", "En mission"
        MAINTENANCE = "maintenance", "Maintenance"

    name = models.CharField("nom drone", max_length=140)
    model = models.CharField("modele", max_length=140)
    serial_number = models.CharField("numero serie", max_length=120, unique=True)
    status = models.CharField("etat", max_length=30, choices=Status.choices, default=Status.AVAILABLE)
    flight_count = models.PositiveIntegerField("nombre de vols", default=0)
    maintenance_date = models.DateField("date de maintenance", null=True, blank=True)
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        db_table = "drones"
        ordering = ["name"]
        verbose_name = "drone"
        verbose_name_plural = "drones"

    def __str__(self):
        return self.name


class Employee(models.Model):
    class Role(models.TextChoices):
        ADMIN = "admin", "Administrateur"
        DRONE_PILOT = "drone_pilot", "Pilote drone"
        AI_TECHNICIAN = "ai_technician", "Technicien IA"
        FIELD_AGENT = "field_agent", "Agent terrain"
        SALES = "sales", "Commercial"

    class Status(models.TextChoices):
        ACTIVE = "active", "Actif"
        FIELD = "field", "Sur terrain"
        OFF = "off", "Indisponible"

    full_name = models.CharField("nom complet", max_length=180)
    role = models.CharField("role", max_length=40, choices=Role.choices)
    phone = models.CharField("telephone", max_length=40, blank=True)
    email = models.EmailField("email", blank=True)
    status = models.CharField("statut", max_length=30, choices=Status.choices, default=Status.ACTIVE)
    hired_at = models.DateField("date d'arrivee", null=True, blank=True)

    class Meta:
        db_table = "employees"
        ordering = ["full_name"]
        verbose_name = "employe"
        verbose_name_plural = "employes"

    def __str__(self):
        return self.full_name


class FieldMission(models.Model):
    class Status(models.TextChoices):
        PLANNED = "planned", "Planifiee"
        IN_PROGRESS = "in_progress", "En cours"
        COMPLETED = "completed", "Terminee"
        CANCELLED = "cancelled", "Annulee"

    company = models.ForeignKey(CompanyClient, on_delete=models.CASCADE, related_name="company_missions")
    parcel = models.ForeignKey(Parcel, on_delete=models.CASCADE, related_name="parcel_missions")
    drone = models.ForeignKey(Drone, on_delete=models.PROTECT, related_name="drone_missions")
    pilot = models.ForeignKey(Employee, on_delete=models.SET_NULL, null=True, blank=True, related_name="pilote_missions")
    mission_date = models.DateTimeField("date")
    status = models.CharField("statut", max_length=30, choices=Status.choices, default=Status.PLANNED)
    notes = models.TextField(blank=True)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        db_table = "field_missions"
        ordering = ["-mission_date"]
        verbose_name = "mission terrain"
        verbose_name_plural = "missions terrain"

    def __str__(self):
        company_name = self.company.name if self.company else "Sans entreprise"
        parcel_name = self.parcel.name if self.parcel else "Sans entreprise"
        return f"Mission : {company_name} - {parcel_name}"


class Disease(models.Model):
    class RiskLevel(models.TextChoices):
        LOW = "low", "Faible"
        MEDIUM = "medium", "Modere"
        HIGH = "high", "Eleve"
        CRITICAL = "critical", "Critique"

    name = models.CharField("maladie", max_length=180, unique=True)
    crop = models.CharField("culture", max_length=140, blank=True)
    symptoms = models.TextField("symptomes", blank=True)
    causes = models.TextField(blank=True)
    risk_level = models.CharField("niveau de risque", max_length=30, choices=RiskLevel.choices, default=RiskLevel.MEDIUM)

    class Meta:
        db_table = "diseases"
        ordering = ["name"]
        verbose_name = "maladie"
        verbose_name_plural = "maladies"

    def __str__(self):
        return self.name


class AiAnalysis(models.Model):
    class Source(models.TextChoices):
        MOBILE = "mobile", "Mobile"
        DRONE = "drone", "Drone"

    class RiskLevel(models.TextChoices):
        LOW = "low", "Faible"
        MEDIUM = "medium", "Modere"
        HIGH = "high", "Eleve"
        CRITICAL = "critical", "Critique"
        UNKNOWN = "unknown", "A confirmer"

    class Provider(models.TextChoices):
        PLANTNET = "plantnet", "Pl@ntNet"
        PLANT_ID = "plant_id", "Plant.id"
        CROP_HEALTH = "crop_health", "Crop.Health"
        PLANT_VILLAGE = "plant_village", "PlantVillage"
        PLANT_DOC = "plant_doc", "PlantDoc"
        AGRICHECK = "agricheck", "Agricheck"

    source = models.CharField("origine", max_length=20, choices=Source.choices)
    mobile_user = models.ForeignKey(
        MobileUser,
        on_delete=models.CASCADE,
        related_name="analyses",
        null=True,
        blank=True,
    )
    company = models.ForeignKey(
        CompanyClient,
        on_delete=models.CASCADE,
        related_name="analyses",
        null=True,
        blank=True,
    )
    parcel = models.ForeignKey(Parcel, on_delete=models.SET_NULL, null=True, blank=True, related_name="analyses")
    mission = models.ForeignKey(FieldMission, on_delete=models.SET_NULL, null=True, blank=True, related_name="analyses")
    image = models.FileField("image", upload_to="analyses/", blank=True)
    image_url = models.URLField("image externe", blank=True)
    detected_plant = models.CharField("plante detectee", max_length=140, blank=True)
    disease = models.ForeignKey(Disease, on_delete=models.SET_NULL, null=True, blank=True, related_name="analyses")
    detected_disease = models.CharField("maladie detectee", max_length=180, blank=True)
    confidence = models.DecimalField("niveau de confiance", max_digits=5, decimal_places=2, null=True, blank=True)
    risk_level = models.CharField("niveau de risque", max_length=30, choices=RiskLevel.choices, default=RiskLevel.UNKNOWN)
    provider = models.CharField("source IA", max_length=40, choices=Provider.choices, default=Provider.AGRICHECK)
    raw_ai_response = models.JSONField("reponse IA brute", null=True, blank=True)
    analyzed_at = models.DateTimeField("date analyse", default=timezone.now)

    class Meta:
        db_table = "ai_analyses"
        ordering = ["-analyzed_at"]
        verbose_name = "analyse IA"
        verbose_name_plural = "analyses IA"

    def __str__(self):
        owner = self.mobile_user or self.company or "Agricheck"
        disease = self.disease.name if self.disease else self.detected_disease or "Diagnostic"
        return f"{owner} - {disease}"

    @property
    def disease_label(self):
        return self.disease.name if self.disease else self.detected_disease

    @property
    def client_label(self):
        if self.source == self.Source.MOBILE and self.mobile_user:
            return self.mobile_user.full_name
        if self.company:
            return self.company.name
        return ""

    @property
    def result_summary(self):
        result_parts = []
        if self.detected_plant:
            result_parts.append(f"Plante detectee : {self.detected_plant}")
        if self.disease_label:
            result_parts.append(f"Maladie detectee : {self.disease_label}")
        if self.confidence is not None:
            result_parts.append(f"Niveau de confiance : {self.confidence}%")
        symptoms = []
        if isinstance(self.raw_ai_response, dict):
            symptoms = self.raw_ai_response.get("symptoms") or []
        if not symptoms and self.disease and self.disease.symptoms:
            symptoms = [line.strip() for line in self.disease.symptoms.splitlines() if line.strip()]
        if symptoms:
            result_parts.append("Symptomes : " + "; ".join(symptoms[:4]))
        result_parts.append(f"Niveau de risque : {self.get_risk_level_display()}")
        result_parts.append(f"Source IA : {self.get_provider_display()}")
        return "\n".join(result_parts)


class Treatment(models.Model):
    disease = models.ForeignKey(Disease, on_delete=models.CASCADE, related_name="treatments")
    product_recommended = models.TextField("produit recommande", blank=True)
    dosage = models.CharField(max_length=180, blank=True)
    frequency = models.CharField("frequence", max_length=180, blank=True)
    prevention = models.TextField("conseils de prevention", blank=True)
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        db_table = "treatments"
        ordering = ["disease__name"]
        verbose_name = "traitement"
        verbose_name_plural = "traitements"

    def __str__(self):
        return f"Traitement - {self.disease}"


class Report(models.Model):
    class Status(models.TextChoices):
        DRAFT = "draft", "Brouillon"
        PUBLISHED = "published", "Publie"

    company = models.ForeignKey(CompanyClient, on_delete=models.CASCADE, related_name="parcel_reports")
    parcel = models.ForeignKey(Parcel, on_delete=models.SET_NULL, null=True, blank=True, related_name="analysis_reports")
    analysis = models.ForeignKey(AiAnalysis, on_delete=models.SET_NULL, null=True, blank=True, related_name="reports")
    title = models.CharField("rapport client", max_length=180)
    ai_result = models.TextField("resultat IA", blank=True)
    recommended_treatment = models.TextField("traitement recommande", blank=True)
    pdf = models.FileField("PDF telechargeable", upload_to="reports/pdfs/", blank=True)
    status = models.CharField("statut", max_length=30, choices=Status.choices, default=Status.DRAFT)
    published_at = models.DateTimeField(null=True, blank=True)
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        db_table = "reports"
        ordering = ["-created_at"]
        verbose_name = "rapport"
        verbose_name_plural = "rapports"

    def __str__(self):
        return self.title

    @property
    def analysis_result_display(self):
        if self.ai_result:
            return self.ai_result
        if self.analysis:
            return self.analysis.result_summary
        return ""

    @property
    def recommended_treatment_display(self):
        if self.recommended_treatment:
            return self.recommended_treatment
        if not self.analysis or not self.analysis.disease:
            return ""
        treatment = self.analysis.disease.treatments.first()
        if not treatment:
            return ""
        parts = [
            treatment.product_recommended,
            treatment.dosage,
            treatment.frequency,
            treatment.prevention,
        ]
        return "\n".join(part for part in parts if part)

    def save(self, *args, **kwargs):
        if self.analysis and not self.ai_result:
            self.ai_result = self.analysis.result_summary
        if self.analysis and not self.recommended_treatment:
            self.recommended_treatment = self.recommended_treatment_display
        if self.status == self.Status.PUBLISHED and self.published_at is None:
            self.published_at = timezone.now()
        if self.status == self.Status.DRAFT:
            self.published_at = None
        super().save(*args, **kwargs)


class Invoice(models.Model):
    class Status(models.TextChoices):
        PENDING = "pending", "En attente"
        PARTIAL = "partial", "Partiel"
        PAID = "paid", "Paye"
        OVERDUE = "overdue", "En retard"
        CANCELLED = "cancelled", "Annule"

    company = models.ForeignKey(CompanyClient, on_delete=models.CASCADE, related_name="invoices")
    number = models.CharField("facture", max_length=80, unique=True)
    subscription = models.CharField("abonnement", max_length=160, blank=True)
    amount_total = models.DecimalField("montant facture", max_digits=12, decimal_places=2)
    amount_paid = models.DecimalField("montant paye", max_digits=12, decimal_places=2, default=0)
    status = models.CharField("statut", max_length=30, choices=Status.choices, default=Status.PENDING)
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

    @property
    def amount_unpaid(self):
        unpaid = self.amount_total - self.amount_paid
        return unpaid if unpaid > 0 else 0


class ImportantAlert(models.Model):
    class Priority(models.TextChoices):
        INFO = "info", "Information"
        WARNING = "warning", "Attention"
        URGENT = "urgent", "Urgent"

    title = models.CharField("alerte", max_length=180)
    message = models.TextField(blank=True)
    priority = models.CharField(max_length=30, choices=Priority.choices, default=Priority.INFO)
    is_resolved = models.BooleanField("resolue", default=False)
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        db_table = "important_alerts"
        ordering = ["is_resolved", "-created_at"]
        verbose_name = "alerte importante"
        verbose_name_plural = "alertes importantes"

    def __str__(self):
        return self.title


class MobileNotification(models.Model):
    mobile_user = models.ForeignKey(MobileUser, on_delete=models.CASCADE, related_name="notifications")
    title = models.CharField(max_length=180)
    message = models.TextField()
    type = models.CharField(max_length=80, blank=True)
    is_read = models.BooleanField(default=False)
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        db_table = "mobile_notifications"
        ordering = ["-created_at"]
        verbose_name = "notification mobile"
        verbose_name_plural = "notifications mobile"

    def __str__(self):
        return self.title


def entity_url(slug, action="list", pk=None):
    if action == "create":
        return reverse("operations:entity_create", kwargs={"slug": slug})
    if action == "update":
        return reverse("operations:entity_update", kwargs={"slug": slug, "pk": pk})
    return reverse("operations:entity_list", kwargs={"slug": slug})
