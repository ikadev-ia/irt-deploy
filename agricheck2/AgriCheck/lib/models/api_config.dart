import 'package:flutter_dotenv/flutter_dotenv.dart';
class ApiConfig {
  const ApiConfig({
    this.backendBaseUrl = const String.fromEnvironment(
      'AGRICHECK_API_BASE_URL',
      defaultValue: 'http://127.0.0.1:8090',
    ),
    this.useBackendProxy = true,
    this.openWeatherApiKey = const String.fromEnvironment(
      'AGRICHECK_WEATHER_KEY',
    ),
    this.latitude = 12.6392,
    this.longitude = -8.0029,
  });
  static String get plantIdApikey =>
     dotenv.env['PLANT_ID_API_KEY'] ?? '';

  final String backendBaseUrl;
  final bool useBackendProxy;
  final String openWeatherApiKey;
  final double latitude;
  final double longitude;

  bool get hasBackend => backendBaseUrl.trim().isNotEmpty;

  bool get hasOpenWeather => openWeatherApiKey.trim().isNotEmpty;

  bool get canRunDiagnosis => useBackendProxy && hasBackend;

  ApiConfig copyWith({
    String? backendBaseUrl,
    bool? useBackendProxy,
    String? openWeatherApiKey,
    double? latitude,
    double? longitude,
  }) {
    return ApiConfig(
      backendBaseUrl: backendBaseUrl ?? this.backendBaseUrl,
      useBackendProxy: useBackendProxy ?? this.useBackendProxy,
      openWeatherApiKey: openWeatherApiKey ?? this.openWeatherApiKey,
      latitude: latitude ?? this.latitude,
      longitude: longitude ?? this.longitude,
    );
  }

  Map<String, dynamic> toJson() {
    return <String, dynamic>{
      'backendBaseUrl': backendBaseUrl,
      'useBackendProxy': useBackendProxy,
      'openWeatherApiKey': openWeatherApiKey,
      'latitude': latitude,
      'longitude': longitude,
    };
  }

  factory ApiConfig.fromJson(Map<String, dynamic> json) {
    return ApiConfig(
      backendBaseUrl: json['backendBaseUrl'] as String? ?? '',
      useBackendProxy: json['useBackendProxy'] as bool? ?? true,
      openWeatherApiKey: json['openWeatherApiKey'] as String? ?? '',
      latitude: _toDouble(json['latitude'], 12.6392),
      longitude: _toDouble(json['longitude'], -8.0029),
    );
  }

  static double _toDouble(Object? value, double fallback) {
    if (value is num) {
      return value.toDouble();
    }
    return double.tryParse(value?.toString() ?? '') ?? fallback;
  }
}
