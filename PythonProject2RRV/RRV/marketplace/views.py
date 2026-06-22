from django.shortcuts import render, redirect, get_object_or_404
from django.contrib.auth.decorators import login_required
from django.contrib import messages
from django.http import JsonResponse
from marketplace.models import Product, Category, Comment
from .forms import ProductForm
from notifications.models import Notification


# 1. ACCUEIL
def home(request):
    """Affiche les articles Marketplace."""
    products = Product.objects.filter(product_type='MARKETPLACE').select_related('seller').order_by('-created_at')
    return render(request, 'marketplace/home.html', {'products': products})


# 2. DÉTAIL DU PRODUIT
def product_detail(request, slug):
    """Affiche les détails d'un produit."""
    product = get_object_or_404(Product.objects.select_related('seller'), slug=slug)
    comments = product.comments.all().order_by('-created_at')

    similar_products = Product.objects.filter(
        category=product.category,
        product_type='MARKETPLACE'
    ).exclude(id=product.id)[:4]

    return render(request, 'marketplace/product_detail.html', {
        'product': product,
        'comments': comments,
        'similar_products': similar_products
    })


# 3. FILTRE PAR CATÉGORIE
def category_products(request, slug):
    category = get_object_or_404(Category, slug=slug)
    products = Product.objects.filter(category=category, product_type='MARKETPLACE').select_related('seller')
    return render(request, 'marketplace/category_products.html', {
        'category': category,
        'products': products
    })


# 4. RECHERCHE
def search_products(request):
    query = request.GET.get('q')
    if query:
        products = Product.objects.filter(title__icontains=query, product_type='MARKETPLACE').select_related('seller')
    else:
        products = Product.objects.filter(product_type='MARKETPLACE').select_related('seller')
    return render(request, 'marketplace/search_results.html', {'products': products, 'query': query})


# 5. DASHBOARD VENDEUR
@login_required
def dashboard(request):
    user_products = request.user.marketplace_items.filter(product_type='MARKETPLACE').order_by('-created_at')
    return render(request, 'marketplace/dashboard.html', {'products': user_products})


# 6. CRÉATION PRODUIT
@login_required
def create_product(request):
    if request.method == 'POST':
        form = ProductForm(request.POST, request.FILES)
        if form.is_valid():
            product = form.save(commit=False)
            product.seller = request.user
            product.product_type = 'MARKETPLACE'
            product.save()
            messages.success(request, "Votre article a été mis en vente !")
            return redirect('marketplace:dashboard')
    else:
        form = ProductForm()
    return render(request, 'marketplace/create_product.html', {'form': form})


# 7. MODIFICATION PRODUIT
@login_required
def edit_product(request, slug):
    product = get_object_or_404(Product, slug=slug, seller=request.user)
    if request.method == 'POST':
        form = ProductForm(request.POST, request.FILES, instance=product)
        if form.is_valid():
            form.save()
            messages.success(request, "Votre article a été mis à jour !")
            return redirect('marketplace:dashboard')
    else:
        form = ProductForm(instance=product)
    return render(request, 'marketplace/create_product.html', {'form': form, 'edit_mode': True})


# 8. SYSTÈME DE LIKES (Dynamique / AJAX)
@login_required
def toggle_like(request, product_id):
    product = get_object_or_404(Product, id=product_id)

    # Vérifie si l'utilisateur a déjà liké
    if request.user in product.likes.all():
        product.likes.remove(request.user)
        liked = False
    else:
        product.likes.add(request.user)
        liked = True

        # Ajout de la notification pour le "Like"
        if request.user != product.seller:
            Notification.objects.create(
                recipient=product.seller,
                notification_type='like',
                text=f"{request.user.username} a aimé votre article : {product.title}",
                link_id=product.slug
            )

    new_count = product.likes.count()

    return JsonResponse({
        'status': 'success',
        'liked': liked,
        'new_count': new_count
    })


# 9. AJOUT DE COMMENTAIRE
@login_required
def add_comment(request, product_id):
    product = get_object_or_404(Product, id=product_id)
    if request.method == 'POST':
        content = request.POST.get('content')
        if content:
            Comment.objects.create(product=product, user=request.user, content=content)
            if request.user != product.seller:
                Notification.objects.create(
                    recipient=product.seller,
                    notification_type='comment',
                    text=f"{request.user.username} a commenté votre article : {product.title}",
                    link_id=product.slug
                )
    return redirect('marketplace:product_detail', slug=product.slug)


# 10. SUCCÈS PAIEMENT
@login_required
def payment_success(request):
    """
    Page de confirmation après paiement réussi.
    Transmet l'utilisateur actuel au template pour afficher son nom.
    """
    return render(request, 'marketplace/payment_success.html', {
        'user': request.user
    })