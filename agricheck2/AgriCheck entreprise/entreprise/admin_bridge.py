import json
from urllib import error, parse, request

from django.conf import settings


def admin_base_url():
    # En production Docker, on utilise le nom du service défini dans docker-compose.yml
    # Sinon on utilise la variable d'environnement AGRICHECK_ADMIN_API_BASE_URL
    return getattr(settings, "AGRICHECK_ADMIN_API_BASE_URL", "http://agricheck-admin:8090").rstrip("/")


def post_json(path, payload):
    data = json.dumps(payload).encode("utf-8")
    req = request.Request(
        f"{admin_base_url()}{path}",
        data=data,
        headers={"Content-Type": "application/json"},
        method="POST",
    )
    try:
        with request.urlopen(req, timeout=4) as response:
            return json.loads(response.read().decode("utf-8") or "{}")
    except (error.URLError, TimeoutError, json.JSONDecodeError):
        return None


def get_json(path, query):
    url = f"{admin_base_url()}{path}?{parse.urlencode(query)}"
    try:
        with request.urlopen(url, timeout=4) as response:
            return json.loads(response.read().decode("utf-8") or "{}")
    except (error.URLError, TimeoutError, json.JSONDecodeError):
        return None


def sync_company_registration(user):
    company = user.company
    if not company:
        return None
    return post_json(
        "/api/client/companies/register/",
        {
            "client_id": company.pk,
            "company_name": company.name,
            "manager_name": company.manager_name,
            "phone": company.phone,
            "email": company.email,
            "address": company.address,
            "hectares": str(company.hectares),
            "subscription_type": company.subscription_type,
            "subscription_price_xof": company.subscription_price_xof,
            "payment_method": company.payment_method,
        },
    )


def fetch_admin_reports(company):
    data = fetch_admin_client_data(company)
    reports = data.get("reports", [])
    if not isinstance(reports, list):
        return []
    return reports


def fetch_admin_invoices(company):
    data = fetch_admin_client_data(company)
    invoices = data.get("invoices", [])
    if not isinstance(invoices, list):
        return []
    return invoices


def fetch_admin_treatments(company):
    data = fetch_admin_client_data(company)
    treatments = data.get("treatments", [])
    if not isinstance(treatments, list):
        return []
    return treatments


def fetch_admin_client_data(company):
    if not company:
        return {}
    data = get_json(
        "/api/client/reports/",
        {
            "company_email": company.email,
            "company_phone": company.phone,
            "company_name": company.name,
        },
    )
    if not data:
        return {}
    return data


def find_admin_report(company, report_id):
    report_id = str(report_id)
    for report in fetch_admin_reports(company):
        if str(report.get("id")) == report_id:
            return report
    return None
