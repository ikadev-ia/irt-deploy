import 'package:flutter/material.dart';

import '../main.dart';
import '../models/chat_message.dart';
import '../theme/app_theme.dart';

class AssistantScreen extends StatefulWidget {
  const AssistantScreen({super.key});

  @override
  State<AssistantScreen> createState() => _AssistantScreenState();
}

class _AssistantScreenState extends State<AssistantScreen> {
  final TextEditingController _controller = TextEditingController();
  final List<ChatMessage> _messages = <ChatMessage>[
    ChatMessage(
      text:
          'Posez une question agricole sur vos cultures, arbres ou traitements.',
      isUser: false,
      createdAt: DateTime.now(),
    ),
  ];
  bool _isLoading = false;

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  Future<void> _send() async {
    await _sendText(_controller.text.trim());
  }

  Future<void> _sendText(String text) async {
    if (text.isEmpty || _isLoading) {
      return;
    }
    final userMessage = ChatMessage(
      text: text,
      isUser: true,
      createdAt: DateTime.now(),
    );
    setState(() {
      _messages.add(userMessage);
      _controller.clear();
      _isLoading = true;
    });
    try {
      final reply = await AgricheckScope.of(
        context,
      ).askAssistant(text, _messages);
      if (!mounted) {
        return;
      }
      setState(() {
        _messages.add(
          ChatMessage(text: reply, isUser: false, createdAt: DateTime.now()),
        );
      });
    } catch (error) {
      if (!mounted) {
        return;
      }
      setState(() {
        _messages.add(
          ChatMessage(
            text: error.toString(),
            isUser: false,
            createdAt: DateTime.now(),
          ),
        );
      });
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Assistant IA')),
      body: Column(
        children: <Widget>[
          SizedBox(
            height: 54,
            child: ListView(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.fromLTRB(12, 8, 12, 6),
              children: <Widget>[
                _SuggestionChip(
                  label: 'Feuilles jaunes',
                  onTap: () => _sendText('Pourquoi mes feuilles jaunissent ?'),
                ),
                _SuggestionChip(
                  label: 'Anthracnose',
                  onTap: () => _sendText('Comment traiter l anthracnose ?'),
                ),
                _SuggestionChip(
                  label: 'Arrosage',
                  onTap: () => _sendText('Quand dois-je arroser ?'),
                ),
                _SuggestionChip(
                  label: 'Traitement',
                  onTap: () => _sendText('Quel traitement utiliser ?'),
                ),
                _SuggestionChip(
                  label: 'Mildiou',
                  onTap: () => _sendText('Comment eviter le mildiou ?'),
                ),
              ],
            ),
          ),
          Expanded(
            child: ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: _messages.length,
              itemBuilder: (context, index) {
                final message = _messages[index];
                return Align(
                  alignment: message.isUser
                      ? Alignment.centerRight
                      : Alignment.centerLeft,
                  child: ConstrainedBox(
                    constraints: const BoxConstraints(maxWidth: 320),
                    child: DecoratedBox(
                      decoration: BoxDecoration(
                        color: message.isUser ? AppTheme.leaf : Colors.white,
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Padding(
                        padding: const EdgeInsets.all(12),
                        child: Text(
                          message.text,
                          style: TextStyle(
                            color: message.isUser
                                ? Colors.white
                                : AppTheme.soil,
                          ),
                        ),
                      ),
                    ),
                  ),
                );
              },
            ),
          ),
          SafeArea(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(12, 8, 12, 12),
              child: Row(
                children: <Widget>[
                  Expanded(
                    child: TextField(
                      controller: _controller,
                      minLines: 1,
                      maxLines: 3,
                      decoration: const InputDecoration(
                        hintText: 'Question agricole...',
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  IconButton.filled(
                    onPressed: _isLoading ? null : _send,
                    icon: _isLoading
                        ? const SizedBox(
                            width: 18,
                            height: 18,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.send),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _SuggestionChip extends StatelessWidget {
  const _SuggestionChip({required this.label, required this.onTap});

  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: ActionChip(
        avatar: const Icon(Icons.auto_awesome, size: 18),
        label: Text(label),
        onPressed: onTap,
      ),
    );
  }
}
