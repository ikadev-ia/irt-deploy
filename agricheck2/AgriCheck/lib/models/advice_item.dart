
class AdviceItem {
  const AdviceItem({
    required this.id,
    required this.title,
    required this.message,
    this.category = '',
    this.crop = '',
    this.createdAt,
  });

  final String id;
  final String title;
  final String message;
  final String category;
  final String crop;
  final DateTime? createdAt;

  factory AdviceItem.fromJson(Map<String, dynamic> json) {
    return AdviceItem(
      id: (json['id'] ?? '').toString(),
      title: json['title'] as String? ?? json['titre'] as String? ?? '',
      message:
          json['message'] as String? ??
          json['body'] as String? ??
          json['content'] as String? ??
          '',
      category:
          json['category'] as String? ?? json['categorie'] as String? ?? '',
      crop: json['crop'] as String? ?? json['culture'] as String? ?? '',
      createdAt: DateTime.tryParse(
        json['createdAt'] as String? ?? json['created_at'] as String? ?? '',
      ),
    );
  }
}
