from django.urls import reverse

from .navigation import NAVIGATION


def admin_context(request):
    items = []
    for key, label, route_name in NAVIGATION:
        if key == "dashboard":
            url = reverse(route_name)
        else:
            url = reverse(route_name, kwargs={"slug": key})
        items.append({"key": key, "label": label, "url": url})
    return {"admin_navigation": items}
