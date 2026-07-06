from django import forms
from django.contrib.auth import get_user_model
from django.contrib.auth.forms import AuthenticationForm, UsernameField
from django.db import transaction

from .models import Company


def format_xof(amount):
    return f"{amount:,}".replace(",", " ")

class EmailAuthenticationForm(AuthenticationForm):
    username = UsernameField(
        label="Email",
        widget=forms.EmailInput(
            attrs={
                "autofocus": True,
                "class": "form-control form-control-lg",
                "placeholder": "adresse@email.com",
                "autocomplete": "email",
            }
        ),
    )
    password = forms.CharField(
        label="Mot de passe",
        strip=False,
        widget=forms.PasswordInput(
            attrs={
                "class": "form-control form-control-lg",
                "placeholder": "Votre mot de passe",
                "autocomplete": "current-password",
            }
        ),
    )


class CompanyRegistrationForm(forms.Form):
    SUBSCRIPTION_CHOICES = [
        (
            plan.value,
            (
                f"{plan.label} - {format_xof(Company.SUBSCRIPTION_PLAN_DETAILS[plan.value]['price_xof'])} "
                f"FCFA/mois, deplacement inclus {Company.SUBSCRIPTION_PLAN_DETAILS[plan.value]['travel_limit_km']} km"
            ),
        )
        for plan in Company.SubscriptionPlan
    ]

    company_name = forms.CharField(
        label="Nom de l'exploitation",
        max_length=180,
        widget=forms.TextInput(attrs={"class": "form-control", "placeholder": "Ex: Agro Mali SA"}),
    )
    manager_name = forms.CharField(
        label="Responsable",
        max_length=180,
        widget=forms.TextInput(attrs={"class": "form-control", "placeholder": "Nom du responsable"}),
    )
    phone = forms.CharField(
        label="Telephone",
        max_length=40,
        widget=forms.TextInput(attrs={"class": "form-control", "placeholder": "+223 ..."}),
    )
    email = forms.EmailField(
        label="Email professionnel",
        widget=forms.EmailInput(attrs={"class": "form-control", "placeholder": "contact@client.com"}),
    )
    address = forms.CharField(
        label="Adresse",
        widget=forms.Textarea(attrs={"class": "form-control", "rows": 3, "placeholder": "Adresse du client"}),
    )
    hectares = forms.DecimalField(
        label="Nombre d'hectares",
        min_value=1,
        max_digits=12,
        decimal_places=2,
        widget=forms.NumberInput(attrs={"class": "form-control", "placeholder": "Ex: 250", "step": "0.01"}),
    )
    subscription_type = forms.ChoiceField(
        label="Abonnement souhaite",
        choices=SUBSCRIPTION_CHOICES,
        help_text="Le prix comprend le deplacement Agricheck dans la limite indiquee.",
        widget=forms.Select(attrs={"class": "form-select"}),
    )
    payment_method = forms.ChoiceField(
        label="Moyen de paiement",
        choices=Company.PaymentMethod.choices,
        widget=forms.Select(attrs={"class": "form-select"}),
    )
    password = forms.CharField(
        label="Mot de passe",
        min_length=8,
        widget=forms.PasswordInput(attrs={"class": "form-control", "autocomplete": "new-password"}),
    )
    password_confirm = forms.CharField(
        label="Confirmer le mot de passe",
        widget=forms.PasswordInput(attrs={"class": "form-control", "autocomplete": "new-password"}),
    )

    @property
    def plan_cards(self):
        cards = []
        for plan in Company.SubscriptionPlan:
            details = Company.SUBSCRIPTION_PLAN_DETAILS[plan.value]
            cards.append(
                {
                    "name": plan.label,
                    "price": f"{format_xof(details['price_xof'])} FCFA / mois",
                    "travel": f"Deplacement inclus jusqu'a {details['travel_limit_km']} km",
                    "summary": details["summary"],
                }
            )
        return cards

    def clean_email(self):
        email = self.cleaned_data["email"].lower()
        User = get_user_model()
        if User.objects.filter(email__iexact=email).exists():
            raise forms.ValidationError("Un compte existe deja avec cet email.")
        return email

    def clean(self):
        cleaned = super().clean()
        password = cleaned.get("password")
        password_confirm = cleaned.get("password_confirm")
        if password and password_confirm and password != password_confirm:
            self.add_error("password_confirm", "Les mots de passe ne correspondent pas.")
        return cleaned

    @transaction.atomic
    def save(self):
        data = self.cleaned_data
        plan_details = Company.SUBSCRIPTION_PLAN_DETAILS[data["subscription_type"]]
        company = Company.objects.create(
            name=data["company_name"],
            manager_name=data["manager_name"],
            phone=data["phone"],
            email=data["email"],
            address=data["address"],
            hectares=data["hectares"],
            subscription_type=data["subscription_type"],
            subscription_price_xof=plan_details["price_xof"],
            travel_included=True,
            travel_limit_km=plan_details["travel_limit_km"],
            payment_method=data["payment_method"],
        )

        User = get_user_model()
        username_base = data["email"].split("@", 1)[0]
        username = username_base
        index = 1
        while User.objects.filter(username=username).exists():
            index += 1
            username = f"{username_base}{index}"

        user = User.objects.create_user(
            username=username,
            email=data["email"],
            password=data["password"],
            first_name=data["manager_name"][:150],
            phone=data["phone"],
            role=User.Role.CLIENT,
            company=company,
        )
        return user
