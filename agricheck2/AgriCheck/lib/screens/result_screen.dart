import 'dart:io';

import 'package:flutter/material.dart';

import '../models/diagnosis_result.dart';
import '../theme/app_theme.dart';
import '../widgets/percent_chip.dart';
import '../widgets/section_header.dart';
import 'treatments_screen.dart';
import 'package:flutter/foundation.dart' show kIsWeb;

class ResultScreen extends StatelessWidget {
  const ResultScreen({required this.result, super.key});

  final DiagnosisResult result;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Resultat')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: <Widget>[
          if (_hasImage)
            ClipRRect(
              borderRadius: BorderRadius.circular(8),
              child: AspectRatio(
                aspectRatio: 4 / 3,
                child:kIsWeb
                     ? Image.network(result.imagePath, fit: BoxFit.cover)
                     : Image.file(File(result.imagePath), fit: BoxFit.cover),
              ),
            ),
          if (_hasImage) const SizedBox(height: 12),
          Card(
            child: Padding(
              padding: const EdgeInsets.all(12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: <Widget>[
                  Row(
                    children: <Widget>[
                      Icon(
                        result.isHealthy
                            ? Icons.verified_user_outlined
                            : Icons.warning_amber_rounded,
                        color: result.isHealthy
                            ? AppTheme.leaf
                            : AppTheme.warning,
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Text(
                          result.plantName,
                          style: Theme.of(context).textTheme.headlineSmall
                              ?.copyWith(fontWeight: FontWeight.w900),
                        ),
                      ),
                    ],
                  ),
                  if (result.scientificName.isNotEmpty) ...<Widget>[
                    const SizedBox(height: 8),
                    Text(
                      result.scientificName,
                      style: Theme.of(context).textTheme.titleMedium,
                    ),
                  ],
                  if (result.family.isNotEmpty) ...<Widget>[
                    const SizedBox(height: 4),
                    Text('Famille botanique: ${result.family}'),
                  ],
                  const SizedBox(height: 14),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: <Widget>[
                      PercentChip(
                        value: result.plantConfidence,
                        label: 'Plante',
                      ),
                      PercentChip(
                        value: result.diseaseConfidence,
                        label: 'Confiance',
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
          const SectionHeader('Diagnostic'),
          Card(
            child: Padding(
              padding: const EdgeInsets.all(12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: <Widget>[
                  Text(
                    result.diseaseName,
                    style: Theme.of(context).textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                  const SizedBox(height: 8),
                  _InfoRow(
                    icon: Icons.priority_high_outlined,
                    label: 'Gravite',
                    value: result.riskLevel,
                  ),
                  _InfoRow(
                    icon: Icons.schedule_outlined,
                    label: 'Analyse',
                    value: _formatDateTime(result.createdAt),
                  ),
                  if (result.locationLabel.isNotEmpty)
                    _InfoRow(
                      icon: Icons.location_on_outlined,
                      label: 'Localisation',
                      value: result.locationLabel,
                    ),
                ],
              ),
            ),
          ),
          if (result.symptoms.isNotEmpty)
            _ListSection(title: 'Symptomes observes', items: result.symptoms),
          if (result.causes.isNotEmpty)
            _ListSection(title: 'Causes probables', items: result.causes),
          const SizedBox(height: 12),
          FilledButton.icon(
            onPressed: () => Navigator.of(context).push(
              MaterialPageRoute<void>(
                builder: (_) => TreatmentsScreen(result: result),
              ),
            ),
            icon: const Icon(Icons.medical_services_outlined),
            label: const Text('Voir les traitements'),
          ),
        ],
      ),
    );
  }

  bool get _hasImage =>
      result.imagePath.trim().isNotEmpty ;


  String _formatDateTime(DateTime date) {
    final day = date.day.toString().padLeft(2, '0');
    final month = date.month.toString().padLeft(2, '0');
    final hour = date.hour.toString().padLeft(2, '0');
    final minute = date.minute.toString().padLeft(2, '0');
    return '$day/$month/${date.year} a $hour:$minute';
  }
}

class _InfoRow extends StatelessWidget {
  const _InfoRow({
    required this.icon,
    required this.label,
    required this.value,
  });

  final IconData icon;
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Icon(icon, size: 20, color: AppTheme.leaf),
          const SizedBox(width: 8),
          Expanded(child: Text('$label: $value')),
        ],
      ),
    );
  }
}

class _ListSection extends StatelessWidget {
  const _ListSection({required this.title, required this.items});

  final String title;
  final List<String> items;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: <Widget>[
        SectionHeader(title),
        Card(
          elevation: 3,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(15),
          ),
          child: Padding(
            padding: const EdgeInsets.all(12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: items.map((item) {
                return Padding(
                  padding: const EdgeInsets.only(bottom: 8),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text("• "),
                      Expanded(child: Text(item)),
                    ],
                  ),
                );
              }).toList(),
            ),
          ),
        )

      ],
    );
  }
}
