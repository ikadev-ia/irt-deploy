class ChatMessage {
  const ChatMessage({
    required this.text,
    required this.isUser,
    required this.createdAt,
  });

  final String text;
  final bool isUser;
  final DateTime createdAt;

  Map<String, dynamic> toJson() {
    return <String, dynamic>{
      'role': isUser ? 'user' : 'assistant',
      'content': text,
      'createdAt': createdAt.toIso8601String(),
    };
  }
}
