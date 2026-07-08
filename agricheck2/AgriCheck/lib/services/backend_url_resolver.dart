import '../models/api_config.dart';
import 'api_exceptions.dart';

class BackendUrlResolver {
  static const List<String> localFallbackUrls = <String>[
    'https://admin.agricheck-mali.com',
  ];

  static List<Uri> baseUris(ApiConfig config) {
    final configured = baseUri(config);
    final urls = <String>[configured.toString()];
    if (isLocalDevUrl(configured)) {
      urls.addAll(localFallbackUrls);
    }
    return urls
        .map((url) => Uri.parse(url.trim()))
        .where((uri) => uri.hasScheme && uri.host.trim().isNotEmpty)
        .fold<List<Uri>>(<Uri>[], (unique, uri) {
          final normalized = uri.toString();
          if (!unique.any((item) => item.toString() == normalized)) {
            unique.add(uri);
          }
          return unique;
        });
  }

  static Uri baseUri(ApiConfig config) {
    if (!config.hasBackend) {
      throw const ConfigException(
        'Connexion Agricheck indisponible. Les modules compatibles passent en mode local.',
      );
    }
    final uri = Uri.parse(config.backendBaseUrl.trim());
    if (!uri.hasScheme || uri.host.trim().isEmpty) {
      throw const ConfigException(
        'URL Agricheck invalide. Exemple: http://172.20.10.3:8000',
      );
    }
    return uri;
  }

  static bool isLocalDevUrl(Uri uri) {
    return uri.scheme == 'http' && (uri.port == 8000 || uri.port == 8090);
  }

  static String networkErrorMessage(List<Uri> bases, Object? error) {
    final tried = bases.map((uri) => uri.toString()).join(', ');
    final detail = error == null ? '' : ' Detail: $error';
    return 'Connexion Agricheck indisponible. Mode local active si possible. URLs testees: $tried.$detail';
  }
}
