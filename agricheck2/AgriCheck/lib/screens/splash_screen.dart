import 'dart:async';

import 'package:flutter/material.dart';

import '../main.dart';
import '../theme/app_theme.dart';
import '../widgets/agricheck_background.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  Timer? _navigationTimer;

  @override
  void initState() {
    super.initState();
    _navigationTimer = Timer(const Duration(milliseconds: 1500), () {
      if (!mounted) {
        return;
      }
      final route = AgricheckScope.read(context).isAuthenticated
          ? '/app'
          : '/auth';
      Navigator.of(context).pushReplacementNamed(route);
    });
  }

  @override
  void dispose() {
    _navigationTimer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.transparent,
      body: AgricheckBackground(
        imageAsset: AgricheckBackground.wheatImage,
        overlayOpacity: 0.44,
        alignment: Alignment.centerLeft,
        child: SafeArea(
          child: Center(
            child: Padding(
              padding: const EdgeInsets.all(28),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: <Widget>[
                  Image.asset(
                    'assets/images/agricheck_logo.png',
                    width: MediaQuery.of(context).size.width * 0.78,
                  ),
                  const SizedBox(height: 24),
                  Text(
                    'AGRICHECK',
                    style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                      color: Colors.white,
                      fontWeight: FontWeight.w900,
                      letterSpacing: 0,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'Votre recolte, notre priorite.',
                    textAlign: TextAlign.center,
                    style: Theme.of(context).textTheme.titleLarge?.copyWith(
                      color: AppTheme.lightLeaf,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
