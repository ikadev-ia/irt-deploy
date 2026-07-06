import 'dart:async';
import 'dart:convert';

import 'package:http/http.dart' as http;

import '../models/api_config.dart';
import '../models/weather_report.dart';
import 'api_exceptions.dart';
import 'backend_url_resolver.dart';

class OpenWeatherClient {
  OpenWeatherClient({http.Client? client}) : _client = client ?? http.Client();

  final http.Client _client;
  static const Duration _timeout = Duration(seconds: 10);

  Future<WeatherReport> fetch(ApiConfig config, {String authToken = ''}) async {
    try {
      return await _fetchOpenMeteoDirect(config);
    } on Object {
      if (!config.hasBackend && !config.hasOpenWeather) {
        rethrow;
      }
    }
    if (config.hasBackend) {
      try {
        return await _fetchFromAgricheck(config, authToken: authToken);
      } on RemoteApiException {
        if (!config.hasOpenWeather) {
          rethrow;
        }
      }
    }
    if (!config.hasOpenWeather) {
      throw const RemoteApiException(
        'Meteo indisponible: verifiez la connexion Internet.',
      );
    }
    return _fetchDirect(config);
  }

  Future<WeatherReport> _fetchFromAgricheck(
    ApiConfig config, {
    required String authToken,
  }) async {
    final bases = BackendUrlResolver.baseUris(config);
    for (final base in bases) {
      final uri = base.resolve(
        '/api/app/weather/?lat=${config.latitude}&lon=${config.longitude}',
      );
      try {
        final response = await _client
            .get(
              uri,
              headers: <String, String>{
                if (authToken.trim().isNotEmpty)
                  'Authorization': 'Bearer ${authToken.trim()}',
              },
            )
            .timeout(_timeout);
        if (response.statusCode < 200 || response.statusCode >= 300) {
          throw RemoteApiException(
            _serverMessage(response.body) ??
                'Meteo indisponible: verifiez la connexion Internet.',
            statusCode: response.statusCode,
          );
        }
        final body = jsonDecode(response.body) as Map<String, dynamic>;
        return _weatherFromBackendBody(body, config);
      } catch (_) {
        // Try the next Agricheck URL, then fall back to the direct meteo error.
      }
    }
    throw const RemoteApiException(
      'Meteo indisponible: verifiez la connexion Internet.',
    );
  }

  WeatherReport _weatherFromBackendBody(
    Map<String, dynamic> body,
    ApiConfig config,
  ) {
    return WeatherReport(
      cityLabel:
          body['cityLabel'] as String? ??
          body['city_label'] as String? ??
          body['localisation'] as String? ??
          'Lat ${config.latitude.toStringAsFixed(2)}, Lon ${config.longitude.toStringAsFixed(2)}',
      temperature: _toDouble(body['temperature']),
      humidity: (_toDouble(body['humidity'] ?? body['humidite'])).round(),
      windSpeed: _toDouble(
        body['windSpeed'] ?? body['wind_speed'] ?? body['vent'],
      ),
      precipitation: _toDouble(
        body['precipitation'] ?? body['precipitations'] ?? body['rain'],
      ),
      description: body['description'] as String? ?? '',
      periods: _parseBackendPeriods(body['periods']),
      days: _parseBackendDays(body['days']),
    );
  }

  Future<WeatherReport> _fetchDirect(ApiConfig config) async {
    final uri = Uri.https(
      'api.openweathermap.org',
      '/data/3.0/onecall',
      <String, String>{
        'lat': config.latitude.toString(),
        'lon': config.longitude.toString(),
        'units': 'metric',
        'lang': 'fr',
        'exclude': 'minutely,hourly,alerts',
        'appid': config.openWeatherApiKey.trim(),
      },
    );
    final response = await _client.get(uri).timeout(_timeout);
    if (response.statusCode < 200 || response.statusCode >= 300) {
      throw RemoteApiException(
        'Le service meteo Agricheck a refuse la demande.',
        statusCode: response.statusCode,
      );
    }
    final body = jsonDecode(response.body) as Map<String, dynamic>;
    final current =
        body['current'] as Map<String, dynamic>? ?? const <String, dynamic>{};
    final daily = body['daily'] as List? ?? const <dynamic>[];
    final days = daily.take(7).map((day) {
      final map = day as Map<String, dynamic>;
      final temp =
          map['temp'] as Map<String, dynamic>? ?? const <String, dynamic>{};
      final weather = map['weather'] as List? ?? const <dynamic>[];
      final weatherMap = weather.isNotEmpty
          ? weather.first as Map<String, dynamic>
          : const <String, dynamic>{};
      return WeatherDay(
        date: DateTime.fromMillisecondsSinceEpoch(
          (_toDouble(map['dt']) * 1000).round(),
        ),
        minTemperature: _toDouble(temp['min']),
        maxTemperature: _toDouble(temp['max']),
        humidity: (_toDouble(map['humidity'])).round(),
        windSpeed: _toDouble(map['wind_speed']),
        precipitation: _toDouble(map['rain']),
        description: weatherMap['description'] as String? ?? '',
      );
    }).toList();
    final weather = current['weather'] as List? ?? const <dynamic>[];
    final weatherMap = weather.isNotEmpty
        ? weather.first as Map<String, dynamic>
        : const <String, dynamic>{};

    return WeatherReport(
      cityLabel:
          'Lat ${config.latitude.toStringAsFixed(2)}, Lon ${config.longitude.toStringAsFixed(2)}',
      temperature: _toDouble(current['temp']),
      humidity: (_toDouble(current['humidity'])).round(),
      windSpeed: _toDouble(current['wind_speed']),
      precipitation: _toDouble(current['rain']),
      description: weatherMap['description'] as String? ?? '',
      periods: const <WeatherPeriod>[],
      days: days,
    );
  }

