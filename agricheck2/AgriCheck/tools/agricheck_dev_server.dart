import 'dart:convert';
import 'dart:io';
import 'dart:math';
import 'dart:typed_data';

import 'package:crypto/crypto.dart';
import 'package:http/http.dart' as http;
import 'package:http_parser/http_parser.dart';

const int port = 8000;
const String dbPath = 'server_data/agricheck_dev_db.json';
const String logPath = 'server_data/agricheck_dev_server.log';
const String apiKeysPath = 'server_data/agricheck_api_keys.env';
Map<String, String>? _envFileCache;

class RemoteDiagnosticException implements Exception {
  const RemoteDiagnosticException(this.message, {required this.statusCode});

  final String message;
  final int statusCode;
}

class UploadedFile {
  const UploadedFile({
    required this.fieldName,
    required this.filename,
    required this.bytes,
    required this.contentType,
  });

  final String fieldName;
  final String filename;
  final List<int> bytes;
  final String contentType;
}

class MultipartForm {
  const MultipartForm({required this.fields, required this.files});

  final Map<String, String> fields;
  final Map<String, UploadedFile> files;
}

Future<void> main() async {
  final server = await HttpServer.bind(InternetAddress.anyIPv4, port);
  await _log('Agricheck dev server running on http://0.0.0.0:$port');
  await _log('Android emulator URL: http://10.0.2.2:$port');

  await for (final request in server) {
    try {
      await _handle(request);
    } catch (error, stackTrace) {
      await _log('$error\n$stackTrace');
      await _json(
        request.response,
        statusCode: HttpStatus.internalServerError,
        body: <String, dynamic>{'detail': 'Erreur serveur Agricheck locale.'},
      );
    }
  }
}

Future<void> _log(String message) async {
  final file = File(logPath);
  await file.parent.create(recursive: true);
  await file.writeAsString(
    '[${DateTime.now().toIso8601String()}] $message\n',
    mode: FileMode.append,
  );
}

Future<void> _handle(HttpRequest request) async {
  final path = request.uri.path;
  if (request.method == 'OPTIONS') {
    return _json(request.response, body: const <String, dynamic>{});
  }

  if (request.method == 'GET' && path == '/api/app/health/') {
    return _json(request.response, body: <String, dynamic>{'status': 'ok'});
  }

  if (request.method == 'POST' && path == '/api/app/auth/register/') {
    return _register(request);
  }
  if (request.method == 'POST' && path == '/api/app/auth/login/') {
    return _login(request);
  }
  if (request.method == 'POST' && path == '/api/app/auth/password-reset/') {
    return _json(
      request.response,
      body: <String, dynamic>{
        'message': 'Demande recue. Configurez l envoi SMS/email en production.',
      },
    );
  }
  if (request.method == 'GET' && path == '/api/app/advice/') {
    return _json(
      request.response,
      body: <String, dynamic>{'advice': <dynamic>[]},
    );
  }
  if (request.method == 'GET' && path == '/api/app/notifications/') {
    return _json(
      request.response,
      body: <String, dynamic>{'notifications': <dynamic>[]},
    );
  }
  if (request.method == 'GET' && path == '/api/app/weather/') {
    return _weather(request);
  }
  if (request.method == 'POST' && path == '/api/app/diagnostics/') {
    return _diagnose(request);
  }
  if (request.method == 'POST' && path == '/api/app/assistant/chat/') {
    return _assistant(request);
  }

  return _json(
    request.response,
    statusCode: HttpStatus.notFound,
    body: <String, dynamic>{'detail': 'Endpoint Agricheck introuvable.'},
  );
}

Future<void> _diagnose(HttpRequest request) async {
  final plantNetKey = _secret('AGRICHECK_PLANTNET_KEY');
  final plantIdKey = _firstSecret(<String>[
    'AGRICHECK_PLANT_ID_KEY',
    'AGRICHECK_HEALTH_KEY',
  ]);
  final cropHealthKey = _secret('AGRICHECK_CROP_HEALTH_KEY');
  if (plantNetKey.isEmpty || (plantIdKey.isEmpty && cropHealthKey.isEmpty)) {
    await request.drain<void>();
    return _json(
      request.response,
      statusCode: HttpStatus.serviceUnavailable,
      body: <String, dynamic>{
        'detail':
            'Diagnostic IA non configure. Renseignez AGRICHECK_PLANTNET_KEY et AGRICHECK_PLANT_ID_KEY ou AGRICHECK_CROP_HEALTH_KEY dans server_data/agricheck_api_keys.env puis redemarrez le backend.',
      },
    );
  }

  try {
    final form = await _readMultipart(request);
    final image = form.files['image'];
    if (image == null || image.bytes.isEmpty) {
      return _json(
        request.response,
        statusCode: HttpStatus.badRequest,
        body: <String, dynamic>{
          'detail': 'Aucune image recue pour le diagnostic IA.',
        },
      );
    }
    final lat = double.tryParse(form.fields['latitude'] ?? '') ?? 12.6392;
    final lon = double.tryParse(form.fields['longitude'] ?? '') ?? -8.0029;
    final plant = await _identifyWithPlantNet(image, plantNetKey);
    final health = plantIdKey.isNotEmpty
        ? await _assessWithPlantId(image, plantIdKey, lat, lon)
        : await _assessWithCropHealth(image, cropHealthKey, lat, lon);
    return _json(
      request.response,
      body: _diagnosisPayload(
        image: image,
        plant: plant,
        health: health,
        latitude: lat,
        longitude: lon,
      ),
    );
  } on RemoteDiagnosticException catch (error) {
    await _log('Diagnostic error: ${error.message}');
    return _json(
      request.response,
      statusCode: error.statusCode,
      body: <String, dynamic>{'detail': error.message},
    );
  } catch (error, stackTrace) {
    await _log('Diagnostic error: $error\n$stackTrace');
    return _json(
      request.response,
      statusCode: HttpStatus.serviceUnavailable,
      body: <String, dynamic>{
        'detail':
            'Diagnostic IA indisponible. Verifiez les cles PlantNet/Plant.id et la connexion Internet du PC serveur.',
      },
    );
  }
}

