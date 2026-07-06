import 'dart:async';
import 'dart:convert';

import 'package:http/http.dart' as http;

import '../models/api_config.dart';
import '../models/chat_message.dart';
import '../models/diagnosis_result.dart';
import '../models/weather_report.dart';
import 'api_exceptions.dart';
import 'backend_url_resolver.dart';

class AssistantService {
  AssistantService({http.Client? client}) : _client = client ?? http.Client();

  final http.Client _client;
  static const Duration _timeout = Duration(seconds: 10);

  Future<String> ask({
    required String message,
    required List<ChatMessage> history,
    required ApiConfig config,
    String authToken = '',
    DiagnosisResult? latestAnalysis,
    WeatherReport? weather,
    int analysisCount = 0,
  }) async {
    if (!config.hasBackend) {
      return _localReply(
        message: message,
        latestAnalysis: latestAnalysis,
        weather: weather,
        analysisCount: analysisCount,
      );
    }
    final bases = BackendUrlResolver.baseUris(config);
    final payload = jsonEncode(<String, dynamic>{
      'message': message,
      'history': history.map((item) => item.toJson()).toList(),
      'context': <String, dynamic>{
        'analysisCount': analysisCount,
        if (latestAnalysis != null) 'latestAnalysis': latestAnalysis.toJson(),
        if (weather != null) 'weather': _weatherToJson(weather),
      },
    });
    for (final base in bases) {
      try {
        final response = await _client
            .post(
              base.resolve('/api/app/assistant/chat/'),
              headers: <String, String>{
                'Content-Type': 'application/json',
                if (authToken.trim().isNotEmpty)
                  'Authorization': 'Bearer ${authToken.trim()}',
              },
              body: payload,
            )
            .timeout(_timeout);
        if (response.statusCode < 200 || response.statusCode >= 300) {
          throw RemoteApiException(
            _serverMessage(response.body) ??
                'Assistant Agricheck indisponible pour cette question.',
            statusCode: response.statusCode,
          );
        }
        final body = jsonDecode(response.body) as Map<String, dynamic>;
        return body['reply'] as String? ??
            body['message'] as String? ??
            _localReply(
              message: message,
              latestAnalysis: latestAnalysis,
              weather: weather,
              analysisCount: analysisCount,
            );
      } catch (_) {
        // Fall back to the local agricultural assistant below.
      }
    }
    return _localReply(
      message: message,
      latestAnalysis: latestAnalysis,
      weather: weather,
      analysisCount: analysisCount,
    );
  }

