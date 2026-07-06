import '../models/advice_item.dart';
import '../models/diagnosis_result.dart';
import '../models/weather_report.dart';

class AgricheckAdviceEngine {
  List<AdviceItem> build({
    required List<DiagnosisResult> history,
    required WeatherReport? weather,
  }) {
    final latest = history.isEmpty ? null : history.first;
    final items = <AdviceItem>[
      _dailyAdvice(latest, weather),
      _weatherAdvice(weather),
      _diseaseAdvice(latest),
      _plantAdvice(latest, history),
    ];
    return items;
  }

  AdviceItem _dailyAdvice(DiagnosisResult? latest, WeatherReport? weather) {
    final plant = _plant(latest);
    final disease = _disease(latest);
    if (latest == null && weather == null) {
      return const AdviceItem(
        id: 'daily-empty',
        title: 'Conseil du jour',
        category: 'General',
        message:
            'Analysez une plante et actualisez la meteo pour recevoir un conseil adapte.',
      );
    }
    if (latest != null && latest.riskLevel.toLowerCase().contains('eleve')) {
      return AdviceItem(
        id: 'daily-risk',
        title: 'Conseil du jour',
        category: 'Risque',
        crop: plant,
        message:
            'Risque eleve sur $plant : inspectez la parcelle aujourd hui et evitez de laisser les parties malades sur place.',
      );
    }
    if (weather != null && weather.temperature > 38) {
      return AdviceItem(
        id: 'daily-heat',
        title: 'Conseil du jour',
        category: 'Meteo',
        crop: plant,
        message: 'Forte chaleur : arrosez tôt le matin ou en fin d apres-midi.',
      );
    }
    return AdviceItem(
      id: 'daily-normal',
      title: 'Conseil du jour',
      category: 'Suivi',
      crop: plant,
      message: latest == null
          ? 'Observez vos cultures aujourd hui et lancez une analyse si vous voyez des taches, jaunissements ou pourritures.'
          : 'Surveillez $plant et notez l evolution de $disease dans l historique Agricheck.',
    );
  }

  AdviceItem _weatherAdvice(WeatherReport? weather) {
    if (weather == null) {
      return const AdviceItem(
        id: 'weather-empty',
        title: 'Conseil meteo',
        category: 'Meteo',
        message:
            'Actualisez la meteo agricole pour recevoir un conseil selon humidite, pluie, chaleur et vent.',
      );
    }
    final messages = <String>[];
    if (weather.humidity > 80) {
      messages.add(
        'Humidité élevée : surveillez les maladies fongiques comme le mildiou, l’oïdium ou l’anthracnose.',
      );
    }
    if (_rainExpected(weather)) {
      messages.add(
        'Pluie prévue : évitez de pulvériser les traitements avant la pluie.',
      );
    }
    if (weather.temperature > 38) {
      messages.add(
        'Forte chaleur : arrosez tôt le matin ou en fin d’après-midi.',
      );
    }
    if (_windKmh(weather.windSpeed) > 20) {
      messages.add('Vent fort : évitez les traitements par pulvérisation.');
    }
    if (messages.isEmpty) {
      messages.add(
        'Meteo favorable : continuez la surveillance et evitez les traitements inutiles.',
      );
    }
    return AdviceItem(
      id: 'weather',
      title: 'Conseil meteo',
      category: 'Meteo',
      message: messages.join('\n'),
    );
  }

  AdviceItem _diseaseAdvice(DiagnosisResult? latest) {
    if (latest == null) {
      return const AdviceItem(
        id: 'disease-empty',
        title: 'Conseil selon la maladie',
        category: 'Maladie',
        message:
            'Aucune maladie detectee pour le moment. Lancez une analyse photo pour recevoir un conseil cible.',
      );
    }
    final disease = latest.diseaseName.toLowerCase();
    final advice = _diseaseRules(disease);
    return AdviceItem(
      id: 'disease',
      title: 'Conseil selon la maladie',
      category: latest.riskLevel,
      crop: latest.plantName,
      message: advice.isEmpty
          ? 'Pour ${latest.diseaseName}, suivez les traitements recommandes dans le resultat IA et surveillez le niveau de risque ${latest.riskLevel}.'
          : advice.join('\n'),
    );
  }

  AdviceItem _plantAdvice(
    DiagnosisResult? latest,
    List<DiagnosisResult> history,
  ) {
    if (latest == null) {
      return const AdviceItem(
        id: 'plant-empty',
        title: 'Conseil selon la plante',
        category: 'Plante',
        message:
            'La plante detectee apparaitra ici apres votre premiere analyse.',
      );
    }
    final samePlantCount = history
        .where((item) => item.plantName == latest.plantName)
        .length;
    final diseaseCount = history
        .where((item) => item.plantName == latest.plantName && !item.isHealthy)
        .length;
    return AdviceItem(
      id: 'plant',
      title: 'Conseil selon la plante',
      category: 'Historique',
      crop: latest.plantName,
      message:
          '${latest.plantName} a ete analysee $samePlantCount fois. Maladies observees : $diseaseCount. Continuez le suivi et comparez les nouvelles photos avec l historique.',
    );
  }

  List<String> _diseaseRules(String disease) {
    if (disease.contains('anthracnose')) {
      return const <String>[
        'Supprimer les parties infectées',
        'Éviter l’humidité excessive',
        'Utiliser un fongicide adapté',
      ];
    }
    if (disease.contains('oidium') || disease.contains('oïdium')) {
      return const <String>[
        'Aérer la plantation',
        'Éviter l’arrosage sur les feuilles',
        'Utiliser un traitement au soufre',
      ];
    }
    if (disease.contains('mildiou')) {
      return const <String>[
        'Éviter l’excès d’eau',
        'Améliorer le drainage',
        'Appliquer un traitement préventif',
      ];
    }
    if (disease.contains('rouille')) {
      return const <String>[
        'Enlever les feuilles contaminées',
        'Surveiller la propagation',
        'Utiliser un traitement recommandé',
      ];
    }
    return const <String>[];
  }

  bool _rainExpected(WeatherReport weather) {
    if (weather.precipitation > 0) {
      return true;
    }
    return weather.periods.any((period) => period.precipitation > 0) ||
        weather.days.any((day) => day.precipitation > 0);
  }

  double _windKmh(double windMs) => windMs * 3.6;

  String _plant(DiagnosisResult? latest) {
    final value = latest?.plantName.trim() ?? '';
    return value.isEmpty ? 'la culture' : value;
  }

  String _disease(DiagnosisResult? latest) {
    final value = latest?.diseaseName.trim() ?? '';
    return value.isEmpty ? 'le probleme observe' : value;
  }
}
