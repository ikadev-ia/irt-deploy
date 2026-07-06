from django.db import migrations, models


class Migration(migrations.Migration):
    dependencies = [
        ("entreprise", "0001_initial"),
    ]

    operations = [
        migrations.AddField(
            model_name="company",
            name="payment_method",
            field=models.CharField(
                blank=True,
                choices=[
                    ("bank_transfer", "Virement bancaire"),
                    ("mobile_money", "Mobile Money"),
                    ("card", "Carte bancaire"),
                    ("check", "Cheque entreprise"),
                ],
                max_length=40,
                verbose_name="moyen de paiement",
            ),
        ),
        migrations.AddField(
            model_name="company",
            name="registration_status",
            field=models.CharField(
                choices=[
                    ("pending", "En attente de configuration"),
                    ("active", "Suivi actif"),
                    ("suspended", "Suspendu"),
                ],
                default="pending",
                max_length=30,
                verbose_name="statut inscription",
            ),
        ),
    ]
