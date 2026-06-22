from django.contrib import admin
from django.contrib.auth.admin import UserAdmin
from .models import User


@admin.register(User)
class CustomUserAdmin(UserAdmin):
    # On définit ici quels champs s'affichent dans l'édition du profil admin
    fieldsets = UserAdmin.fieldsets + (
        ('Informations Revina', {'fields': ('photo', 'phone', 'city', 'bio')}),
    )

    # On définit les colonnes visibles dans la liste des utilisateurs
    list_display = ['username', 'email', 'phone', 'city', 'is_staff']