Future<Map<String, dynamic>> _identifyWithPlantNet(
  UploadedFile image,
  String apiKey,
) async {
  final request =
      http.MultipartRequest(
          'POST',
          Uri.https('my-api.plantnet.org', '/v2/identify/all', <String, String>{
            'api-key': apiKey,
            'include-related-images': 'false',
            'no-reject': 'false',
            'lang': 'fr',
          }),
        )
        ..fields['organs'] = 'auto'
        ..files.add(
          http.MultipartFile.fromBytes(
            'images',
            image.bytes,
            filename: image.filename,
            contentType: _mediaTypeFor(image.filename),
          ),
        );
  final response = await http.Response.fromStream(
    await request.send().timeout(const Duration(seconds: 35)),
  ).timeout(const Duration(seconds: 35));
  if (response.statusCode < 200 || response.statusCode >= 300) {
    throw RemoteDiagnosticException(
      'PlantNet a refuse l identification de la plante.',
      statusCode: response.statusCode,
    );
  }
  final payload = jsonDecode(response.body) as Map<String, dynamic>;
  final results = payload['results'];
  if (results is! List || results.isEmpty) {
    throw const RemoteDiagnosticException(
      'PlantNet n a pas reconnu de plante exploitable sur cette image.',
      statusCode: HttpStatus.unprocessableEntity,
    );
  }
  final best = results.first as Map<String, dynamic>;
  final species =
      best['species'] as Map<String, dynamic>? ?? const <String, dynamic>{};
  final commonNames = species['commonNames'];
  final commonName = commonNames is List && commonNames.isNotEmpty
      ? commonNames.first.toString()
      : (payload['bestMatch'] as String? ?? 'Plante identifiee');
  final scientificName =
      species['scientificNameWithoutAuthor'] as String? ??
      species['scientificName'] as String? ??
      payload['bestMatch'] as String? ??
      '';
  final family = species['family'] is Map<String, dynamic>
      ? ((species['family']
                    as Map<String, dynamic>)['scientificNameWithoutAuthor']
                as String? ??
            '')
      : '';
  return <String, dynamic>{
    'name': commonName,
    'scientificName': scientificName,
    'family': family,
    'confidence': _toDouble(best['score']),
  };
}

Future<Map<String, dynamic>> _assessWithPlantId(
  UploadedFile image,
  String apiKey,
  double lat,
  double lon,
) async {
  final payload = jsonEncode(<String, dynamic>{
    'images': <String>[base64Encode(image.bytes)],
    'latitude': lat,
    'longitude': lon,
    'similar_images': false,
    'health': 'all',
    'classification_level': 'species',
    'language': 'fr',
    'details': <String>[
      'description',
      'common_names',
      'taxonomy',
      'treatment',
      'cause',
      'url',
    ],
  });
  final endpoints = <Uri>[
    Uri.https('plant.id', '/api/v3/identification'),
    Uri.https('api.plant.id', '/v3/identification'),
  ];
  http.Response? lastResponse;
  for (final endpoint in endpoints) {
    final response = await http
        .post(
          endpoint,
          headers: <String, String>{
            'Api-Key': apiKey,
            'Content-Type': 'application/json',
          },
          body: payload,
        )
        .timeout(const Duration(seconds: 40));
    if (response.statusCode >= 200 && response.statusCode < 300) {
      return _parsePlantIdHealth(
        jsonDecode(response.body) as Map<String, dynamic>,
      );
    }
    lastResponse = response;
  }
  throw RemoteDiagnosticException(
    'Plant.id a refuse le diagnostic maladie.',
    statusCode: lastResponse?.statusCode ?? HttpStatus.serviceUnavailable,
  );
}

Future<Map<String, dynamic>> _assessWithCropHealth(
  UploadedFile image,
  String apiKey,
  double lat,
  double lon,
) async {
  final payload = jsonEncode(<String, dynamic>{
    'images': <String>[base64Encode(image.bytes)],
    'latitude': lat,
    'longitude': lon,
    'similar_images': false,
    'language': 'fr',
    'details': <String>[
      'description',
      'common_names',
      'taxonomy',
      'treatment',
      'cause',
      'url',
    ],
  });
  final endpoints = <Uri>[
    Uri.https('crop.kindwise.com', '/api/v1/identification'),
    Uri.https('api.crop.kindwise.com', '/v1/identification'),
  ];
  http.Response? lastResponse;
  for (final endpoint in endpoints) {
    final response = await http
        .post(
          endpoint,
          headers: <String, String>{
            'Api-Key': apiKey,
            'Content-Type': 'application/json',
          },
          body: payload,
        )
        .timeout(const Duration(seconds: 40));
    if (response.statusCode >= 200 && response.statusCode < 300) {
      return _parsePlantIdHealth(
        jsonDecode(response.body) as Map<String, dynamic>,
      );
    }
    lastResponse = response;
  }
  throw RemoteDiagnosticException(
    'Crop.health a refuse le diagnostic maladie.',
    statusCode: lastResponse?.statusCode ?? HttpStatus.serviceUnavailable,
  );
}

