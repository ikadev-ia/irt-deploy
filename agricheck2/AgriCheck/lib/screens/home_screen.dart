import 'package:flutter/material.dart';

import '../main.dart';
import '../theme/app_theme.dart';
import '../widgets/agricheck_background.dart';
import '../widgets/empty_state.dart';
import '../widgets/info_card.dart';
import '../widgets/section_header.dart';
import 'result_screen.dart';

class HomeScreen extends StatelessWidget {
  const HomeScreen({
    required this.onAnalyze,
    required this.onAdvice,
    super.key,
  });

  final VoidCallback onAnalyze;
  final VoidCallback onAdvice;

  @override
  Widget build(BuildContext context) {
    final state = AgricheckScope.of(context);
    final userName = state.user?.fullName.trim() ?? '';
    final latest = state.history.take(3).toList();

    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 20),
      children: <Widget>[
        _HomeHero(userName: userName),
        const SizedBox(height: 14),
        Row(
          children: <Widget>[
            Expanded(
              child: FilledButton.icon(
                onPressed: onAnalyze,
                icon: const Icon(Icons.camera_alt_outlined),
                label: const Text('Scanner'),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: OutlinedButton.icon(
                onPressed: onAnalyze,
                icon: const Icon(Icons.photo_library_outlined),
                label: const Text('Importer'),
                style: OutlinedButton.styleFrom(
                  backgroundColor: Colors.white.withValues(alpha: 0.92),
                ),
              ),
            ),
          ],
        ),
        const SectionHeader('Resume'),
        InfoCard(
          title: 'Analyses realisees',
          value: state.analysesCount.toString(),
          icon: Icons.analytics_outlined,
        ),
        InfoCard(
          title: 'Maladies detectees',
          value: state.diseasesCount.toString(),
          icon: Icons.warning_amber_rounded,
          tint: AppTheme.warning,
        ),
        Card(
          child: ListTile(
            leading: const Icon(Icons.lightbulb_outline, color: AppTheme.leaf),
            title: const Text('Conseils agricoles'),
            subtitle: const Text(
              'Conseils selon meteo, plante, maladie, risque et historique.',
            ),
            trailing: const Icon(Icons.chevron_right),
            onTap: onAdvice,
          ),
        ),
        SectionHeader(
          'Dernieres analyses',
          action: TextButton(
            onPressed: onAnalyze,
            style: TextButton.styleFrom(foregroundColor: Colors.white),
            child: const Text('Analyser'),
          ),
        ),
        if (latest.isEmpty)
          const EmptyState(
            icon: Icons.eco_outlined,
            title: 'Aucune analyse',
            message:
                'L historique se remplira apres la premiere photo analysee.',
          )
        else
          ...latest.map(
            (item) => Card(
              child: ListTile(
                title: Text(item.plantName),
                subtitle: Text(
                  '${item.diseaseName} - Risque ${item.riskLevel}',
                ),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => Navigator.of(context).push(
                  MaterialPageRoute<void>(
                    builder: (_) => ResultScreen(result: item),
                  ),
                ),
              ),
            ),
          ),
        const SizedBox(height: 12),
        TextButton.icon(
          onPressed: () => Navigator.of(context).pushNamed('/about'),
          icon: const Icon(Icons.info_outline),
          label: const Text('A propos d Agricheck'),
          style: TextButton.styleFrom(foregroundColor: Colors.white),
        ),
        const SizedBox(height: 8),
        OutlinedButton.icon(
          onPressed: () => _logout(context, state),
          icon: const Icon(Icons.logout),
          label: const Text('Se deconnecter'),
          style: OutlinedButton.styleFrom(
            backgroundColor: Colors.white.withValues(alpha: 0.92),
          ),
        ),
      ],
    );
  }

  Future<void> _logout(BuildContext context, AgricheckAppState state) async {
    await state.logout();
    if (context.mounted) {
      Navigator.of(context).pushNamedAndRemoveUntil('/auth', (route) => false);
    }
  }
}

class _HomeHero extends StatelessWidget {
  const _HomeHero({required this.userName});

  final String userName;

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(8),
        boxShadow: <BoxShadow>[
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.18),
            blurRadius: 28,
            offset: const Offset(0, 14),
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(8),
        child: Stack(
          children: <Widget>[
            Positioned.fill(
              child: Image.asset(
                AgricheckBackground.wheatImage,
                fit: BoxFit.cover,
                alignment: Alignment.centerLeft,
              ),
            ),
            Positioned.fill(
              child: DecoratedBox(
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                    colors: <Color>[
                      Colors.black.withValues(alpha: 0.68),
                      Colors.black.withValues(alpha: 0.28),
                    ],
                  ),
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(18),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: <Widget>[
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.92),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Image.asset(
                      'assets/images/agricheck_logo.png',
                      width: 118,
                    ),
                  ),
                  const SizedBox(height: 22),
                  Text(
                    userName.isEmpty ? 'Bienvenue sur Agricheck' : 'Bonjour',
                    style: Theme.of(context).textTheme.labelLarge?.copyWith(
                      color: AppTheme.lightLeaf,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    userName.isEmpty
                        ? 'Votre recolte, notre priorite.'
                        : userName,
                    style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                      color: Colors.white,
                      fontWeight: FontWeight.w900,
                      height: 1.05,
                    ),
                  ),
                  const SizedBox(height: 10),
                  Text(
                    userName.isEmpty
                        ? 'Scannez une plante ou importez une image pour obtenir un diagnostic agricole.'
                        : 'Scannez, suivez vos resultats et recevez des conseils agricoles utiles.',
                    style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                      color: Colors.white.withValues(alpha: 0.9),
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
