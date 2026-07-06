# Generated for Agricheck Entreprise.
import django.contrib.auth.models
import django.contrib.auth.validators
import django.db.models.deletion
import django.utils.timezone
from django.conf import settings
from django.db import migrations, models


class Migration(migrations.Migration):
    initial = True

    dependencies = [
        ("auth", "0012_alter_user_first_name_max_length"),
    ]

    operations = [
        migrations.CreateModel(
            name="Company",
            fields=[
                ("id", models.BigAutoField(auto_created=True, primary_key=True, serialize=False, verbose_name="ID")),
                ("name", models.CharField(max_length=180, verbose_name="nom entreprise")),
                ("manager_name", models.CharField(blank=True, max_length=180, verbose_name="responsable")),
                ("phone", models.CharField(blank=True, max_length=40, verbose_name="telephone")),
                ("email", models.EmailField(blank=True, max_length=254, verbose_name="email")),
                ("address", models.TextField(blank=True, verbose_name="adresse")),
                ("hectares", models.DecimalField(decimal_places=2, default=0, max_digits=12, verbose_name="nombre d'hectares")),
                ("subscription_type", models.CharField(blank=True, max_length=120, verbose_name="type d'abonnement")),
                ("created_at", models.DateTimeField(auto_now_add=True)),
                ("updated_at", models.DateTimeField(auto_now=True)),
            ],
            options={
                "verbose_name": "entreprise",
                "verbose_name_plural": "entreprises",
                "db_table": "companies",
                "ordering": ["name"],
            },
        ),
        migrations.CreateModel(
            name="Disease",
            fields=[
                ("id", models.BigAutoField(auto_created=True, primary_key=True, serialize=False, verbose_name="ID")),
                ("name", models.CharField(max_length=180, unique=True, verbose_name="maladie")),
                ("crop", models.CharField(blank=True, max_length=140, verbose_name="culture")),
                ("description", models.TextField(blank=True)),
                ("symptoms", models.TextField(blank=True, verbose_name="symptomes")),
                ("causes", models.TextField(blank=True)),
                ("risk_level", models.CharField(choices=[("low", "Faible"), ("medium", "Modere"), ("high", "Eleve"), ("critical", "Critique")], default="medium", max_length=30)),
            ],
            options={
                "verbose_name": "maladie",
                "verbose_name_plural": "maladies",
                "db_table": "diseases",
                "ordering": ["name"],
            },
        ),
        migrations.CreateModel(
            name="Drone",
            fields=[
                ("id", models.BigAutoField(auto_created=True, primary_key=True, serialize=False, verbose_name="ID")),
                ("name", models.CharField(max_length=140, verbose_name="drone utilise")),
                ("serial_number", models.CharField(max_length=120, unique=True)),
                ("model", models.CharField(blank=True, max_length=140)),
                ("status", models.CharField(choices=[("available", "Disponible"), ("in_mission", "En mission"), ("maintenance", "Maintenance")], default="available", max_length=30)),
                ("created_at", models.DateTimeField(auto_now_add=True)),
            ],
            options={
                "verbose_name": "drone",
                "verbose_name_plural": "drones",
                "db_table": "drones",
                "ordering": ["name"],
            },
        ),
        migrations.CreateModel(
            name="User",
            fields=[
                ("id", models.BigAutoField(auto_created=True, primary_key=True, serialize=False, verbose_name="ID")),
                ("password", models.CharField(max_length=128, verbose_name="password")),
                ("last_login", models.DateTimeField(blank=True, null=True, verbose_name="last login")),
                ("is_superuser", models.BooleanField(default=False, help_text="Designates that this user has all permissions without explicitly assigning them.", verbose_name="superuser status")),
                ("username", models.CharField(error_messages={"unique": "A user with that username already exists."}, help_text="Required. 150 characters or fewer. Letters, digits and @/./+/-/_ only.", max_length=150, unique=True, validators=[django.contrib.auth.validators.UnicodeUsernameValidator()], verbose_name="username")),
                ("first_name", models.CharField(blank=True, max_length=150, verbose_name="first name")),
                ("last_name", models.CharField(blank=True, max_length=150, verbose_name="last name")),
                ("email", models.EmailField(max_length=254, unique=True, verbose_name="email")),
                ("is_staff", models.BooleanField(default=False, help_text="Designates whether the user can log into this admin site.", verbose_name="staff status")),
                ("is_active", models.BooleanField(default=True, help_text="Designates whether this user should be treated as active. Unselect this instead of deleting accounts.", verbose_name="active")),
                ("date_joined", models.DateTimeField(default=django.utils.timezone.now, verbose_name="date joined")),
                ("role", models.CharField(choices=[("client", "Client entreprise"), ("admin", "Admin Agricheck")], default="client", max_length=30)),
                ("phone", models.CharField(blank=True, max_length=40, verbose_name="telephone")),
                ("company", models.ForeignKey(blank=True, null=True, on_delete=django.db.models.deletion.SET_NULL, related_name="users", to="entreprise.company")),
                ("groups", models.ManyToManyField(blank=True, help_text="The groups this user belongs to. A user will get all permissions granted to each of their groups.", related_name="user_set", related_query_name="user", to="auth.group", verbose_name="groups")),
                ("user_permissions", models.ManyToManyField(blank=True, help_text="Specific permissions for this user.", related_name="user_set", related_query_name="user", to="auth.permission", verbose_name="user permissions")),
            ],
            options={
                "verbose_name": "utilisateur",
                "verbose_name_plural": "utilisateurs",
                "db_table": "users",
            },
            managers=[
                ("objects", django.contrib.auth.models.UserManager()),
            ],
        ),
        migrations.CreateModel(
            name="Parcel",
            fields=[
                ("id", models.BigAutoField(auto_created=True, primary_key=True, serialize=False, verbose_name="ID")),
                ("name", models.CharField(max_length=180, verbose_name="nom parcelle")),
                ("surface_hectares", models.DecimalField(decimal_places=2, max_digits=12, verbose_name="surface")),
                ("crop", models.CharField(max_length=140, verbose_name="culture")),
                ("gps_latitude", models.DecimalField(blank=True, decimal_places=7, max_digits=10, null=True)),
                ("gps_longitude", models.DecimalField(blank=True, decimal_places=7, max_digits=10, null=True)),
                ("general_state", models.CharField(choices=[("healthy", "Bon"), ("watch", "Sous surveillance"), ("risk", "Risque"), ("critical", "Critique")], default="watch", max_length=30, verbose_name="etat general")),
                ("created_at", models.DateTimeField(auto_now_add=True)),
                ("updated_at", models.DateTimeField(auto_now=True)),
                ("company", models.ForeignKey(on_delete=django.db.models.deletion.CASCADE, related_name="parcels", to="entreprise.company")),
            ],
            options={
                "verbose_name": "parcelle",
                "verbose_name_plural": "parcelles",
                "db_table": "parcels",
                "ordering": ["company__name", "name"],
            },
        ),
        migrations.CreateModel(
            name="DroneMission",
            fields=[
                ("id", models.BigAutoField(auto_created=True, primary_key=True, serialize=False, verbose_name="ID")),
                ("status", models.CharField(choices=[("planned", "Mission planifiee"), ("in_progress", "Mission en cours"), ("completed", "Mission terminee"), ("cancelled", "Mission annulee")], default="planned", max_length=30)),
                ("mission_date", models.DateTimeField(verbose_name="date mission")),
                ("notes", models.TextField(blank=True)),
                ("created_at", models.DateTimeField(auto_now_add=True)),
                ("updated_at", models.DateTimeField(auto_now=True)),
                ("company", models.ForeignKey(on_delete=django.db.models.deletion.CASCADE, related_name="drone_missions", to="entreprise.company")),
                ("created_by", models.ForeignKey(blank=True, null=True, on_delete=django.db.models.deletion.SET_NULL, related_name="created_missions", to=settings.AUTH_USER_MODEL)),
                ("drone", models.ForeignKey(on_delete=django.db.models.deletion.PROTECT, related_name="missions", to="entreprise.drone")),
                ("parcel", models.ForeignKey(on_delete=django.db.models.deletion.CASCADE, related_name="drone_missions", to="entreprise.parcel")),
            ],
            options={
                "verbose_name": "mission drone",
                "verbose_name_plural": "missions drone",
                "db_table": "drone_missions",
                "ordering": ["-mission_date"],
            },
        ),
        migrations.CreateModel(
            name="DroneImage",
            fields=[
                ("id", models.BigAutoField(auto_created=True, primary_key=True, serialize=False, verbose_name="ID")),
                ("image", models.FileField(upload_to="drone-images/")),
                ("caption", models.CharField(blank=True, max_length=180)),
                ("captured_at", models.DateTimeField(default=django.utils.timezone.now)),
                ("gps_latitude", models.DecimalField(blank=True, decimal_places=7, max_digits=10, null=True)),
                ("gps_longitude", models.DecimalField(blank=True, decimal_places=7, max_digits=10, null=True)),
                ("mission", models.ForeignKey(on_delete=django.db.models.deletion.CASCADE, related_name="images", to="entreprise.dronemission")),
            ],
            options={
                "verbose_name": "image drone",
                "verbose_name_plural": "images drone",
                "db_table": "drone_images",
                "ordering": ["-captured_at"],
            },
        ),
        migrations.CreateModel(
            name="Analysis",
            fields=[
                ("id", models.BigAutoField(auto_created=True, primary_key=True, serialize=False, verbose_name="ID")),
                ("detected_crop", models.CharField(blank=True, max_length=140, verbose_name="culture detectee")),
                ("confidence", models.DecimalField(blank=True, decimal_places=2, max_digits=5, null=True, verbose_name="confiance IA")),
                ("risk_level", models.CharField(choices=[("low", "Faible"), ("medium", "Modere"), ("high", "Eleve"), ("critical", "Critique")], default="medium", max_length=30, verbose_name="niveau de risque")),
                ("ai_provider", models.CharField(choices=[("plantnet", "Pl@ntNet"), ("plant_id", "Plant.id"), ("crop_health", "Crop.Health"), ("plant_village", "PlantVillage"), ("plant_doc", "PlantDoc")], default="plantnet", max_length=40)),
                ("raw_result", models.JSONField(blank=True, null=True)),
                ("analyzed_at", models.DateTimeField(default=django.utils.timezone.now, verbose_name="date analyse")),
                ("company", models.ForeignKey(on_delete=django.db.models.deletion.CASCADE, related_name="analyses", to="entreprise.company")),
                ("disease", models.ForeignKey(blank=True, null=True, on_delete=django.db.models.deletion.SET_NULL, related_name="analyses", to="entreprise.disease")),
                ("image", models.ForeignKey(blank=True, null=True, on_delete=django.db.models.deletion.SET_NULL, related_name="analyses", to="entreprise.droneimage")),
                ("mission", models.ForeignKey(on_delete=django.db.models.deletion.CASCADE, related_name="analyses", to="entreprise.dronemission")),
                ("parcel", models.ForeignKey(on_delete=django.db.models.deletion.CASCADE, related_name="analyses", to="entreprise.parcel")),
            ],
            options={
                "verbose_name": "analyse IA",
                "verbose_name_plural": "analyses IA",
                "db_table": "analyses",
                "ordering": ["-analyzed_at"],
            },
        ),
        migrations.CreateModel(
            name="Treatment",
            fields=[
                ("id", models.BigAutoField(auto_created=True, primary_key=True, serialize=False, verbose_name="ID")),
                ("natural_treatments", models.TextField(blank=True, verbose_name="traitements naturels")),
                ("recommended_products", models.TextField(blank=True, verbose_name="produits recommandes")),
                ("dosage", models.TextField(blank=True)),
                ("prevention", models.TextField(blank=True)),
                ("updated_at", models.DateTimeField(auto_now=True)),
                ("disease", models.ForeignKey(on_delete=django.db.models.deletion.CASCADE, related_name="treatments", to="entreprise.disease")),
            ],
            options={
                "verbose_name": "traitement recommande",
                "verbose_name_plural": "traitements recommandes",
                "db_table": "treatments",
                "ordering": ["disease__name"],
            },
        ),
        migrations.CreateModel(
            name="Report",
            fields=[
                ("id", models.BigAutoField(auto_created=True, primary_key=True, serialize=False, verbose_name="ID")),
                ("title", models.CharField(max_length=180)),
                ("summary", models.TextField(blank=True, verbose_name="resultat IA")),
                ("affected_zones", models.TextField(blank=True, verbose_name="zones touchees")),
                ("heatmap", models.FileField(blank=True, upload_to="reports/heatmaps/", verbose_name="carte thermique")),
                ("recommended_treatments", models.TextField(blank=True, verbose_name="traitements recommandes")),
                ("pdf", models.FileField(blank=True, upload_to="reports/pdfs/", verbose_name="rapport PDF")),
                ("is_published", models.BooleanField(default=False)),
                ("published_at", models.DateTimeField(blank=True, null=True)),
                ("created_at", models.DateTimeField(auto_now_add=True)),
                ("analysis", models.ForeignKey(blank=True, null=True, on_delete=django.db.models.deletion.SET_NULL, related_name="reports", to="entreprise.analysis")),
                ("company", models.ForeignKey(on_delete=django.db.models.deletion.CASCADE, related_name="reports", to="entreprise.company")),
                ("mission", models.ForeignKey(on_delete=django.db.models.deletion.CASCADE, related_name="reports", to="entreprise.dronemission")),
                ("parcel", models.ForeignKey(on_delete=django.db.models.deletion.CASCADE, related_name="reports", to="entreprise.parcel")),
            ],
            options={
                "verbose_name": "rapport",
                "verbose_name_plural": "rapports",
                "db_table": "reports",
                "ordering": ["-published_at", "-created_at"],
            },
        ),
        migrations.CreateModel(
            name="Invoice",
            fields=[
                ("id", models.BigAutoField(auto_created=True, primary_key=True, serialize=False, verbose_name="ID")),
                ("number", models.CharField(max_length=80, unique=True, verbose_name="facture")),
                ("amount", models.DecimalField(decimal_places=2, max_digits=12, verbose_name="montant")),
                ("currency", models.CharField(default="XOF", max_length=10)),
                ("status", models.CharField(choices=[("pending", "En attente"), ("paid", "Paye"), ("overdue", "En retard"), ("cancelled", "Annule")], default="pending", max_length=30, verbose_name="statut paiement")),
                ("issued_at", models.DateField(default=django.utils.timezone.localdate, verbose_name="date emission")),
                ("due_date", models.DateField(blank=True, null=True, verbose_name="date echeance")),
                ("paid_at", models.DateField(blank=True, null=True, verbose_name="date paiement")),
                ("pdf", models.FileField(blank=True, upload_to="invoices/", verbose_name="facture PDF")),
                ("company", models.ForeignKey(on_delete=django.db.models.deletion.CASCADE, related_name="invoices", to="entreprise.company")),
            ],
            options={
                "verbose_name": "facture",
                "verbose_name_plural": "factures",
                "db_table": "invoices",
                "ordering": ["-issued_at"],
            },
        ),
        migrations.CreateModel(
            name="Notification",
            fields=[
                ("id", models.BigAutoField(auto_created=True, primary_key=True, serialize=False, verbose_name="ID")),
                ("type", models.CharField(choices=[("analysis_available", "Nouvelle analyse disponible"), ("report_published", "Rapport publie"), ("high_risk", "Risque eleve detecte"), ("urgent_treatment", "Traitement urgent"), ("invoice_available", "Facture disponible")], max_length=40)),
                ("title", models.CharField(max_length=180)),
                ("message", models.TextField(blank=True)),
                ("priority", models.CharField(choices=[("info", "Information"), ("warning", "Attention"), ("urgent", "Urgent")], default="info", max_length=30)),
                ("is_read", models.BooleanField(default=False)),
                ("link", models.CharField(blank=True, max_length=240)),
                ("created_at", models.DateTimeField(auto_now_add=True)),
                ("company", models.ForeignKey(on_delete=django.db.models.deletion.CASCADE, related_name="notifications", to="entreprise.company")),
                ("user", models.ForeignKey(blank=True, null=True, on_delete=django.db.models.deletion.SET_NULL, related_name="notifications", to=settings.AUTH_USER_MODEL)),
            ],
            options={
                "verbose_name": "notification",
                "verbose_name_plural": "notifications",
                "db_table": "notifications",
                "ordering": ["-created_at"],
            },
        ),
    ]