Map<String, dynamic> _parsePlantIdHealth(Map<String, dynamic> body) {
  final result = body['result'] as Map<String, dynamic>? ?? body;
  final healthyNode =
      result['is_healthy'] as Map<String, dynamic>? ??
      result['isHealthy'] as Map<String, dynamic>? ??
      const <String, dynamic>{};
  final healthyProbability = _toDouble(healthyNode['probability']);
  final isHealthy = healthyNode['binary'] as bool? ?? healthyProbability >= 0.5;
  final diseaseNode =
      result['disease'] as Map<String, dynamic>? ??
      result['health'] as Map<String, dynamic>? ??
      result['crop_health'] as Map<String, dynamic>? ??
      const <String, dynamic>{};
  final suggestions =
      diseaseNode['suggestions'] as List? ??
      diseaseNode['classes'] as List? ??
      const <dynamic>[];

  if (suggestions.isEmpty) {
    return <String, dynamic>{
      'name': isHealthy
          ? 'Plante probablement saine'
          : 'Maladie non determinee',
      'confidence': isHealthy ? healthyProbability : 0.0,
      'isHealthy': isHealthy,
      'symptoms': <String>[],
      'causes': <String>[],
      'biologicalTreatments': <String>[],
      'chemicalTreatments': <String>[],
      'prevention': <String>[],
      'dosage': '',
    };
  }

  final best = suggestions.first as Map<String, dynamic>;
  final details =
      best['details'] as Map<String, dynamic>? ?? const <String, dynamic>{};
  final treatment =
      details['treatment'] as Map<String, dynamic>? ??
      const <String, dynamic>{};
  final probability = _toDouble(
    best['probability'] ?? best['confidence'] ?? best['score'],
  );
  return <String, dynamic>{
    'name':
        best['name'] as String? ??
        best['common_name'] as String? ??
        best['scientific_name'] as String? ??
        'Maladie detectee',
    'confidence': probability,
    'isHealthy': isHealthy && probability < 0.2,
    'symptoms': _stringList(details['symptoms']),
    'causes': _stringList(details['cause']).isEmpty
        ? _stringList(details['causes'])
        : _stringList(details['cause']),
    'biologicalTreatments': _stringList(treatment['biological']),
    'chemicalTreatments': _stringList(treatment['chemical']),
    'prevention': _stringList(treatment['prevention']),
    'dosage': _localizedText(treatment['dosage']) ?? '',
    'description':
        _localizedText(details['description']) ??
        _localizedText(best['description']) ??
        '',
  };
}

Map<String, dynamic> _diagnosisPayload({
  required UploadedFile image,
  required Map<String, dynamic> plant,
  required Map<String, dynamic> health,
  required double latitude,
  required double longitude,
}) {
  final isHealthy = health['isHealthy'] as bool? ?? false;
  final confidence = _toDouble(health['confidence']);
  return <String, dynamic>{
    'id': DateTime.now().microsecondsSinceEpoch.toString(),
    'createdAt': DateTime.now().toIso8601String(),
    'imageName': image.filename,
    'plantName': plant['name'] as String? ?? 'Plante identifiee',
    'scientificName': plant['scientificName'] as String? ?? '',
    'plantConfidence': _toDouble(plant['confidence']),
    'family': plant['family'] as String? ?? '',
    'diseaseName': health['name'] as String? ?? 'Diagnostic non disponible',
    'diseaseConfidence': confidence,
    'riskLevel': _riskLevel(isHealthy, confidence),
    'isHealthy': isHealthy,
    'plantDescription': '',
    'symptoms': health['symptoms'] as List? ?? const <String>[],
    'causes': health['causes'] as List? ?? const <String>[],
    'biologicalTreatments':
        health['biologicalTreatments'] as List? ?? const <String>[],
    'chemicalTreatments':
        health['chemicalTreatments'] as List? ?? const <String>[],
    'prevention': health['prevention'] as List? ?? const <String>[],
    'dosage': health['dosage'] as String? ?? '',
    'frequency': isHealthy
        ? 'Surveillance simple chaque semaine'
        : 'Suivre les recommandations renvoyees par Plant.id',
    'urgencyLevel': _urgencyLevel(isHealthy, confidence),
    'latitude': latitude,
    'longitude': longitude,
    'locationLabel':
        'GPS ${latitude.toStringAsFixed(4)}, ${longitude.toStringAsFixed(4)}',
    'source': 'PlantNet + Plant.id',
  };
}

String _riskLevel(bool isHealthy, double confidence) {
  if (isHealthy) {
    return 'Faible';
  }
  if (confidence >= 0.75) {
    return 'Eleve';
  }
  if (confidence >= 0.45) {
    return 'Moyen';
  }
  return 'A confirmer';
}

String _urgencyLevel(bool isHealthy, double confidence) {
  if (isHealthy) {
    return 'Surveillance simple';
  }
  if (confidence >= 0.75) {
    return 'Intervention rapide';
  }
  if (confidence >= 0.45) {
    return 'A traiter sous observation';
  }
  return 'A confirmer avant traitement';
}

