from django.contrib.auth import get_user_model
from django.contrib.auth.backends import ModelBackend


class EmailOrUsernameBackend(ModelBackend):
    """Authenticate client users with their email address or username."""

    def authenticate(self, request, username=None, password=None, **kwargs):
        UserModel = get_user_model()
        identifier = username or kwargs.get(UserModel.USERNAME_FIELD)
        if not identifier or not password:
            return None

        try:
            user = UserModel.objects.get(email__iexact=identifier)
        except UserModel.DoesNotExist:
            try:
                user = UserModel.objects.get(username__iexact=identifier)
            except UserModel.DoesNotExist:
                UserModel().set_password(password)
                return None

        if user.check_password(password) and self.user_can_authenticate(user):
            return user
        return None
