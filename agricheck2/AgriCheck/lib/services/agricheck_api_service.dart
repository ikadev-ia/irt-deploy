import 'dart:async';
import 'dart:convert';

import 'package:http/http.dart' as http;

import '../models/advice_item.dart';
import '../models/api_config.dart';
import '../models/app_notification.dart';
import '../models/diagnosis_result.dart';
import '../models/user_profile.dart';
import 'api_exceptions.dart';
import 'backend_url_resolver.dart';

class AuthResult {
  const AuthResult({required this.token, required this.user});

  final String token;
  final UserProfile user;
}

class AgricheckApiService {
  AgricheckApiService({http.Client? client})
    : _client = client ?? http.Client();

  static const Duration _timeout = Duration(seconds: 6);

  final http.Client _client;

  Future<AuthResult> login({
    required ApiConfig config,
    required String identifier,
    required String password,
  }) async {
    final body = await _postJson(
      config: config,
      path: '/api/app/auth/login/',
      payload: <String, dynamic>{
        'identifier': identifier,
        'email_or_phone': identifier,
        'password': password,
      },
    );
    return _authResultFromJson(body);
  }

  Future<AuthResult> register({
    required ApiConfig config,
    required String fullName,
    required String phone,
    required String email,
    required String password,
  }) async {
    final body = await _postJson(
      config: config,
      path: '/api/app/auth/register/',
      payload: <String, dynamic>{
        'fullName': fullName,
        'full_name': fullName,
        'phone': phone,
        'email': email,
        'password': password,
      },
    );
    return _authResultFromJson(body);
  }

  Future<void> requestPasswordReset({
    required ApiConfig config,
    required String identifier,
  }) async {
    await _postJson(
      config: config,
      path: '/api/app/auth/password-reset/',
      payload: <String, dynamic>{
        'identifier': identifier,
        'email_or_phone': identifier,
      },
    );
  }

  Future<List<AdviceItem>> fetchAdvice({
    required ApiConfig config,
    required String token,
    String crop = '',
  }) async {
    final query = crop.trim().isEmpty
        ? ''
        : '?crop=${Uri.encodeQueryComponent(crop.trim())}';
    final body = await _getJson(
      config: config,
      path: '/api/app/advice/$query',
      token: token,
    );
    final rows = _extractList(body, 'advice');
    return rows.map((item) => AdviceItem.fromJson(item)).toList();
  }

  Future<List<AppNotification>> fetchNotifications({
    required ApiConfig config,
    required String token,
  }) async {
    final body = await _getJson(
      config: config,
      path: '/api/app/notifications/',
      token: token,
    );
    final rows = _extractList(body, 'notifications');
    return rows.map((item) => AppNotification.fromJson(item)).toList();
  }

  Future<void> syncAnalysis({
    required ApiConfig config,
    required String token,
    required DiagnosisResult result,
  }) async {
    if (token.trim().isEmpty || token.startsWith('local-')) {
      return;
    }
    await _postJson(
      config: config,
      path: '/api/app/analyses/',
      token: token,
      payload: result.toJson(),
    );
  }

  Future<Map<String, dynamic>> _postJson({
    required ApiConfig config,
    required String path,
    required Map<String, dynamic> payload,
    String token = '',
  }) async {
    Object? lastNetworkError;
    final bases = BackendUrlResolver.baseUris(config);
    for (final base in bases) {
      try {
        final response = await _client
            .post(
              base.resolve(path),
              headers: _headers(token),
              body: jsonEncode(payload),
            )
            .timeout(_timeout);
        return _decodeResponse(response);
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

  Future<Map<String, dynamic>> _getJson({
    required ApiConfig config,
    required String path,
    String token = '',
  }) async {
    Object? lastNetworkError;
    final bases = BackendUrlResolver.baseUris(config);
    for (final base in bases) {
      try {
        final response = await _client
            .get(base.resolve(path), headers: _headers(token))
            .timeout(_timeout);
        return _decodeResponse(response);
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

  Map<String, String> _headers(String token) {
    return <String, String>{
      'Content-Type': 'application/json',
      if (token.trim().isNotEmpty) 'Authorization': 'Bearer ${token.trim()}',
    };
  }

  Map<String, dynamic> _decodeResponse(http.Response response) {
    if (response.statusCode < 200 || response.statusCode >= 300) {
      throw RemoteApiException(
        _serverMessage(response.body) ??
            'Connexion Agricheck indisponible pour cette demande.',
        statusCode: response.statusCode,
      );
    }
    if (response.body.trim().isEmpty) {
      return const <String, dynamic>{};
    }
    final decoded = jsonDecode(response.body);
    if (decoded is Map<String, dynamic>) {
      return decoded;
    }
    if (decoded is List) {
      return <String, dynamic>{'data': decoded};
    }
    return const <String, dynamic>{};
  }

  String? _serverMessage(String body) {
    if (body.trim().isEmpty) {
      return null;
    }
    try {
      final decoded = jsonDecode(body);
      if (decoded is Map<String, dynamic>) {
        final value =
            decoded['message'] ??
            decoded['detail'] ??
            decoded['error'] ??
            decoded['errors'];
        if (value == null) {
          return null;
        }
        if (value is String) {
          return value;
        }
        return value.toString();
      }
    } catch (_) {
      return null;
    }
    return null;
  }

  AuthResult _authResultFromJson(Map<String, dynamic> body) {
    final token =
        body['token'] as String? ??
        body['access'] as String? ??
        body['access_token'] as String? ??
        '';
    final userMap = body['user'] as Map<String, dynamic>? ?? body;
    if (token.trim().isEmpty) {
      throw const RemoteApiException('Session Agricheck indisponible.');
    }
    return AuthResult(token: token, user: UserProfile.fromJson(userMap));
  }

  List<Map<String, dynamic>> _extractList(
    Map<String, dynamic> body,
    String preferredKey,
  ) {
    final value = body[preferredKey] ?? body['results'] ?? body['data'];
    if (value is List) {
      return value.whereType<Map<String, dynamic>>().toList();
    }
    return const <Map<String, dynamic>>[];
  }
}
