from pathlib import Path
from django.contrib.messages import constants as messages

# 1. Chemins de base
BASE_DIR = Path(__file__).resolve().parent.parent

# 2. Sécurité & Debug
SECRET_KEY = 'revina-secret-key-super-secure'
DEBUG = True
ALLOWED_HOSTS = ['127.0.0.1', 'localhost', 'shop-revina.com','www.shop-revina.com', '31.207.38.14']

# 3. Applications
INSTALLED_APPS = [
    'django.contrib.admin',
    'django.contrib.auth',
    'django.contrib.contenttypes',
    'django.contrib.sessions',
    'django.contrib.messages',
    'django.contrib.staticfiles',
    'crispy_forms',
    'crispy_bootstrap5',
    'channels',
    'accounts',
    'marketplace',
    'shop',
    'payments',
    'messaging',
    'notifications',
    'reviews',
     'cart',
    'products',
]

# 4. Configuration Utilisateur
AUTH_USER_MODEL = 'accounts.User'

# 5. Configuration Crispy Forms
CRISPY_ALLOWED_TEMPLATE_PACKS = "bootstrap5"
CRISPY_TEMPLATE_PACK = "bootstrap5"

# 6. Middleware
MIDDLEWARE = [
    'django.middleware.security.SecurityMiddleware',
    'django.contrib.sessions.middleware.SessionMiddleware',
    'django.middleware.common.CommonMiddleware',
    'django.middleware.csrf.CsrfViewMiddleware',
    'django.contrib.auth.middleware.AuthenticationMiddleware',
    'django.contrib.messages.middleware.MessageMiddleware',
    'django.middleware.clickjacking.XFrameOptionsMiddleware',
]

ROOT_URLCONF = 'config.urls'

# 7. Templates
TEMPLATES = [
    {
        'BACKEND': 'django.template.backends.django.DjangoTemplates',
        'DIRS': [BASE_DIR / 'templates'],
        'APP_DIRS': True,
        'OPTIONS': {
            'context_processors': [
                'django.template.context_processors.debug',
                'django.template.context_processors.request',
                'django.contrib.auth.context_processors.auth',
                'django.contrib.messages.context_processors.messages',
                'marketplace.context_processors.categories_processor',
                'notifications.context_processors.unread_notifications_count',
                'cart.context_processors.cart_count'
            ],
        },
    },
]

WSGI_APPLICATION = 'config.wsgi.application'

# 8. Base de données
DATABASES = {
    'default': {
        'ENGINE': 'django.db.backends.sqlite3',
        'NAME': BASE_DIR / 'db.sqlite3',
    }
}

# 9. Internationalisation
LANGUAGE_CODE = 'fr-fr'
TIME_ZONE = 'Africa/Bamako'
USE_I18N = True
USE_TZ = True

# 10. Fichiers Statiques
STATIC_URL = '/static/'
STATICFILES_DIRS = [BASE_DIR / "static"]
STATIC_ROOT = BASE_DIR / 'staticfiles'

# 11. Fichiers Médias
MEDIA_URL = '/media/'
MEDIA_ROOT = BASE_DIR / 'media'

# 12. Redirections Authentification
LOGIN_REDIRECT_URL = 'marketplace:home'
LOGOUT_REDIRECT_URL = 'marketplace:home'
LOGIN_URL = 'login'

# 13. Configuration des Tags de Messages
MESSAGE_TAGS = {
    messages.DEBUG: 'secondary',
    messages.INFO: 'info',
    messages.SUCCESS: 'success',
    messages.WARNING: 'warning',
    messages.ERROR: 'danger',
}

# 14. Divers
DEFAULT_AUTO_FIELD = 'django.db.models.BigAutoField'