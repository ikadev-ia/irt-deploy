import 'package:flutter/material.dart';

import '../widgets/agricheck_background.dart';
import 'advice_screen.dart';
import 'analysis_screen.dart';
import 'history_screen.dart';
import 'home_screen.dart';
import 'notifications_screen.dart';
import 'profile_screen.dart';
import 'weather_screen.dart';

class ShellScreen extends StatefulWidget {
  const ShellScreen({super.key});

  @override
  State<ShellScreen> createState() => _ShellScreenState();
}

class _ShellScreenState extends State<ShellScreen> {
  int _index = 0;

  @override
  Widget build(BuildContext context) {
    final pages = <Widget>[
      HomeScreen(
        onAnalyze: () => setState(() => _index = 1),
        onAdvice: () => setState(() => _index = 4),
      ),
      const AnalysisScreen(),
      const HistoryScreen(),
      const WeatherScreen(),
      const AdviceScreen(),
      const ProfileScreen(),
    ];
    final titles = <String>[
      'Accueil',
      'Analyse',
      'Historique',
      'Meteo agricole',
      'Conseils',
      'Profil',
    ];

    return Scaffold(
      backgroundColor: Colors.transparent,
      appBar: AppBar(
        title: Text(titles[_index]),
        actions: <Widget>[
          IconButton(
            tooltip: 'A propos d Agricheck',
            onPressed: () => Navigator.of(context).pushNamed('/about'),
            icon: const Icon(Icons.info_outline),
          ),
          IconButton(
            tooltip: 'Notifications',
            onPressed: () => Navigator.of(context).push(
              MaterialPageRoute<void>(
                builder: (_) => const NotificationsScreen(),
              ),
            ),
            icon: const Icon(Icons.notifications_outlined),
          ),
        ],
      ),

      body : pages[_index],
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: (index) => setState(() => _index = index),
        destinations: const <NavigationDestination>[
          NavigationDestination(
            icon: Icon(Icons.home_outlined),
            selectedIcon: Icon(Icons.home),
            label: 'Accueil',
          ),
          NavigationDestination(
            icon: Icon(Icons.camera_enhance_outlined),
            selectedIcon: Icon(Icons.camera_enhance),
            label: 'Analyse',
          ),
          NavigationDestination(
            icon: Icon(Icons.history_outlined),
            selectedIcon: Icon(Icons.history),
            label: 'Historique',
          ),
          NavigationDestination(
            icon: Icon(Icons.cloud_outlined),
            selectedIcon: Icon(Icons.cloud),
            label: 'Meteo',
          ),
          NavigationDestination(
            icon: Icon(Icons.lightbulb_outline),
            selectedIcon: Icon(Icons.lightbulb),
            label: 'Conseils',
          ),
          NavigationDestination(
            icon: Icon(Icons.person_outline),
            selectedIcon: Icon(Icons.person),
            label: 'Profil',
          ),
        ],
      ),
    );
  }
}
