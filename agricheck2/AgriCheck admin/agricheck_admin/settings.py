import os
from pathlib import Path

try:
    from dotenv import load_dotenv
except ImportError:  # pragma: no cover
    load_dotenv = None


BASE_DIR = Path(__file__).resolve().parent.parent

if load_dotenv:
    load_dotenv(BASE_DIR / ".env")


SECRET_KEY = os.getenv("SECRET_KEY", "dev-only-agricheck-admin-secret-key")
DEBUG = os.getenv("DEBUG", "True").lower() in {"1", "true", "yes", "on"}

ALLOWED_HOSTS = [
    host.strip()
    for host in os.getenv("ALLOWED_HOSTS", "127.0.0.1,localhost,10.0.2.2,10.0.3.2,172.20.10.3,*").split(",")
    if host.strip()
]
if DEBUG and "testserver" not in ALLOWED_HOSTS:
    ALLOWED_HOSTS.append("testserver")

CSRF_TRUSTED_ORIGINS = [
    origin.strip()
    for origin in os.getenv("CSRF_TRUSTED_ORIGINS", "").split(",")
    if origin.strip()
]


INSTALLED_APPS = [
    "django.contrib.admin",
    "django.contrib.auth",
    "django.contrib.contenttypes",
    "django.contrib.sessions",
    "django.contrib.messages",
    "django.contrib.staticfiles",
    "operations",
]

MIDDLEWARE = [
    "django.middleware.security.SecurityMiddleware",
    "django.contrib.sessions.middleware.SessionMiddleware",
    "operations.middleware.SimpleApiCorsMiddleware",
    "django.middleware.common.CommonMiddleware",
    "django.middleware.csrf.CsrfViewMiddleware",
    "django.contrib.auth.middleware.AuthenticationMiddleware",
    "django.contrib.messages.middleware.MessageMiddleware",
    "django.middleware.clickjacking.XFrameOptionsMiddleware",
]

ROOT_URLCONF = "agricheck_admin.urls"

TEMPLATES = [
    {
        "BACKEND": "django.template.backends.django.DjangoTemplates",
        "DIRS": [BASE_DIR / "templates"],
        "APP_DIRS": True,
        "OPTIONS": {
            "context_processors": [
                "django.template.context_processors.debug",
                "django.template.context_processors.request",
                "django.contrib.auth.context_processors.auth",
                "django.contrib.messages.context_processors.messages",
                "operations.context_processors.admin_context",
            ],
        },
    },
]

WSGI_APPLICATION = "agricheck_admin.wsgi.application"


DB_ENGINE = os.getenv("DB_ENGINE", "django.db.backends.sqlite3")
if DB_ENGINE == "django.db.backends.sqlite3":
    DATABASES = {
        "default": {
            "ENGINE": DB_ENGINE,
            "NAME": os.getenv("DB_NAME", BASE_DIR / "db.sqlite3"),
        }
    }
else:
    DATABASES = {
        "default": {
            "ENGINE": DB_ENGINE,
            "NAME": os.getenv("DB_NAME", "agricheck_admin"),
            "USER": os.getenv("DB_USER", "root"),
            "PASSWORD": os.getenv("DB_PASSWORD", ""),
            "HOST": os.getenv("DB_HOST", "127.0.0.1"),
            "PORT": os.getenv("DB_PORT", "3306"),
            "OPTIONS": {"charset": "utf8mb4"},
        }
    }


AUTH_PASSWORD_VALIDATORS = [
    {"NAME": "django.contrib.auth.password_validation.UserAttributeSimilarityValidator"},
    {"NAME": "django.contrib.auth.password_validation.MinimumLengthValidator"},
    {"NAME": "django.contrib.auth.password_validation.CommonPasswordValidator"},
    {"NAME": "django.contrib.auth.password_validation.NumericPasswordValidator"},
]


LANGUAGE_CODE = "fr-fr"
TIME_ZONE = "Africa/Bamako"
USE_I18N = True
USE_TZ = True

STATIC_URL = "static/"
STATICFILES_DIRS = [BASE_DIR / "static"]
STATIC_ROOT = BASE_DIR / "staticfiles"

MEDIA_URL = "media/"
MEDIA_ROOT = BASE_DIR / "media"

DEFAULT_AUTO_FIELD = "django.db.models.BigAutoField"

LOGIN_URL = "login"
LOGIN_REDIRECT_URL = "operations:dashboard"
LOGOUT_REDIRECT_URL = "login"

AGRICHECK_MOBILE_PROJECT_PATH = os.getenv(
    "AGRICHECK_MOBILE_PROJECT_PATH",
    r"C:\Users\kader diarra\StudioProjects\AgriCheck",
)
AGRICHECK_CLIENT_PROJECT_PATH = os.getenv(
    "AGRICHECK_CLIENT_PROJECT_PATH",
    r"C:\Users\kader diarra\PycharmProjects\AgriCheck entreprise",
)

PLANTNET_API_KEY = os.getenv("PLANTNET_API_KEY") or os.getenv("AGRICHECK_PLANTNET_KEY", "")
PLANTNET_PROJECT = os.getenv("PLANTNET_PROJECT", "all")
PLANT_ID_API_KEY = (
    os.getenv("PLANT_ID_API_KEY")
    or os.getenv("AGRICHECK_PLANT_ID_KEY")
    or os.getenv("AGRICHECK_HEALTH_KEY")
    or os.getenv("KINDWISE_API_KEY")
    or ""
)
KINDWISE_API_KEY = PLANT_ID_API_KEY
CROP_HEALTH_API_KEY = os.getenv("CROP_HEALTH_API_KEY") or os.getenv("AGRICHECK_CROP_HEALTH_KEY", "")
DRONE_API_KEY = os.getenv("DRONE_API_KEY", "")
