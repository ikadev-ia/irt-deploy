import 'package:flutter/material.dart';

import '../models/diagnosis_result.dart';
import '../theme/app_theme.dart';
import '../widgets/empty_state.dart';
import '../widgets/section_header.dart';

class TreatmentsScreen extends StatelessWidget {
  const TreatmentsScreen({required this.result, super.key});

  final DiagnosisResult result;

  @override
  Widget build(BuildContext context) {
    final hasAny =
        result.biologicalTreatments.isNotEmpty ||
        result.chemicalTreatments.isNotEmpty ||
        result.prevention.isNotEmpty ||
        result.dosage.isNotEmpty ||
        result.frequency.isNotEmpty ||
        result.urgencyLevel.isNotEmpty;

    return Scaffold(
      appBar: AppBar(title: const Text('Traitements')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: <Widget>[
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: <Widget>[
                  Text(
                    result.diseaseName,
                    style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text('Culture: ${result.plantName}'),
                  const SizedBox(height: 8),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: <Widget>[
                      _TreatmentBadge(
                        icon: Icons.priority_high_outlined,
                        text: result.urgencyLevel.isEmpty
                            ? 'Urgence: ${result.riskLevel}'
                            : result.urgencyLevel,
                      ),
                      if (result.frequency.isNotEmpty)
                        _TreatmentBadge(
                          icon: Icons.repeat_outlined,
                          text: result.frequency,
                        ),
                    ],
                  ),
                ],
              ),
            ),
          ),
          if (!hasAny)
            const EmptyState(
              icon: Icons.medical_services_outlined,
              title: 'Traitement non fourni',
              message:
                  'Aucun protocole complet n a ete recu. Faites confirmer par un conseiller agricole avant traitement.',
            )
          else ...<Widget>[
            if (result.biologicalTreatments.isNotEmpty)
              _TreatmentSection(
                title: 'Solutions naturelles',
                items: result.biologicalTreatments,
                icon: Icons.spa_outlined,
              ),
            if (result.chemicalTreatments.isNotEmpty)
              _TreatmentSection(
                title: 'Produits recommandes',
                items: result.chemicalTreatments,
                icon: Icons.science_outlined,
              ),
            if (result.dosage.isNotEmpty) ...<Widget>[
              const SectionHeader('Dosage recommande'),
              Card(
                child: ListTile(
                  leading: const Icon(Icons.medication_outlined),
                  title: Text(result.dosage),
                ),
              ),
            ],
            if (result.frequency.isNotEmpty) ...<Widget>[
              const SectionHeader('Frequence d application'),
              Card(
                child: ListTile(
                  leading: const Icon(Icons.event_repeat_outlined),
                  title: Text(result.frequency),
                ),
              ),
            ],
            if (result.prevention.isNotEmpty)
              _TreatmentSection(
                title: 'Conseils de prevention',
                items: result.prevention,
                icon: Icons.health_and_safety_outlined,
              ),
          ],
          const SizedBox(height: 10),
          const Text(
            'Les produits et dosages doivent etre valides selon les produits homologues disponibles localement.',
          ),
        ],
      ),
    );
  }
}

class _TreatmentBadge extends StatelessWidget {
  const _TreatmentBadge({required this.icon, required this.text});

  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        color: AppTheme.field,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: <Widget>[
            Icon(icon, size: 18, color: AppTheme.leaf),
            const SizedBox(width: 6),
            ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 260),
              child: Text(text),
            ),
          ],
        ),
      ),
    );
  }
}

class _TreatmentSection extends StatelessWidget {
  const _TreatmentSection({
    required this.title,
    required this.items,
    required this.icon,
  });

  final String title;
  final List<String> items;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: <Widget>[
        SectionHeader(title),
        Card(
          child: Padding(
            padding: const EdgeInsets.symmetric(vertical: 8),
            child: Column(
              children: items
                  .map(
                    (item) => ListTile(leading: Icon(icon), title: Text(item)),
                  )
                  .toList(),
            ),
          ),
        ),
      ],
    );
  }
}
