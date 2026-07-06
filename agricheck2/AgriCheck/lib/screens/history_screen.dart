import 'dart:io';

import 'package:flutter/material.dart';

import '../main.dart';
import '../models/diagnosis_result.dart';
import '../widgets/empty_state.dart';
import 'result_screen.dart';

class HistoryScreen extends StatefulWidget {
  const HistoryScreen({super.key});

  @override
  State<HistoryScreen> createState() => _HistoryScreenState();
}

class _HistoryScreenState extends State<HistoryScreen> {
  final TextEditingController _searchController = TextEditingController();
  String _filter = 'all';

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final history = AgricheckScope.of(context).history;

    debugPrint("History = ${history.length}");

    final filtered = _filtered(history);
    debugPrint("Filtered = ${filtered.length}");

    if (history.isEmpty) {
      return const Center(
        child: EmptyState(
          icon: Icons.history_outlined,
          title: 'Historique vide',
          message:
              'Chaque diagnostic sera conserve ici avec sa photo, sa date et son resultat.',
        ),
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: filtered.length,
      itemBuilder: (context, index) {
        final item = filtered[index];

        return Card(
          child: ListTile(
            title: Text(item.plantName),
            subtitle: Text(item.diseaseName),
            trailing: Text(item.riskLevel),

          ),
        );
      },
    );
  }

  List<DiagnosisResult> _filtered(List<DiagnosisResult> history) {
    final query = _searchController.text.trim().toLowerCase();
    return history.where((item) {
      final matchesFilter = switch (_filter) {
        'disease' => !item.isHealthy,
        'healthy' => item.isHealthy,
        _ => true,
      };
      final matchesQuery =
          query.isEmpty ||
          item.plantName.toLowerCase().contains(query) ||
          item.diseaseName.toLowerCase().contains(query);
      return matchesFilter && matchesQuery;
    }).toList();
  }

  String _formatDate(DateTime date) {
    final day = date.day.toString().padLeft(2, '0');
    final month = date.month.toString().padLeft(2, '0');
    final hour = date.hour.toString().padLeft(2, '0');
    final minute = date.minute.toString().padLeft(2, '0');
    return '$day/$month/${date.year} $hour:$minute';
  }
}

class _HistoryThumbnail extends StatelessWidget {
  const _HistoryThumbnail({required this.result});

  final DiagnosisResult result;

  @override
  Widget build(BuildContext context) {
    final file = result.imagePath.trim().isEmpty
        ? null
        : File(result.imagePath);
    if (file != null && file.existsSync()) {
      return ClipRRect(
        borderRadius: BorderRadius.circular(8),
        child: Image.file(file, width: 54, height: 54, fit: BoxFit.cover),
      );
    }
    return CircleAvatar(
      child: Icon(
        result.isHealthy ? Icons.eco_outlined : Icons.warning_amber_rounded,
      ),
    );
  }
}
