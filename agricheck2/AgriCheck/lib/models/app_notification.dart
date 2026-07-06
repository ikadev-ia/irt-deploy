class AppNotification {
  const AppNotification({
    required this.id,
    required this.title,
    required this.message,
    required this.createdAt,
    this.type = '',
    this.isRead = false,
  });

  final String id;
  final String title;
  final String message;
  final DateTime createdAt;
  final String type;
  final bool isRead;

  factory AppNotification.fromJson(Map<String, dynamic> json) {
    return AppNotification(
      id: (json['id'] ?? '').toString(),
      title: json['title'] as String? ?? json['titre'] as String? ?? '',
      message:
          json['message'] as String? ??
          json['body'] as String? ??
          json['content'] as String? ??
          '',
      createdAt:
          DateTime.tryParse(
            json['createdAt'] as String? ?? json['created_at'] as String? ?? '',
          ) ??
          DateTime.now(),
      type: json['type'] as String? ?? '',
      isRead: json['isRead'] as bool? ?? json['is_read'] as bool? ?? false,
    );
  }
}
