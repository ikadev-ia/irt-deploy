from django.contrib import admin
from django.urls import path, include
from django.conf import settings
from django.conf.urls.static import static

urlpatterns = [
    # 1. Administration Django
    path('admin/', admin.site.urls),

    # 2. Authentification & Profil
    path('accounts/', include('django.contrib.auth.urls')),
    path('accounts/', include('accounts.urls')),
    path('profile/', include('accounts.urls')),

    # 3. Modules spécifiques
    path('shop/', include('shop.urls', namespace='shop')),
    path('cart/', include('cart.urls', namespace='cart')), # Ajouté ici

    # Intégration avec namespace
    path('payments/', include('payments.urls', namespace='payments')),

    path('messages/', include('messaging.urls')),
    path('notifications/', include('notifications.urls')),
    path('reviews/', include('reviews.urls')),

    # 4. Marketplace (Accueil et listes de produits)
    path('', include('marketplace.urls')),
]

# CONFIGURATION POUR LE DÉVELOPPEMENT
if settings.DEBUG:
    # Service des fichiers MEDIA et STATIC
    urlpatterns += static(settings.MEDIA_URL, document_root=settings.MEDIA_ROOT)
    urlpatterns += static(settings.STATIC_URL, document_root=settings.STATIC_ROOT)