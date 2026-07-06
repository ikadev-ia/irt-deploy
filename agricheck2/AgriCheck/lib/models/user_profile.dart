import 'dart:convert';

class UserProfile {
  const UserProfile({
    required this.id,
    required this.fullName,
    required this.phone,
    required this.email,
    required this.createdAt,
    this.avatarUrl = '',
  });

  final String id;
  final String fullName;
  final String phone;
  final String email;
  final DateTime createdAt;
  final String avatarUrl;

  Map<String, dynamic> toJson() {
    return <String, dynamic>{
      'id': id,
      'fullName': fullName,
      'phone': phone,
      'email': email,
      'createdAt': createdAt.toIso8601String(),
      'avatarUrl': avatarUrl,
    };
  }

  factory UserProfile.fromJson(Map<String, dynamic> json) {
    return UserProfile(
      id: (json['id'] ?? json['uuid'] ?? '').toString(),
      fullName:
          json['fullName'] as String? ??
          json['full_name'] as String? ??
          json['name'] as String? ??
          '',
      phone:
          json['phone'] as String? ??
          json['telephone'] as String? ??
          json['phone_number'] as String? ??
          '',
      email: json['email'] as String? ?? '',
      createdAt:
          DateTime.tryParse(
            json['createdAt'] as String? ?? json['created_at'] as String? ?? '',
          ) ??
          DateTime.now(),
      avatarUrl:
          json['avatarUrl'] as String? ?? json['avatar_url'] as String? ?? '',
    );
  }

  String encode() => jsonEncode(toJson());

  static UserProfile decode(String source) {
    return UserProfile.fromJson(jsonDecode(source) as Map<String, dynamic>);
  }
}
