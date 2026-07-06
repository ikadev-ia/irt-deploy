import 'package:geolocator/geolocator.dart';

import '../models/api_config.dart';

class LocationService {
  static const Duration _quickTimeout = Duration(seconds: 3);
  static const Duration _positionTimeout = Duration(seconds: 5);

  Future<ApiConfig> withCurrentLocation(ApiConfig config) async {
    try {
      final enabled = await Geolocator.isLocationServiceEnabled().timeout(
        _quickTimeout,
      );
      if (!enabled) {
        return config;
      }
      var permission = await Geolocator.checkPermission().timeout(
        _quickTimeout,
      );
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission().timeout(
          _quickTimeout,
        );
      }
      if (permission == LocationPermission.denied ||
          permission == LocationPermission.deniedForever) {
        return config;
      }
      final position = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.medium,
          timeLimit: _positionTimeout,
        ),
      ).timeout(_positionTimeout);
      return config.copyWith(
        latitude: position.latitude,
        longitude: position.longitude,
      );
    } catch (_) {
      return config;
    }
  }
}