Future<void> _weather(HttpRequest request) async {
  final lat =
      double.tryParse(request.uri.queryParameters['lat'] ?? '') ?? 12.6392;
  final lon =
      double.tryParse(request.uri.queryParameters['lon'] ?? '') ?? -8.0029;
  try {
    final body = await _fetchOpenMeteo(lat, lon);
    return _json(request.response, body: body);
  } catch (error, stackTrace) {
    await _log('Weather error: $error\n$stackTrace');
    return _json(
      request.response,
      statusCode: HttpStatus.serviceUnavailable,
      body: <String, dynamic>{
        'detail':
            'Meteo agricole indisponible. Verifiez la connexion Internet du PC serveur.',
      },
    );
  }
}

Future<Map<String, dynamic>> _fetchOpenMeteo(double lat, double lon) async {
  final uri = Uri.https('api.open-meteo.com', '/v1/forecast', <String, String>{
    'latitude': lat.toString(),
    'longitude': lon.toString(),
    'current':
        'temperature_2m,relative_humidity_2m,precipitation,weather_code,wind_speed_10m',
    'hourly':
        'temperature_2m,relative_humidity_2m,precipitation,weather_code,wind_speed_10m',
    'daily':
        'weather_code,temperature_2m_max,temperature_2m_min,precipitation_sum,wind_speed_10m_max,relative_humidity_2m_mean',
    'timezone': 'auto',
    'forecast_days': '7',
  });
  final text = await _downloadText(uri);
  final data = jsonDecode(text) as Map<String, dynamic>;
  final current =
      data['current'] as Map<String, dynamic>? ?? const <String, dynamic>{};
  final daily =
      data['daily'] as Map<String, dynamic>? ?? const <String, dynamic>{};
  final hourly =
      data['hourly'] as Map<String, dynamic>? ?? const <String, dynamic>{};
  final times = (daily['time'] as List? ?? const <dynamic>[])
      .map((item) => item.toString())
      .toList();
  final codes = daily['weather_code'] as List? ?? const <dynamic>[];
  final mins = daily['temperature_2m_min'] as List? ?? const <dynamic>[];
  final maxes = daily['temperature_2m_max'] as List? ?? const <dynamic>[];
  final rains = daily['precipitation_sum'] as List? ?? const <dynamic>[];
  final winds = daily['wind_speed_10m_max'] as List? ?? const <dynamic>[];
  final humidities =
      daily['relative_humidity_2m_mean'] as List? ?? const <dynamic>[];
  final days = <Map<String, dynamic>>[];
  for (var i = 0; i < times.length && i < 7; i += 1) {
    final code = _toInt(_listValue(codes, i));
    days.add(<String, dynamic>{
      'date': times[i],
      'minTemperature': _toDouble(_listValue(mins, i)),
      'maxTemperature': _toDouble(_listValue(maxes, i)),
      'humidity': _toInt(_listValue(humidities, i)),
      'windSpeed': _kmhToMs(_toDouble(_listValue(winds, i))),
      'precipitation': _toDouble(_listValue(rains, i)),
      'description': _weatherCodeLabel(code),
    });
  }
  final currentCode = _toInt(current['weather_code']);
  return <String, dynamic>{
    'cityLabel': 'GPS ${lat.toStringAsFixed(2)}, ${lon.toStringAsFixed(2)}',
    'temperature': _toDouble(current['temperature_2m']),
    'humidity': _toInt(current['relative_humidity_2m']),
    'windSpeed': _kmhToMs(_toDouble(current['wind_speed_10m'])),
    'precipitation': _toDouble(current['precipitation']),
    'description': _weatherCodeLabel(currentCode),
    'periods': _weatherPeriods(hourly),
    'days': days,
    'source': 'Open-Meteo',
  };
}