  Future<WeatherReport> _fetchOpenMeteoDirect(ApiConfig config) async {
    final uri = Uri.https('api.open-meteo.com', '/v1/forecast', <
      String,
      String
    >{
      'latitude': config.latitude.toString(),
      'longitude': config.longitude.toString(),
      'current':
          'temperature_2m,relative_humidity_2m,precipitation,weather_code,wind_speed_10m',
      'hourly':
          'temperature_2m,relative_humidity_2m,precipitation,weather_code,wind_speed_10m',
      'daily':
          'weather_code,temperature_2m_max,temperature_2m_min,precipitation_sum,wind_speed_10m_max,relative_humidity_2m_mean',
      'timezone': 'auto',
      'forecast_days': '7',
    });
    final response = await _client.get(uri).timeout(_timeout);
    if (response.statusCode < 200 || response.statusCode >= 300) {
      throw RemoteApiException(
        'Meteo indisponible: verifiez la connexion Internet.',
        statusCode: response.statusCode,
      );
    }
    final body = jsonDecode(response.body) as Map<String, dynamic>;
    final current =
        body['current'] as Map<String, dynamic>? ?? const <String, dynamic>{};
    final daily =
        body['daily'] as Map<String, dynamic>? ?? const <String, dynamic>{};
    final hourly =
        body['hourly'] as Map<String, dynamic>? ?? const <String, dynamic>{};
    final days = _parseOpenMeteoDays(daily);
    return WeatherReport(
      cityLabel:
          'GPS ${config.latitude.toStringAsFixed(2)}, ${config.longitude.toStringAsFixed(2)}',
      temperature: _toDouble(current['temperature_2m']),
      humidity: (_toDouble(current['relative_humidity_2m'])).round(),
      windSpeed: _kmhToMs(_toDouble(current['wind_speed_10m'])),
      precipitation: _toDouble(current['precipitation']),
      description: _weatherCodeLabel(
        (_toDouble(current['weather_code'])).round(),
      ),
      periods: _parseOpenMeteoPeriods(hourly),
      days: days,
    );
  }

  List<WeatherPeriod> _parseOpenMeteoPeriods(Map<String, dynamic> hourly) {
    final times = hourly['time'] as List? ?? const <dynamic>[];
    final codes = hourly['weather_code'] as List? ?? const <dynamic>[];
    final temps = hourly['temperature_2m'] as List? ?? const <dynamic>[];
    final rains = hourly['precipitation'] as List? ?? const <dynamic>[];
    final winds = hourly['wind_speed_10m'] as List? ?? const <dynamic>[];
    final humidities =
        hourly['relative_humidity_2m'] as List? ?? const <dynamic>[];
    final parsedTimes = times
        .map((item) => DateTime.tryParse(item.toString()))
        .whereType<DateTime>()
        .toList();
    if (parsedTimes.isEmpty) {
      return const <WeatherPeriod>[];
    }
    final firstDay = DateTime(
      parsedTimes.first.year,
      parsedTimes.first.month,
      parsedTimes.first.day,
    );
    final targets = <String, int>{
      'Matin': 6,
      'Midi': 12,
      'Soir': 18,
      'Nuit': 21,
    };
    return targets.entries.map((target) {
      final index = _closestHourIndex(parsedTimes, firstDay, target.value);
      return WeatherPeriod(
        label: target.key,
        time: parsedTimes[index],
        temperature: _toDouble(_listValue(temps, index)),
        humidity: (_toDouble(_listValue(humidities, index))).round(),
        windSpeed: _kmhToMs(_toDouble(_listValue(winds, index))),
        precipitation: _toDouble(_listValue(rains, index)),
        description: _weatherCodeLabel(
          (_toDouble(_listValue(codes, index))).round(),
        ),
      );
    }).toList();
  }

