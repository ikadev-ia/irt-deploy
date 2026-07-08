from django.db import migrations, models


class Migration(migrations.Migration):
    dependencies = [
        ("entreprise", "0004_company_subscription_pricing"),
    ]

    operations = [
        migrations.AlterModelOptions(
            name="company",
            options={
                "ordering": ["name"],
                "verbose_name": "client",
                "verbose_name_plural": "clients",
            },
        ),
        migrations.AlterField(
            model_name="company",
            name="name",
            field=models.CharField(max_length=180, verbose_name="nom exploitation"),
        ),
        migrations.AlterField(
            model_name="company",
            name="payment_method",
            field=models.CharField(
                blank=True,
                choices=[
                    ("bank_transfer", "Virement bancaire"),
                    ("mobile_money", "Mobile Money"),
                    ("card", "Carte bancaire"),
                    ("check", "Cheque client"),
                ],
                max_length=40,
                verbose_name="moyen de paiement",
            ),
        ),
        migrations.AlterField(
            model_name="company",
            name="subscription_type",
            field=models.CharField(
                blank=True,
                choices=[
                    ("starter", "Suivi initial client"),
                    ("growth", "Croissance Drone + IA"),
                    ("large", "Grandes exploitations"),
                ],
                default="starter",
                max_length=120,
                verbose_name="type d'abonnement",
            ),
        ),
        migrations.AddField(
            model_name="treatment",
            name="frequency",
            field=models.CharField(blank=True, max_length=180, verbose_name="frequence"),
        ),
        migrations.AlterField(
            model_name="notification",
            name="type",
            field=models.CharField(
                choices=[
                    ("account_created", "Inscription enregistree"),
                    ("mission_completed", "Nouvelle mission terminee"),
                    ("analysis_available", "Nouvelle analyse disponible"),
                    ("report_published", "Rapport publie"),
                    ("high_risk", "Risque eleve detecte"),
                    ("urgent_treatment", "Traitement recommande"),
                    ("treatment_recommended", "Traitement recommande"),
                    ("invoice_available", "Facture disponible"),
                ],
                max_length=40,
            ),
        ),
        migrations.AlterField(
            model_name="user",
            name="role",
            field=models.CharField(
                choices=[
                    ("client", "Client Agricheck"),
                    ("admin", "Admin Agricheck"),
                ],
                default="client",
                max_length=30,
            ),
        ),
    ]