List<Map<String, dynamic>> _weatherPeriods(Map<String, dynamic> hourly) {
  final times = (hourly['time'] as List? ?? const <dynamic>[])
      .map((item) => DateTime.tryParse(item.toString()))
      .whereType<DateTime>()
      .toList();
  if (times.isEmpty) {
    return const <Map<String, dynamic>>[];
  }
  final codes = hourly['weather_code'] as List? ?? const <dynamic>[];
  final temps = hourly['temperature_2m'] as List? ?? const <dynamic>[];
  final rains = hourly['precipitation'] as List? ?? const <dynamic>[];
  final winds = hourly['wind_speed_10m'] as List? ?? const <dynamic>[];
  final humidities =
      hourly['relative_humidity_2m'] as List? ?? const <dynamic>[];
  final day = DateTime(times.first.year, times.first.month, times.first.day);
  final targets = <String, int>{'Matin': 6, 'Midi': 12, 'Soir': 18, 'Nuit': 21};
  return targets.entries.map((entry) {
    final index = _closestHourIndex(times, day, entry.value);
    final code = _toInt(_listValue(codes, index));
    return <String, dynamic>{
      'label': entry.key,
      'time': times[index].toIso8601String(),
      'temperature': _toDouble(_listValue(temps, index)),
      'humidity': _toInt(_listValue(humidities, index)),
      'windSpeed': _kmhToMs(_toDouble(_listValue(winds, index))),
      'precipitation': _toDouble(_listValue(rains, index)),
      'description': _weatherCodeLabel(code),
    };
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

Future<String> _downloadText(Uri uri) async {
  if (Platform.isWindows) {
    final script =
        "\$ProgressPreference='SilentlyContinue'; "
        '[Net.ServicePointManager]::SecurityProtocol=[Net.SecurityProtocolType]::Tls12; '
        "(Invoke-WebRequest -Uri '$uri' -UseBasicParsing).Content";
    final result = await Process.run('powershell', <String>[
      '-NoProfile',
      '-ExecutionPolicy',
      'Bypass',
      '-Command',
      script,
    ]).timeout(const Duration(seconds: 18));
    if (result.exitCode != 0) {
      throw HttpException(result.stderr.toString());
    }
    return result.stdout.toString();
  }

  final client = HttpClient()..connectionTimeout = const Duration(seconds: 8);
  try {
    final req = await client.getUrl(uri).timeout(const Duration(seconds: 8));
    final res = await req.close().timeout(const Duration(seconds: 12));
    final text = await utf8.decoder
        .bind(res)
        .join()
        .timeout(const Duration(seconds: 12));
    if (res.statusCode < 200 || res.statusCode >= 300) {
      throw HttpException('Open-Meteo HTTP ${res.statusCode}');
    }
    return text;
  } finally {
    client.close(force: true);
  }
}

Object? _listValue(List<dynamic> values, int index) {
  return index < values.length ? values[index] : null;
}

double _kmhToMs(double value) => value / 3.6;

double _toDouble(Object? value) {
  if (value is num) {
    return value.toDouble();
  }
  return double.tryParse(value?.toString() ?? '') ?? 0;
}

int _toInt(Object? value) => _toDouble(value).round();

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

Future<void> _assistant(HttpRequest request) async {
  final payload = await _body(request);
  final message = _string(payload['message']).toLowerCase();
  final context =
      payload['context'] as Map<String, dynamic>? ?? const <String, dynamic>{};
  final reply = _assistantReply(message, context);
  return _json(request.response, body: <String, dynamic>{'reply': reply});
}

String _assistantReply(String message, Map<String, dynamic> context) {
  if (message.trim().isEmpty) {
    return 'Posez une question sur une culture, une maladie de plante, un traitement ou l entretien agricole.';
  }
  if (!_isAgricultureQuestion(message)) {
    return 'Je n ai pas cette reponse. Je peux seulement aider sur les cultures, les plantes, les arbres fruitiers, les maladies vegetales et les traitements agricoles.';
  }
  final latest =
      context['latestAnalysis'] as Map<String, dynamic>? ??
      const <String, dynamic>{};
  final weather =
      context['weather'] as Map<String, dynamic>? ?? const <String, dynamic>{};
  final plant = _string(latest['plantName'] ?? latest['plant_name']);
  final disease = _string(latest['diseaseName'] ?? latest['disease_name']);
  final risk = _string(latest['riskLevel'] ?? latest['risk_level']);
  final weatherLine = _assistantWeatherLine(weather);

  if (_containsAny(message, <String>[
    'dernier',
    'analyse',
    'resultat',
    'résultat',
    'diagnostic',
  ])) {
    if (plant.isEmpty && disease.isEmpty) {
      return 'Aucun resultat d analyse disponible. Lancez une photo de plante pour que je donne un conseil cible.';
    }
    return 'Derniere analyse: $plant, probleme observe: $disease, risque: $risk. $weatherLine';
  }
  if (_containsAny(message, <String>['tomate', 'tomates'])) {
    return 'Pour la tomate: evitez de mouiller les feuilles, espacez les plants, retirez les feuilles tres atteintes et surveillez mildiou, alternariose et taches foliaires. Pour confirmer une maladie, lancez une analyse photo.';
  }
  if (_containsAny(message, <String>['riz'])) {
    return 'Pour le riz: surveillez les taches foliaires, la rouille et les signes de stress hydrique. Gardez une bonne gestion de l eau et evitez les parcelles trop denses.';
  }
  if (_containsAny(message, <String>['mais', 'maïs', 'mil', 'sorgho'])) {
    return 'Pour les cereales: surveillez rouille, brulure foliaire et taches. Retirez les residus malades, alternez les cultures et signalez rapidement les symptomes qui progressent.';
  }
  if (_containsAny(message, <String>['oignon', 'gombo', 'piment'])) {
    return 'Pour les legumes: privilegiez un sol bien draine, evitez l exces d humidite et inspectez souvent les feuilles. Les taches, jaunissements et pourritures doivent etre analyses avec une photo nette.';
  }
  if (_containsAny(message, <String>[
    'manguier',
    'mangue',
    'papayer',
    'papaye',
    'oranger',
    'citronnier',
    'mandarinier',
    'bananier',
    'banane',
  ])) {
    return 'Pour les arbres fruitiers: taillez les parties malades, gardez le pied propre, evitez l eau stagnante et surveillez anthracnose, taches foliaires et pourritures. Une photo aide a confirmer.';
  }
  if (_containsAny(message, <String>['coton'])) {
    return 'Pour le coton: surveillez les taches foliaires, jaunissements et attaques d insectes. Notez la parcelle touchee et faites une analyse photo avant tout traitement.';
  }
  if (_containsAny(message, <String>[
    'maladie',
    'tache',
    'taches',
    'jaune',
    'feuille',
    'pourriture',
    'rouille',
    'mildiou',
    'oidium',
    'oïdium',
    'anthracnose',
  ])) {
    if (disease.isNotEmpty) {
      return _diseaseAssistantReply(disease, plant, risk, weatherLine);
    }
    return 'Pour un symptome visible: prenez une photo nette de la feuille ou du fruit, evitez l ombre forte, puis lancez l analyse. En attendant, isolez les parties tres malades et evitez d arroser le feuillage.';
  }
  if (_containsAny(message, <String>[
    'traitement',
    'traiter',
    'fongicide',
    'insecticide',
    'engrais',
    'dosage',
  ])) {
    if (disease.isNotEmpty) {
      return _diseaseAssistantReply(disease, plant, risk, weatherLine);
    }
    return 'Pour un traitement: confirmez d abord la maladie, respectez l etiquette du produit, portez une protection et evitez de traiter en plein soleil ou avant une pluie. Pour le dosage exact, suivez le produit disponible localement.';
  }
  if (_containsAny(message, <String>[
    'arroser',
    'arrosage',
    'eau',
    'irrigation',
  ])) {
    final temp = _toDouble(weather['temperature']);
    if (temp > 38) {
      return 'Forte chaleur: arrosez tot le matin ou en fin d apres-midi, au pied de la plante. Evitez de mouiller les feuilles.';
    }
    return 'Pour l arrosage: arrosez tot le matin ou en fin de journee, visez le pied de la plante et evitez de garder les feuilles humides longtemps. $weatherLine';
  }
  if (plant.isNotEmpty || disease.isNotEmpty) {
    return 'Pour $plant: surveillez $disease, risque $risk. $weatherLine';
  }
  return 'Je peux aider sur cette question agricole. Precisez la culture concernee et les symptomes observes pour une reponse plus utile.';
}

String _assistantWeatherLine(Map<String, dynamic> weather) {
  if (weather.isEmpty) {
    return 'Actualisez la meteo pour adapter le conseil.';
  }
  final temp = _toDouble(weather['temperature']);
  final humidity = _toInt(weather['humidity']);
  final windKmh = _toDouble(weather['windSpeed']) * 3.6;
  final rain = _toDouble(weather['precipitation']);
  if (humidity > 80) {
    return 'Humidite elevee: surveillez mildiou, oidium et anthracnose.';
  }
  if (rain > 0) {
    return 'Pluie prevue: evitez de pulveriser avant la pluie.';
  }
  if (temp > 38) {
    return 'Forte chaleur: arrosez tot le matin ou en fin d apres-midi.';
  }
  if (windKmh > 20) {
    return 'Vent fort: evitez les traitements par pulverisation.';
  }
  return 'Meteo utilisable: continuez la surveillance.';
}

String _diseaseAssistantReply(
  String disease,
  String plant,
  String risk,
  String weatherLine,
) {
  final lower = disease.toLowerCase();
  final crop = plant.isEmpty ? 'la plante' : plant;
  if (lower.contains('anthracnose')) {
    return 'Anthracnose sur $crop: supprimez les parties infectees, evitez l humidite excessive et utilisez un fongicide adapte. Risque: $risk. $weatherLine';
  }
  if (lower.contains('oidium') || lower.contains('oïdium')) {
    return 'Oidium sur $crop: aerez la plantation, evitez l arrosage sur les feuilles et utilisez un traitement au soufre. Risque: $risk.';
  }
  if (lower.contains('mildiou')) {
    return 'Mildiou sur $crop: evitez l exces d eau, ameliorez le drainage et appliquez un traitement preventif. $weatherLine';
  }
  if (lower.contains('rouille')) {
    return 'Rouille sur $crop: enlevez les feuilles contaminees, surveillez la propagation et utilisez le traitement recommande. Risque: $risk.';
  }
  return 'Pour $disease sur $crop: suivez le resultat IA, observez l evolution et adaptez le traitement au risque $risk. $weatherLine';
}

bool _isAgricultureQuestion(String message) {
  return _containsAny(message, <String>[
    'culture',
    'plante',
    'arbre',
    'feuille',
    'fruit',
    'champ',
    'parcelle',
    'maladie',
    'traitement',
    'engrais',
    'fongicide',
    'insecticide',
    'arroser',
    'arrosage',
    'irrigation',
    'recolte',
    'récolte',
    'semis',
    'sol',
    'racine',
    'tomate',
    'oignon',
    'gombo',
    'piment',
    'riz',
    'mais',
    'maïs',
    'mil',
    'sorgho',
    'manguier',
    'mangue',
    'papayer',
    'papaye',
    'oranger',
    'citronnier',
    'mandarinier',
    'bananier',
    'banane',
    'coton',
    'karite',
    'karité',
    'nere',
    'néré',
    'tamarinier',
    'anacardier',
    'anthracnose',
    'oidium',
    'oïdium',
    'mildiou',
    'rouille',
    'fusariose',
    'bacteriose',
    'bactériose',
    'alternariose',
    'cercosporiose',
    'mosaique',
    'mosaïque',
    'pourriture',
  ]);
}

bool _containsAny(String text, List<String> keywords) {
  return keywords.any((keyword) => text.contains(keyword));
}

Future<void> _register(HttpRequest request) async {
  final payload = await _body(request);
  final fullName = _string(payload['fullName'] ?? payload['full_name']);
  final phone = _normalizePhone(payload['phone']);
  final email = _normalizeEmail(payload['email']);
  final password = _string(payload['password']);

  if (fullName.isEmpty || phone.isEmpty || email.isEmpty || password.isEmpty) {
    return _json(
      request.response,
      statusCode: HttpStatus.badRequest,
      body: <String, dynamic>{
        'detail': 'Nom, telephone, email et mot de passe requis.',
      },
    );
  }

  final db = await _loadDb();
  final users = (db['users'] as List).cast<Map<String, dynamic>>();
  final existing = users
      .where(
        (user) =>
            _normalizePhone(user['phone']) == phone ||
            _normalizeEmail(user['email']) == email,
      )
      .toList();
  if (existing.isNotEmpty) {
    final user = existing.first;
    user['fullName'] = fullName;
    user['phone'] = phone;
    user['email'] = email;
    user['passwordHash'] = _hash(password);
    user['updatedAt'] = DateTime.now().toIso8601String();
    await _saveDb(db);
    return _json(
      request.response,
      body: <String, dynamic>{
        'token': _token(user['id'] as String),
        'user': _publicUser(user),
        'message': 'Compte local mis a jour.',
      },
    );
  }

  final now = DateTime.now().toIso8601String();
  final user = <String, dynamic>{
    'id': _id(),
    'fullName': fullName,
    'phone': phone,
    'email': email,
    'passwordHash': _hash(password),
    'createdAt': now,
  };
  users.add(user);
  await _saveDb(db);

  final token = _token(user['id'] as String);
  return _json(
    request.response,
    statusCode: HttpStatus.created,
    body: <String, dynamic>{'token': token, 'user': _publicUser(user)},
  );
}

Future<void> _login(HttpRequest request) async {
  final payload = await _body(request);
  final rawIdentifier = payload['identifier'] ?? payload['email_or_phone'];
  final identifier = _normalizeEmail(rawIdentifier);
  final phoneIdentifier = _normalizePhone(rawIdentifier);
  final password = _string(payload['password']);

  if ((identifier.isEmpty && phoneIdentifier.isEmpty) || password.isEmpty) {
    return _json(
      request.response,
      statusCode: HttpStatus.badRequest,
      body: <String, dynamic>{'detail': 'Identifiant et mot de passe requis.'},
    );
  }

  final db = await _loadDb();
  final users = (db['users'] as List).cast<Map<String, dynamic>>();
  final matches = users.where((user) {
    final email = _normalizeEmail(user['email']);
    final phone = _normalizePhone(user['phone']);
    return email == identifier ||
        (phoneIdentifier.isNotEmpty && phone == phoneIdentifier);
  }).toList();

  if (matches.isEmpty) {
    return _json(
      request.response,
      statusCode: HttpStatus.unauthorized,
      body: <String, dynamic>{
        'detail':
            'Compte introuvable. Touchez Creer un compte pour vous inscrire.',
      },
    );
  }

  if (_string(matches.first['passwordHash']) != _hash(password)) {
    return _json(
      request.response,
      statusCode: HttpStatus.unauthorized,
      body: <String, dynamic>{
        'detail':
            'Mot de passe incorrect. Pour le backend local, recréez le compte avec le même email ou téléphone pour choisir un nouveau mot de passe.',
      },
    );
  }

  final user = matches.first;
  return _json(
    request.response,
    body: <String, dynamic>{
      'token': _token(user['id'] as String),
      'user': _publicUser(user),
    },
  );
}

Future<Map<String, dynamic>> _body(HttpRequest request) async {
  final text = await utf8.decoder.bind(request).join();
  if (text.trim().isEmpty) {
    return <String, dynamic>{};
  }
  return jsonDecode(text) as Map<String, dynamic>;
}

Future<MultipartForm> _readMultipart(HttpRequest request) async {
  final contentType = request.headers.contentType;
  final boundary = contentType?.parameters['boundary'];
  if (contentType?.mimeType != 'multipart/form-data' ||
      boundary == null ||
      boundary.isEmpty) {
    throw const RemoteDiagnosticException(
      'Le diagnostic attend une requete multipart/form-data avec une image.',
      statusCode: HttpStatus.badRequest,
    );
  }

  final builder = BytesBuilder(copy: false);
  await for (final chunk in request) {
    builder.add(chunk);
  }
  final raw = latin1.decode(builder.takeBytes());
  final marker = '--$boundary';
  final fields = <String, String>{};
  final files = <String, UploadedFile>{};

  for (final part in raw.split(marker)) {
    if (part.trim().isEmpty || part.trim() == '--') {
      continue;
    }
    var cleaned = part;
    if (cleaned.startsWith('\r\n')) {
      cleaned = cleaned.substring(2);
    }
    if (cleaned.endsWith('\r\n')) {
      cleaned = cleaned.substring(0, cleaned.length - 2);
    }
    if (cleaned.endsWith('--')) {
      cleaned = cleaned.substring(0, cleaned.length - 2);
    }
    final splitIndex = cleaned.indexOf('\r\n\r\n');
    if (splitIndex < 0) {
      continue;
    }
    final headerText = cleaned.substring(0, splitIndex);
    final content = cleaned.substring(splitIndex + 4);
    final headers = headerText
        .split('\r\n')
        .where((line) => line.contains(':'))
        .fold<Map<String, String>>(<String, String>{}, (map, line) {
          final index = line.indexOf(':');
          map[line.substring(0, index).trim().toLowerCase()] = line
              .substring(index + 1)
              .trim();
          return map;
        });
    final disposition = headers['content-disposition'] ?? '';
    final name = _headerParameter(disposition, 'name');
    if (name.isEmpty) {
      continue;
    }
    final filename = _headerParameter(disposition, 'filename');
    if (filename.isEmpty) {
      fields[name] = content.trim();
      continue;
    }
    files[name] = UploadedFile(
      fieldName: name,
      filename: filename,
      bytes: latin1.encode(content),
      contentType: headers['content-type'] ?? 'application/octet-stream',
    );
  }

  return MultipartForm(fields: fields, files: files);
}

String _headerParameter(String header, String name) {
  final expression = RegExp('$name="([^"]*)"');
  final match = expression.firstMatch(header);
  return match?.group(1) ?? '';
}

String _firstSecret(List<String> names) {
  for (final name in names) {
    final value = _secret(name);
    if (value.isNotEmpty) {
      return value;
    }
  }
  return '';
}

String _secret(String name) {
  final fromEnv = Platform.environment[name]?.trim() ?? '';
  if (_isRealSecret(fromEnv)) {
    return fromEnv;
  }
  final fromFile = _envFile()[name]?.trim() ?? '';
  if (_isRealSecret(fromFile)) {
    return fromFile;
  }
  return '';
}

bool _isRealSecret(String value) {
  return value.isNotEmpty && !value.toUpperCase().startsWith('REMPLACE_PAR');
}

Map<String, String> _envFile() {
  final cached = _envFileCache;
  if (cached != null) {
    return cached;
  }
  final file = File(apiKeysPath);
  if (!file.existsSync()) {
    _envFileCache = const <String, String>{};
    return _envFileCache!;
  }
  final values = <String, String>{};
  for (final line in file.readAsLinesSync()) {
    final trimmed = line.trim();
    if (trimmed.isEmpty || trimmed.startsWith('#') || !trimmed.contains('=')) {
      continue;
    }
    final index = trimmed.indexOf('=');
    values[trimmed.substring(0, index).trim()] = trimmed
        .substring(index + 1)
        .trim();
  }
  _envFileCache = values;
  return values;
}

MediaType _mediaTypeFor(String filename) {
  final lower = filename.toLowerCase();
  if (lower.endsWith('.png')) {
    return MediaType('image', 'png');
  }
  if (lower.endsWith('.webp')) {
    return MediaType('image', 'webp');
  }
  return MediaType('image', 'jpeg');
}

String? _localizedText(Object? value) {
  if (value == null) {
    return null;
  }
  if (value is String) {
    return value;
  }
  if (value is Map<String, dynamic>) {
    final fallback = value.values.whereType<String>();
    return value['fr'] as String? ??
        value['en'] as String? ??
        (fallback.isEmpty ? null : fallback.first);
  }
  return value.toString();
}

List<String> _stringList(Object? value) {
  if (value == null) {
    return const <String>[];
  }
  if (value is List) {
    return value
        .map(_localizedText)
        .whereType<String>()
        .where((item) => item.trim().isNotEmpty)
        .toList();
  }
  final text = _localizedText(value);
  if (text == null || text.trim().isEmpty) {
    return const <String>[];
  }
  return <String>[text];
}

Future<Map<String, dynamic>> _loadDb() async {
  final file = File(dbPath);
  if (!await file.exists()) {
    await file.parent.create(recursive: true);
    final initial = <String, dynamic>{'users': <Map<String, dynamic>>[]};
    await file.writeAsString(
      const JsonEncoder.withIndent('  ').convert(initial),
    );
    return initial;
  }
  final decoded = jsonDecode(await file.readAsString()) as Map<String, dynamic>;
  decoded['users'] ??= <Map<String, dynamic>>[];
  decoded['users'] = (decoded['users'] as List)
      .map((item) => Map<String, dynamic>.from(item as Map))
      .toList();
  return decoded;
}

Future<void> _saveDb(Map<String, dynamic> db) async {
  final file = File(dbPath);
  await file.parent.create(recursive: true);
  await file.writeAsString(const JsonEncoder.withIndent('  ').convert(db));
}

Future<void> _json(
  HttpResponse response, {
  int statusCode = HttpStatus.ok,
  required Map<String, dynamic> body,
}) async {
  response.statusCode = statusCode;
  response.headers.contentType = ContentType.json;
  response.headers.add('Access-Control-Allow-Origin', '*');
  response.headers.add(
    'Access-Control-Allow-Headers',
    'Content-Type, Authorization',
  );
  response.write(jsonEncode(body));
  await response.close();
}

Map<String, dynamic> _publicUser(Map<String, dynamic> user) {
  return <String, dynamic>{
    'id': user['id'],
    'fullName': user['fullName'],
    'phone': user['phone'],
    'email': user['email'],
    'createdAt': user['createdAt'],
  };
}

String _hash(String password) {
  return sha256.convert(utf8.encode('agricheck-dev:$password')).toString();
}

String _id() {
  final random = Random.secure();
  final millis = DateTime.now().millisecondsSinceEpoch;
  final suffix = List<int>.generate(8, (_) => random.nextInt(256));
  return '$millis-${base64UrlEncode(suffix).replaceAll('=', '')}';
}

String _token(String userId) {
  final random = Random.secure();
  final bytes = List<int>.generate(24, (_) => random.nextInt(256));
  return base64UrlEncode(utf8.encode(userId) + bytes).replaceAll('=', '');
}

String _string(Object? value) => value?.toString().trim() ?? '';

String _normalizeEmail(Object? value) {
  return _string(value).toLowerCase();
}

String _normalizePhone(Object? value) {
  return _string(value).replaceAll(RegExp(r'[^0-9+]'), '');
}
