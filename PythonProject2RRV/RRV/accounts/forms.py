from django import forms
from django.contrib.auth.forms import UserCreationForm
from .models import User


class RegisterForm(UserCreationForm):
    """
    Formulaire d'inscription complet pour Revina.
    Inclut les champs d'authentification et les informations de profil.
    """
    email = forms.EmailField(required=True, label="Adresse email")

    class Meta(UserCreationForm.Meta):
        model = User
        fields = UserCreationForm.Meta.fields + (
            'email',
            'photo',
            'phone',
            'city',
            'bio'
        )

    def __init__(self, *args, **kwargs):
        super(RegisterForm, self).__init__(*args, **kwargs)
        self.fields['city'].widget.attrs['placeholder'] = 'Ex: Bamako'
        self.fields['phone'].widget.attrs['placeholder'] = 'Ex: +223 ...'


class ProfileUpdateForm(forms.ModelForm):
    """
    Formulaire permettant à l'utilisateur de modifier son profil Revina.
    """

    class Meta:
        model = User
        # On utilise les champs réels de ton modèle User
        fields = ['first_name', 'last_name', 'email', 'phone', 'city', 'photo', 'bio']

        widgets = {
            'first_name': forms.TextInput(attrs={'class': 'form-control'}),
            'last_name': forms.TextInput(attrs={'class': 'form-control'}),
            'email': forms.EmailInput(attrs={'class': 'form-control'}),
            'phone': forms.TextInput(attrs={'class': 'form-control', 'placeholder': 'Ex: +223 ...'}),
            'city': forms.TextInput(attrs={'class': 'form-control', 'placeholder': 'Ex: Bamako'}),
            'photo': forms.FileInput(attrs={'class': 'form-control'}),
            'bio': forms.Textarea(attrs={'class': 'form-control', 'rows': 3}),
        }