  int _closestHourIndex(List<DateTime> times, DateTime day, int hour) {
    final target = day.add(Duration(hours: hour));
    var bestIndex = 0;
    var bestDistance = target.difference(times.first).abs();
    for (var i = 1; i < times.length; i += 1) {
      final distance = target.difference(times[i]).abs();
      if (distance < bestDistance) {
        bestDistance = distance;
        bestIndex = i;
      }
    }
    return bestIndex;
  }

  List<WeatherDay> _parseOpenMeteoDays(Map<String, dynamic> daily) {
    final times = daily['time'] as List? ?? const <dynamic>[];
    final codes = daily['weather_code'] as List? ?? const <dynamic>[];
    final mins = daily['temperature_2m_min'] as List? ?? const <dynamic>[];
    final maxes = daily['temperature_2m_max'] as List? ?? const <dynamic>[];
    final rains = daily['precipitation_sum'] as List? ?? const <dynamic>[];
    final winds = daily['wind_speed_10m_max'] as List? ?? const <dynamic>[];
    final humidities =
        daily['relative_humidity_2m_mean'] as List? ?? const <dynamic>[];
    final days = <WeatherDay>[];
    for (var i = 0; i < times.length && i < 7; i += 1) {
      days.add(
        WeatherDay(
          date:
              DateTime.tryParse(times[i].toString()) ??
              DateTime.now().add(Duration(days: i)),
          minTemperature: _toDouble(_listValue(mins, i)),
          maxTemperature: _toDouble(_listValue(maxes, i)),
          humidity: (_toDouble(_listValue(humidities, i))).round(),
          windSpeed: _kmhToMs(_toDouble(_listValue(winds, i))),
          precipitation: _toDouble(_listValue(rains, i)),
          description: _weatherCodeLabel(
            (_toDouble(_listValue(codes, i))).round(),
          ),
        ),
      );
    }
    return days;
  }

  List<WeatherDay> _parseBackendDays(Object? value) {
    if (value is! List) {
      return const <WeatherDay>[];
    }
    return value.map((item) {
      final map = item as Map<String, dynamic>;
      return WeatherDay(
        date: DateTime.tryParse(map['date'] as String? ?? '') ?? DateTime.now(),
        minTemperature: _toDouble(
          map['minTemperature'] ?? map['min_temperature'] ?? map['temp_min'],
        ),
        maxTemperature: _toDouble(
          map['maxTemperature'] ?? map['max_temperature'] ?? map['temp_max'],
        ),
        humidity: (_toDouble(map['humidity'] ?? map['humidite'])).round(),
        windSpeed: _toDouble(
          map['windSpeed'] ?? map['wind_speed'] ?? map['vent'],
        ),
        precipitation: _toDouble(
          map['precipitation'] ?? map['precipitations'] ?? map['rain'],
        ),
        description: map['description'] as String? ?? '',
      );
    }).toList();
  }

  List<WeatherPeriod> _parseBackendPeriods(Object? value) {
    if (value is! List) {
      return const <WeatherPeriod>[];
    }
    return value.map((item) {
      final map = item as Map<String, dynamic>;
      return WeatherPeriod(
        label: map['label'] as String? ?? '',
        time: DateTime.tryParse(map['time'] as String? ?? '') ?? DateTime.now(),
        temperature: _toDouble(map['temperature']),
        humidity: (_toDouble(map['humidity'] ?? map['humidite'])).round(),
        windSpeed: _toDouble(
          map['windSpeed'] ?? map['wind_speed'] ?? map['vent'],
        ),
        precipitation: _toDouble(
          map['precipitation'] ?? map['precipitations'] ?? map['rain'],
        ),
        description: map['description'] as String? ?? '',
      );
    }).toList();
  }

  double _toDouble(Object? value) {
    if (value is num) {
      return value.toDouble();
    }
    if (value is Map<String, dynamic>) {
      return _toDouble(value['1h'] ?? value['3h']);
    }
    return double.tryParse(value?.toString() ?? '') ?? 0;
  }

  Object? _listValue(List<dynamic> values, int index) {
    return index < values.length ? values[index] : null;
  }

  double _kmhToMs(double value) => value / 3.6;

  String _weatherCodeLabel(int code) {
    if (code == 0) {
      return 'Ciel clair';
    }
    if (code == 1 || code == 2 || code == 3) {
      return 'Partiellement nuageux';
    }
    if (code == 45 || code == 48) {
      return 'Brouillard';
    }
    if ((code >= 51 && code <= 67) || (code >= 80 && code <= 82)) {
      return 'Pluie';
    }
    if (code >= 71 && code <= 77) {
      return 'Chute de neige';
    }
    if (code >= 95) {
      return 'Orage';
    }
    return 'Prevision disponible';
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
