from django.shortcuts import render, get_object_or_404
from shop.models import ShopProduct  # Modèle spécifique utilisé pour la boutique


def shop_index(request):
    """
    Vue principale de la boutique REVINA.
    Récupère tous les produits via ShopProduct.
    """
    query = request.GET.get('q')

    # On récupère tous les objets du modèle ShopProduct
    products = ShopProduct.objects.all().order_by('-created_at')

    # Filtrage par recherche si une requête est présente
    if query:
        products = products.filter(name__icontains=query)

    context = {
        'products': products,
        'query': query,
    }
    return render(request, 'shop/index.html', context)


def boutique_view(request):
    """
    Redirection vers la vue shop_index.
    """
    return shop_index(request)


def article_detail(request, pk):
    """
    Affiche les détails complets d'un article spécifique.
    """
    article = get_object_or_404(ShopProduct, pk=pk)
    return render(request, 'shop/article_detail.html', {'article': article})