class ConfigException implements Exception {
  const ConfigException(this.message);

  final String message;

  @override
  String toString() => message;
}

class RemoteApiException implements Exception {
  const RemoteApiException(this.message, {this.statusCode});

  final String message;
  final int? statusCode;

  @override
  String toString() {
    if (statusCode == null) {
      return message;
    }
    return '$message (HTTP $statusCode)';
  }
}
