import 'package:flutter/material.dart';

class PercentChip extends StatelessWidget {
  const PercentChip({required this.value, required this.label, super.key});

  final double value;
  final String label;

  @override
  Widget build(BuildContext context) {
    final percent = (value * 100).clamp(0, 100).round();
    return DecoratedBox(
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.primary.withValues(alpha: 0.10),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        child: Text(
          '$label $percent %',
          style: const TextStyle(fontWeight: FontWeight.w700),
        ),
      ),
    );
  }
}
