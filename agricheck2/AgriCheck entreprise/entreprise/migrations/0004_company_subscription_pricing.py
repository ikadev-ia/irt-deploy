from django.db import migrations, models


PLAN_DETAILS = {
    "starter": (75000, 30),
    "growth": (150000, 50),
    "large": (300000, 80),
}


def update_existing_subscriptions(apps, schema_editor):
    Company = apps.get_model("entreprise", "Company")
    legacy_mapping = {
        "Suivi Grandes Exploitations": "large",
        "Drone + Analyses IA": "growth",
        "Entreprise Multi-sites": "large",
    }

    for company in Company.objects.all():
        plan = legacy_mapping.get(company.subscription_type, company.subscription_type or "starter")
        if plan not in PLAN_DETAILS:
            plan = "starter"
        price_xof, travel_limit_km = PLAN_DETAILS[plan]
        company.subscription_type = plan
        company.subscription_price_xof = price_xof
        company.travel_included = True
        company.travel_limit_km = travel_limit_km
        company.save(
            update_fields=[
                "subscription_type",
                "subscription_price_xof",
                "travel_included",
                "travel_limit_km",
            ]
        )


class Migration(migrations.Migration):
    dependencies = [
        ("entreprise", "0003_notification_account_created_choice"),
    ]

    operations = [
        migrations.AddField(
            model_name="company",
            name="subscription_price_xof",
            field=models.PositiveIntegerField(default=75000, verbose_name="prix mensuel"),
        ),
        migrations.AddField(
            model_name="company",
            name="travel_included",
            field=models.BooleanField(default=True, verbose_name="deplacement inclus"),
        ),
        migrations.AddField(
            model_name="company",
            name="travel_limit_km",
            field=models.PositiveIntegerField(default=30, verbose_name="kilometres inclus"),
        ),
        migrations.RunPython(update_existing_subscriptions, migrations.RunPython.noop),
        migrations.AlterField(
            model_name="company",
            name="subscription_type",
            field=models.CharField(
                blank=True,
                choices=[
                    ("starter", "Debut entreprise"),
                    ("growth", "Croissance Drone + IA"),
                    ("large", "Grandes exploitations"),
                ],
                default="starter",
                max_length=120,
                verbose_name="type d'abonnement",
            ),
        ),
    ]