  String _localReply({
    required String message,
    required DiagnosisResult? latestAnalysis,
    required WeatherReport? weather,
    required int analysisCount,
  }) {
    final text = message.trim().toLowerCase();
    if (!_isAgriculturalQuestion(text)) {
      return 'Je n ai pas cette reponse. Posez-moi une question sur les cultures, les plantes, les maladies, les traitements, l arrosage ou la meteo agricole.';
    }

    final disease = latestAnalysis?.diseaseName.trim() ?? '';
    final plant = latestAnalysis?.plantName.trim() ?? '';
    final risk = latestAnalysis?.riskLevel.trim() ?? '';
    final weatherAdvice = _weatherAdvice(weather);

    if (_containsAny(text, const <String>['anthracnose'])) {
      return 'Anthracnose: supprimez les parties infectees, evitez l humidite excessive et utilisez un fongicide adapte. $weatherAdvice';
    }
    if (_containsAny(text, const <String>['oidium', 'oïdium'])) {
      return 'Oidium: aerez la plantation, evitez l eau sur les feuilles et utilisez un traitement au soufre si necessaire. $weatherAdvice';
    }
    if (_containsAny(text, const <String>['mildiou'])) {
      return 'Mildiou: reduisez l exces d eau, ameliorez le drainage et appliquez un traitement preventif adapte. $weatherAdvice';
    }
    if (_containsAny(text, const <String>['rouille'])) {
      return 'Rouille: retirez les feuilles contaminees, surveillez la propagation et appliquez le traitement recommande. $weatherAdvice';
    }
    if (_containsAny(text, const <String>['arroser', 'arrosage', 'eau'])) {
      if (weather != null && weather.temperature > 38) {
        return 'Forte chaleur: arrosez tot le matin ou en fin d apres-midi, sans mouiller excessivement les feuilles.';
      }
      return 'Arrosez de preference le matin. Gardez le sol humide sans exces d eau, surtout si la plante montre des signes de maladie.';
    }
    if (_containsAny(text, const <String>[
      'traitement',
      'traiter',
      'produit',
      'soigner',
    ])) {
      if (latestAnalysis != null && disease.isNotEmpty) {
        return 'Pour $plant, le dernier diagnostic indique $disease avec un risque $risk. Suivez les traitements recommandes dans la page Resultat et evitez de pulveriser avant la pluie. $weatherAdvice';
      }
      return 'Commencez par lancer une analyse photo. Ensuite je pourrai adapter le traitement a la plante, a la maladie et a la meteo locale.';
    }
    if (_containsAny(text, const <String>[
      'feuille',
      'feuilles',
      'jaune',
      'jaunissent',
      'tache',
      'taches',
    ])) {
      return 'Les feuilles jaunes ou tachees peuvent venir d un exces d eau, d une carence, d une maladie fongique ou d un ravageur. Prenez une photo nette de la feuille pour lancer le diagnostic IA reel.';
    }
    if (_containsAny(text, const <String>[
      'meteo',
      'pluie',
      'vent',
      'humidite',
      'chaleur',
    ])) {
      return weatherAdvice.isEmpty
          ? 'Consultez la page Meteo agricole pour adapter l arrosage et les traitements.'
          : weatherAdvice;
    }
    if (latestAnalysis != null && plant.isNotEmpty) {
      return 'Pour $plant, surveillez les feuilles, l humidite et l evolution des symptomes. Derniere analyse: $disease, risque $risk. $weatherAdvice';
    }
    return 'Je peux aider sur les cultures, les maladies des plantes, l arrosage, la prevention et les traitements. Lancez une analyse photo pour obtenir des conseils plus precis.';
  }

  bool _isAgriculturalQuestion(String text) {
    return _containsAny(text, const <String>[
      'agri',
      'arbre',
      'arroser',
      'arrosage',
      'bananier',
      'champ',
      'culture',
      'feuille',
      'feuilles',
      'fongicide',
      'maladie',
      'mildiou',
      'mil',
      'mais',
      'maïs',
      'manguier',
      'meteo',
      'oignon',
      'oidium',
      'oïdium',
      'plante',
      'pluie',
      'recolte',
      'récolte',
      'riz',
      'rouille',
      'sorgho',
      'tache',
      'taches',
      'tomate',
      'traitement',
      'traiter',
    ]);
  }

  bool _containsAny(String text, List<String> keywords) {
    return keywords.any(text.contains);
  }

  String _weatherAdvice(WeatherReport? weather) {
    if (weather == null) {
      return '';
    }
    if (weather.humidity > 80) {
      return 'Humidite elevee: surveillez les maladies fongiques comme le mildiou, l oidium ou l anthracnose.';
    }
    if (weather.precipitation > 0) {
      return 'Pluie prevue: evitez de pulveriser les traitements avant la pluie.';
    }
    if (weather.temperature > 38) {
      return 'Forte chaleur: arrosez tot le matin ou en fin d apres-midi.';
    }
    if (weather.windSpeed * 3.6 > 20) {
      return 'Vent fort: evitez les traitements par pulverisation.';
    }
    return 'La meteo actuelle ne montre pas de risque agricole majeur.';
  }

  Map<String, dynamic> _weatherToJson(WeatherReport weather) {
    return <String, dynamic>{
      'cityLabel': weather.cityLabel,
      'temperature': weather.temperature,
      'humidity': weather.humidity,
      'windSpeed': weather.windSpeed,
      'precipitation': weather.precipitation,
      'description': weather.description,
      'periods': weather.periods
          .map(
            (period) => <String, dynamic>{
              'label': period.label,
              'temperature': period.temperature,
              'humidity': period.humidity,
              'windSpeed': period.windSpeed,
              'precipitation': period.precipitation,
              'description': period.description,
            },
          )
          .toList(),
    };
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
