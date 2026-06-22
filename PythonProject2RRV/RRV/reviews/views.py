from django.shortcuts import render, redirect
from django.contrib import messages


def confirmer_paiement(request):
    if request.method == "POST":
        # Récupération des données envoyées par ton formulaire
        nom_carte = request.POST.get('nom_carte')
        num_carte = request.POST.get('numero_carte')
        # ... récupère les autres champs (CVV, Date, etc.)

        # --- CONDITION DE VALIDATION ---
        # Si un des champs obligatoires est vide ou contient uniquement des espaces
        if not nom_carte or not num_carte or nom_carte.strip() == "" or num_carte.strip() == "":
            messages.error(request,
                           "Erreur : Toutes les informations de paiement doivent être remplies pour confirmer l'achat.")
            return redirect('nom_de_ton_url_de_paiement')  # Remplace par le nom de ton URL de formulaire

        # Si tout est rempli, on arrive ici et on affiche le succès
        return render(request, 'paiement/succes.html')

    # Si c'est un accès normal (GET), on affiche simplement la page de paiement
    return render(request, 'paiement/formulaire.html')