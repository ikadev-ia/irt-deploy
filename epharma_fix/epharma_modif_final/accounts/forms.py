from django import forms
from django.contrib.auth.forms import UserCreationForm, AuthenticationForm
from .models import Utilisateur

class InscriptionForm(UserCreationForm):
    first_name = forms.CharField(label='Prénom', max_length=50)
    last_name = forms.CharField(label='Nom', max_length=50)
    email = forms.EmailField(label='Email')
    telephone = forms.CharField(label='Téléphone', max_length=20)
    adresse = forms.CharField(label='Adresse de livraison', widget=forms.Textarea(attrs={'rows': 2}))

    class Meta:
        model = Utilisateur
        fields = ['username', 'first_name', 'last_name', 'email', 'telephone', 'adresse', 'password1', 'password2']

class ConnexionForm(AuthenticationForm):
    username = forms.CharField(label='Nom d\'utilisateur')
    password = forms.CharField(label='Mot de passe', widget=forms.PasswordInput)

class ProfilForm(forms.ModelForm):
    class Meta:
        model = Utilisateur
        fields = ['first_name', 'last_name', 'email', 'telephone', 'adresse', 'photo', 'date_naissance']
        widgets = {
            'adresse': forms.Textarea(attrs={'rows': 2}),
            'date_naissance': forms.DateInput(attrs={'type': 'date'}),
        }
