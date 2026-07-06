import 'dart:convert';

import 'package:shared_preferences/shared_preferences.dart';

import '../models/api_config.dart';

class ConfigStore {
  static const String _storageKey = 'agricheck.api_config.v1';

  Future<ApiConfig> load() async {
    final preferences = await SharedPreferences.getInstance();
    final raw = preferences.getString(_storageKey);
    if (raw == null || raw.trim().isEmpty) {
      return const ApiConfig();
    }
    final stored = ApiConfig.fromJson(jsonDecode(raw) as Map<String, dynamic>);
    const defaults = ApiConfig();
    return stored.copyWith(
      backendBaseUrl: stored.backendBaseUrl.trim().isEmpty
          ? defaults.backendBaseUrl
          : stored.backendBaseUrl,
      openWeatherApiKey: stored.openWeatherApiKey.trim().isEmpty
          ? defaults.openWeatherApiKey
          : stored.openWeatherApiKey,
    );
  }

  Future<void> save(ApiConfig config) async {
    final preferences = await SharedPreferences.getInstance();
    await preferences.setString(_storageKey, jsonEncode(config.toJson()));
  }
}
