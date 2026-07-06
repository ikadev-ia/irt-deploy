import 'dart:convert';

class PlantIdentity {
  const PlantIdentity({
    required this.commonName,
    required this.scientificName,
    required this.confidence,
    this.family = '',
    this.description = '',
  });

  final String commonName;
  final String scientificName;
  final double confidence;
  final String family;
  final String description;
}

class HealthAssessment {
  const HealthAssessment({
    required this.diseaseName,
    required this.confidence,
    required this.isHealthy,
    this.description = '',
    this.symptoms = const <String>[],
    this.causes = const <String>[],
    this.biologicalTreatments = const <String>[],
    this.chemicalTreatments = const <String>[],
    this.prevention = const <String>[],
    this.dosage = '',
    this.provider = '',
  });

  final String diseaseName;
  final double confidence;
  final bool isHealthy;
  final String description;
  final List<String> symptoms;
  final List<String> causes;
  final List<String> biologicalTreatments;
  final List<String> chemicalTreatments;
  final List<String> prevention;
  final String dosage;
  final String provider;
}

class DiagnosisResult {
  const DiagnosisResult({
    required this.id,
    required this.createdAt,
    required this.imageName,
    this.imagePath = '',
    required this.plantName,
    required this.scientificName,
    required this.plantConfidence,
    required this.diseaseName,
    required this.diseaseConfidence,
    required this.riskLevel,
    required this.isHealthy,
    required this.analysedAt,
    this.plantDescription = '',
    this.symptoms = const <String>[],
    this.causes = const <String>[],
    this.biologicalTreatments = const <String>[],
    this.chemicalTreatments = const <String>[],
    this.prevention = const <String>[],
    this.dosage = '',
    this.frequency = '',
    this.urgencyLevel = '',
    this.locationLabel = '',
    this.latitude,
    this.longitude,
    this.family = '',
    this.source = '',
  });

  final String id;
  final DateTime createdAt;
  final String imageName;
  final String imagePath;
  final String plantName;
  final String scientificName;
  final double plantConfidence;
  final String diseaseName;
  final double diseaseConfidence;
  final String riskLevel;
  final bool isHealthy;
  final DateTime analysedAt ;
  final String plantDescription;
  final List<String> symptoms;
  final List<String> causes;
  final List<String> biologicalTreatments;
  final List<String> chemicalTreatments;
  final List<String> prevention;
  final String dosage;
  final String frequency;
  final String urgencyLevel;
  final String locationLabel;
  final double? latitude;
  final double? longitude;
  final String family;
  final String source;

  Map<String, dynamic> toJson() {
    return <String, dynamic>{
      'id': id,
      'createdAt': createdAt.toIso8601String(),
      'imageName': imageName,
      'imagePath': imagePath,
      'plantName': plantName,
      'scientificName': scientificName,
      'plantConfidence': plantConfidence,
      'diseaseName': diseaseName,
      'diseaseConfidence': diseaseConfidence,
      'riskLevel': riskLevel,
      'isHealthy': isHealthy,
      'plantDescription': plantDescription,
      'symptoms': symptoms,
      'causes': causes,
      'biologicalTreatments': biologicalTreatments,
      'chemicalTreatments': chemicalTreatments,
      'prevention': prevention,
      'dosage': dosage,
      'frequency': frequency,
      'urgencyLevel': urgencyLevel,
      'locationLabel': locationLabel,
      'latitude': latitude,
      'longitude': longitude,
      'family': family,
      'source': source,
      'analysedAt': analysedAt.toIso8601String(),
    };
  }

  factory DiagnosisResult.fromJson(Map<String, dynamic> json) {
    return DiagnosisResult(
      id:
          json['id'] as String? ??
          DateTime.now().microsecondsSinceEpoch.toString(),
      createdAt:
          DateTime.tryParse(
            json['createdAt'] as String? ??
                json['created_at'] as String? ??
                json['date_analyse'] as String? ??
                json['analyzed_at'] as String? ??
                '',
          ) ??
          DateTime.now(),
      imageName:
          json['imageName'] as String? ??
          json['image_name'] as String? ??
          json['image'] as String? ??
          '',
      imagePath:
          json['imagePath'] as String? ??
          json['image_path'] as String? ??
          json['image_url'] as String? ??
          '',
      plantName:
          json['plantName'] as String? ??
          json['plant_name'] as String? ??
          json['nom_plante'] as String? ??
          'Plante non identifiee',
      scientificName:
          json['scientificName'] as String? ??
          json['scientific_name'] as String? ??
          json['nom_scientifique'] as String? ??
          '',
      plantConfidence: _toDouble(
        json['plantConfidence'] ?? json['plant_confidence'],
      ),
      diseaseName:
          json['diseaseName'] as String? ??
          json['disease_name'] as String? ??
          json['maladie_detectee'] as String? ??
          'Diagnostic non disponible',
      diseaseConfidence: _toDouble(
        json['diseaseConfidence'] ??
            json['disease_confidence'] ??
            json['confiance_ia'] ??
            json['confidence'],
      ),
      riskLevel:
          json['riskLevel'] as String? ??
          json['risk_level'] as String? ??
          json['niveau_risque'] as String? ??
          'Indetermine',
      isHealthy: json['isHealthy'] as bool? ?? false,
      analysedAt:
      DateTime.tryParse(
        json['analysedAt'] as String? ??
            json['analysed_at'] as String? ??
            json['date_analyse'] as String? ??
            '',
      ) ??
          DateTime.now(),
      plantDescription: json['plantDescription'] as String? ?? '',
      symptoms: _toStringList(json['symptoms']),
      causes: _toStringList(json['causes']),
      biologicalTreatments: _toStringList(
        json['biologicalTreatments'] ??
            json['biological_treatments'] ??
            json['natural_solutions'],
      ),
      chemicalTreatments: _toStringList(
        json['chemicalTreatments'] ??
            json['chemical_treatments'] ??
            json['recommended_products'] ??
            json['traitements_recommandes'],
      ),
      prevention: _toStringList(json['prevention']),
      dosage:
          json['dosage'] as String? ??
          json['recommended_dosage'] as String? ??
          '',
      frequency:
          json['frequency'] as String? ??
          json['application_frequency'] as String? ??
          '',
      urgencyLevel:
          json['urgencyLevel'] as String? ??
          json['urgency_level'] as String? ??
          '',
      locationLabel:
          json['locationLabel'] as String? ??
          json['location_label'] as String? ??
          json['gps'] as String? ??
          '',
      latitude: _nullableDouble(json['latitude']),
      longitude: _nullableDouble(json['longitude']),
      family:
          json['family'] as String? ??
          json['botanical_family'] as String? ??
          '',
      source: json['source'] as String? ?? '',
    );
  }

  String encode() => jsonEncode(toJson());

  static DiagnosisResult decode(String source) {
    return DiagnosisResult.fromJson(jsonDecode(source) as Map<String, dynamic>);
  }

  static double _toDouble(Object? value) {
    if (value is num) {
      return value.toDouble();
    }
    return double.tryParse(value?.toString() ?? '') ?? 0;
  }

  static double? _nullableDouble(Object? value) {
    if (value == null) {
      return null;
    }
    if (value is num) {
      return value.toDouble();
    }
    return double.tryParse(value.toString());
  }

  static List<String> _toStringList(Object? value) {
    if (value is List) {
      return value
          .map((item) => item.toString())
          .where((item) => item.trim().isNotEmpty)
          .toList();
    }
    if (value is String && value.trim().isNotEmpty) {
      return <String>[value];
    }
    return const <String>[];
  }
}
