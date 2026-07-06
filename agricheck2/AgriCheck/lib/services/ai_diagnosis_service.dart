import 'dart:async';
import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:image_picker/image_picker.dart';

import '../models/api_config.dart';
import '../models/diagnosis_result.dart';
import 'api_exceptions.dart';
import 'backend_url_resolver.dart';

class AiDiagnosisService {
  AiDiagnosisService({http.Client? httpClient})
    : _httpClient = httpClient ?? http.Client();

  final http.Client _httpClient;
  static const Duration _timeout = Duration(seconds: 90);
  static const String _diagnosisConfigMessage =
      'Diagnostic IA non disponible: demarrez Agricheck Admin et configurez PLANT_ID_API_KEY dans le fichier .env du serveur.';

  Future<DiagnosisResult> diagnose({
    required XFile image,
    required ApiConfig config,
    String authToken = '',
  }) async {
    if (!config.hasBackend) {
      throw const ConfigException(_diagnosisConfigMessage);
    }
    if (authToken.trim().isEmpty || authToken.startsWith('local-')) {
      throw const ConfigException(
        'Connectez-vous a Agricheck avant de lancer une analyse.',
      );
    }
    return _diagnoseWithBackend(
      image: image,
      config: config,
      authToken: authToken,
    );
  }

  Future<DiagnosisResult> _diagnoseWithBackend({
    required XFile image,
    required ApiConfig config,
    required String authToken,
  }) async {
    Object? lastNetworkError;
    final bases = BackendUrlResolver.baseUris(config);
    final bytes = await image.readAsBytes();
    for (final baseUri in bases) {
      final endpoint = baseUri.resolve('/api/app/diagnostics/');
      final request = http.MultipartRequest('POST', endpoint)
        ..fields['latitude'] = config.latitude.toString()
        ..fields['longitude'] = config.longitude.toString()
        ..files.add(
          http.MultipartFile.fromBytes(
            'image',
            bytes,
            filename: image.name.isEmpty ? 'agricheck_leaf.jpg' : image.name,
          ),
        );
      request.headers['Authorization'] = 'Bearer ${authToken.trim()}';
      try {
        final response = await http.Response.fromStream(
          await _httpClient.send(request).timeout(_timeout),
        ).timeout(_timeout);
        if (response.statusCode < 200 || response.statusCode >= 300) {
          throw RemoteApiException(
            _serverMessage(response.body) ?? _diagnosisConfigMessage,
            statusCode: response.statusCode,
          );
        }
        return DiagnosisResult.fromJson(
          jsonDecode(response.body) as Map<String, dynamic>,
        );
      } on TimeoutException catch (error) {
        lastNetworkError = error;
      } on http.ClientException catch (error) {
        lastNetworkError = error;
      }
    }
    throw RemoteApiException(
      BackendUrlResolver.networkErrorMessage(bases, lastNetworkError),
    );
  }

  String? _serverMessage(String body) {
    if (body.trim().isEmpty) {
      return null;
    }
    try {
      final decoded = jsonDecode(body);
      if (decoded is Map<String, dynamic>) {
        final value =
            decoded['detail'] ?? decoded['message'] ?? decoded['error'];
        return value?.toString();
      }
    } catch (_) {
      return null;
    }
    return null;
  }
}
