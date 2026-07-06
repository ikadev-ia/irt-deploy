import 'package:shared_preferences/shared_preferences.dart';

import '../models/diagnosis_result.dart';

class HistoryStore {
  static const String _storageKey = 'agricheck.history.v1';

  Future<List<DiagnosisResult>> load() async {
    final preferences = await SharedPreferences.getInstance();
    final rows = preferences.getStringList(_storageKey) ?? const <String>[];
    return rows.map(DiagnosisResult.decode).toList()
      ..sort((a, b) => b.createdAt.compareTo(a.createdAt));
  }

  Future<void> save(List<DiagnosisResult> history) async {
    final preferences = await SharedPreferences.getInstance();
    final rows = history.take(80).map((item) => item.encode()).toList();
    await preferences.setStringList(_storageKey, rows);
  }
}
