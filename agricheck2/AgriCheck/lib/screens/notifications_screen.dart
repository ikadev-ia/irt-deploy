import 'package:flutter/material.dart';

import '../main.dart';
import '../models/app_notification.dart';
import '../widgets/empty_state.dart';

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({super.key});

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  late Future<List<AppNotification>> _future;

  @override
  void initState() {
    super.initState();
    _future = AgricheckScope.read(context).fetchNotifications();
  }

  void _reload() {
    setState(() {
      _future = AgricheckScope.read(context).fetchNotifications();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Notifications'),
        actions: <Widget>[
          IconButton(
            tooltip: 'Actualiser',
            onPressed: _reload,
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: FutureBuilder<List<AppNotification>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return EmptyState(
              icon: Icons.cloud_off_outlined,
              title: 'Notifications indisponibles',
              message: snapshot.error.toString(),
            );
          }
          final notifications = snapshot.data ?? const <AppNotification>[];
          if (notifications.isEmpty) {
            return const EmptyState(
              icon: Icons.notifications_outlined,
              title: 'Aucune notification',
              message: 'Les alertes Agricheck apparaitront ici.',
            );
          }
          return ListView.builder(
            padding: const EdgeInsets.all(16),
            itemCount: notifications.length,
            itemBuilder: (context, index) {
              final item = notifications[index];
              return Card(
                child: ListTile(
                  leading: Icon(_iconFor(item.type)),
                  title: Text(item.title),
                  subtitle: Text(
                    '${_formatDate(item.createdAt)}\n${item.message}',
                  ),
                  isThreeLine: true,
                ),
              );
            },
          );
        },
      ),
    );
  }

  IconData _iconFor(String type) {
    final normalized = type.toLowerCase();
    if (normalized.contains('weather') || normalized.contains('meteo')) {
      return Icons.cloud_outlined;
    }
    if (normalized.contains('treatment') || normalized.contains('traitement')) {
      return Icons.medication_outlined;
    }
    if (normalized.contains('analysis') || normalized.contains('analyse')) {
      return Icons.analytics_outlined;
    }
    return Icons.notifications_outlined;
  }

  String _formatDate(DateTime date) {
    final day = date.day.toString().padLeft(2, '0');
    final month = date.month.toString().padLeft(2, '0');
    final hour = date.hour.toString().padLeft(2, '0');
    final minute = date.minute.toString().padLeft(2, '0');
    return '$day/$month/${date.year} $hour:$minute';
  }
}
