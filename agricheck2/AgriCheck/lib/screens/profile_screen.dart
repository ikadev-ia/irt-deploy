import 'package:flutter/material.dart';

import '../main.dart';
import '../widgets/info_card.dart';
import '../widgets/section_header.dart';

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final state = AgricheckScope.of(context);
    final user = state.user;
    final plantsCount = state.history
        .map((item) => item.plantName.toLowerCase())
        .toSet()
        .length;
    final latest = state.history.isEmpty ? null : state.history.first;

    return ListView(
      padding: const EdgeInsets.all(16),
      children: <Widget>[
        Center(
          child: Column(
            children: <Widget>[
              Image.asset('assets/images/agricheck_logo.png', width: 230),
              const SizedBox(height: 8),
              const Text(
                'Votre récolte, notre priorité.',
                textAlign: TextAlign.center,
                style: TextStyle(fontWeight: FontWeight.w900),
              ),
            ],
          ),
        ),
        const SizedBox(height: 14),
        Card(
          child: ListTile(
            leading: const CircleAvatar(child: Icon(Icons.person_outline)),
            title: Text(
              user?.fullName.trim().isNotEmpty == true
                  ? user!.fullName
                  : 'Compte Agricheck',
            ),
            subtitle: Text(
              user == null
                  ? 'Utilisateur non connecte'
                  : [
                      user.phone,
                      user.email,
                    ].where((item) => item.trim().isNotEmpty).join(' - '),
            ),
          ),
        ),
        const SectionHeader('Statistiques'),
        InfoCard(
          title: 'Analyses realisees',
          value: state.analysesCount.toString(),
          icon: Icons.analytics_outlined,
        ),
        InfoCard(
          title: 'Plantes analysees',
          value: plantsCount.toString(),
          icon: Icons.eco_outlined,
        ),
        InfoCard(
          title: 'Maladies detectees',
          value: state.diseasesCount.toString(),
          icon: Icons.warning_amber_rounded,
        ),
        Card(
          child: ListTile(
            leading: const Icon(Icons.schedule_outlined),
            title: const Text('Derniere analyse'),
            subtitle: Text(
              latest == null
                  ? 'Aucune analyse pour le moment'
                  : '${latest.plantName} - ${_formatDate(latest.createdAt)}',
            ),
          ),
        ),
        const SectionHeader('Informations'),
        Card(
          child: Column(
            children: <Widget>[
              ListTile(
                leading: const Icon(Icons.info_outline),
                title: const Text('À propos d’Agricheck'),
                subtitle: const Text('Informations officielles Agricheck'),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => Navigator.of(context).pushNamed('/about'),
              ),
              ListTile(
                leading: const Icon(Icons.phone_outlined),
                title: const Text('Telephone'),
                subtitle: Text(
                  user?.phone.trim().isEmpty == false
                      ? user!.phone
                      : 'Non renseigne',
                ),
              ),
              ListTile(
                leading: const Icon(Icons.alternate_email),
                title: const Text('Email'),
                subtitle: Text(
                  user?.email.trim().isEmpty == false
                      ? user!.email
                      : 'Non renseigne',
                ),
              ),
              ListTile(
                leading: const Icon(Icons.calendar_today_outlined),
                title: const Text('Date d inscription'),
                subtitle: Text(
                  user == null ? 'Non disponible' : _formatDate(user.createdAt),
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 12),
        OutlinedButton.icon(
          onPressed: () async {
            await state.logout();
            if (context.mounted) {
              Navigator.of(
                context,
              ).pushNamedAndRemoveUntil('/auth', (route) => false);
            }
          },
          icon: const Icon(Icons.logout),
          label: const Text('Se deconnecter'),
        ),
      ],
    );
  }

  String _formatDate(DateTime date) {
    final day = date.day.toString().padLeft(2, '0');
    final month = date.month.toString().padLeft(2, '0');
    return '$day/$month/${date.year}';
  }
}
