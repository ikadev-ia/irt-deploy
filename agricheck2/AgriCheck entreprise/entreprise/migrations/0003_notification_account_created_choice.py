from django.db import migrations, models


class Migration(migrations.Migration):
    dependencies = [
        ("entreprise", "0002_company_registration_fields"),
    ]

    operations = [
        migrations.AlterField(
            model_name="notification",
            name="type",
            field=models.CharField(
                choices=[
                    ("account_created", "Inscription enregistree"),
                    ("analysis_available", "Nouvelle analyse disponible"),
                    ("report_published", "Rapport publie"),
                    ("high_risk", "Risque eleve detecte"),
                    ("urgent_treatment", "Traitement urgent"),
                    ("invoice_available", "Facture disponible"),
                ],
                max_length=40,
            ),
        ),
    ]
