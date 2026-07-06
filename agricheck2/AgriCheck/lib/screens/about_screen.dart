import 'package:flutter/material.dart';

import '../theme/app_theme.dart';

class AboutScreen extends StatelessWidget {
  const AboutScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('À propos d’Agricheck')),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
        children: const <Widget>[
          _HeroBanner(),
          _AboutSection(
            icon: Icons.groups_outlined,
            title: 'Qui sommes-nous ?',
            body:
                'Agricheck est une entreprise malienne spécialisée dans la détection intelligente des maladies des plantes grâce à l’intelligence artificielle et aux technologies de surveillance agricole.\n\nNotre solution accompagne les agriculteurs, les coopératives agricoles et les grandes exploitations dans l’identification rapide des maladies des cultures afin d’améliorer les rendements agricoles, réduire les pertes de récoltes et faciliter la prise de décision.\n\nAgricheck contribue à la modernisation de l’agriculture malienne en proposant des outils numériques accessibles, innovants et adaptés aux réalités du terrain.',
          ),
          _AboutSection(
            icon: Icons.flag_outlined,
            title: 'Notre mission',
            body:
                'Notre mission est d’aider les agriculteurs à protéger leurs cultures grâce à :',
            bullets: <String>[
              'La détection précoce des maladies des plantes.',
              'L’analyse intelligente des images agricoles.',
              'La surveillance des exploitations par drone.',
              'La recommandation de traitements adaptés.',
              'Le suivi continu de l’état des cultures.',
              'Nous voulons rendre les technologies agricoles modernes accessibles à tous les producteurs.',
            ],
          ),
          _AboutSection(
            icon: Icons.visibility_outlined,
            title: 'Notre vision',
            body:
                'Devenir une référence de l’Agritech en Afrique de l’Ouest en développant des solutions innovantes basées sur l’intelligence artificielle, les drones et l’analyse des données agricoles.\n\nNotre ambition est de contribuer à la sécurité alimentaire, à l’amélioration des rendements agricoles et au développement durable du secteur agricole malien.',
          ),
          _SolutionsSection(),
          _AboutSection(
            icon: Icons.memory_outlined,
            title: 'Nos technologies',
            body: 'Agricheck utilise :',
            bullets: <String>[
              'Intelligence Artificielle (IA)',
              'Vision par ordinateur',
              'Drones agricoles',
              'Applications mobiles',
              'Applications web',
              'Bases de données agricoles',
              'PlantVillage',
              'PlantDoc',
              'Plant Pathology Dataset',
              'Pl@ntNet',
              'Données collectées localement au Mali',
            ],
          ),
          _AboutSection(
            icon: Icons.location_on_outlined,
            title: 'Notre implantation',
            body:
                'Localisation :\n\nSébénikoro\nBamako\nMali\n\nCette implantation permet à Agricheck d’assurer un accès rapide aux différentes zones agricoles, de faciliter les interventions sur le terrain et de développer des partenariats avec les acteurs du secteur agricole.',
          ),
          _AboutSection(
            icon: Icons.favorite_border,
            title: 'Nos valeurs',
            bullets: <String>[
              'Innovation',
              'Fiabilité',
              'Professionnalisme',
              'Accessibilité',
              'Satisfaction client',
              'Développement durable',
            ],
          ),
          _ContactSection(),
        ],
      ),
    );
  }
}

class _HeroBanner extends StatelessWidget {
  const _HeroBanner();

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: const Color(0xFFD8E7DB)),
      ),
      child: Column(
        children: <Widget>[
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: const Color(0xFFF5F7FA),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Image.asset('assets/images/agricheck_logo.png', width: 280),
          ),
          const SizedBox(height: 12),
          Text(
            'À propos d’Agricheck',
            textAlign: TextAlign.center,
            style: Theme.of(context).textTheme.headlineSmall?.copyWith(
              color: AppTheme.leaf,
              fontWeight: FontWeight.w900,
            ),
          ),
          const SizedBox(height: 6),
          const Text(
            '“Votre récolte, notre priorité.”',
            textAlign: TextAlign.center,
            style: TextStyle(
              color: AppTheme.soil,
              fontSize: 17,
              fontWeight: FontWeight.w800,
            ),
          ),
        ],
      ),
    );
  }
}

class _AboutSection extends StatelessWidget {
  const _AboutSection({
    required this.icon,
    required this.title,
    this.body = '',
    this.bullets = const <String>[],
  });

  final IconData icon;
  final String title;
  final String body;
  final List<String> bullets;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            Row(
              children: <Widget>[
                CircleAvatar(
                  radius: 18,
                  backgroundColor: AppTheme.field,
                  child: Icon(icon, color: AppTheme.leaf, size: 20),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    title,
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w900,
                      color: AppTheme.leaf,
                    ),
                  ),
                ),
              ],
            ),
            if (body.isNotEmpty) ...<Widget>[
              const SizedBox(height: 10),
              Text(body),
            ],
            if (bullets.isNotEmpty) ...<Widget>[
              const SizedBox(height: 10),
              ...bullets.map(
                (item) => Padding(
                  padding: const EdgeInsets.only(bottom: 6),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: <Widget>[
                      const Icon(
                        Icons.check_circle_outline,
                        size: 18,
                        color: AppTheme.brightLeaf,
                      ),
                      const SizedBox(width: 8),
                      Expanded(child: Text(item)),
                    ],
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _SolutionsSection extends StatelessWidget {
  const _SolutionsSection();

  @override
  Widget build(BuildContext context) {
    return const _AboutSection(
      icon: Icons.apps_outlined,
      title: 'Nos solutions',
      body:
          'Agricheck Mobile\nAnalyse des plantes par photo.\nDétection automatique des maladies.\nHistorique des analyses.\nTraitements recommandés.\nConseils agricoles.\n\nAgricheck Client\nSuivi des grandes exploitations agricoles.\nConsultation des analyses réalisées par Agricheck.\nAccès aux rapports.\nTraitements recommandés.\nHistorique du suivi.\n\nAgricheck Admin\nGestion complète de l’entreprise.\nGestion des clients.\nGestion des drones.\nGestion des analyses.\nGestion des rapports.\nGestion des abonnements et paiements.',
    );
  }
}

class _ContactSection extends StatelessWidget {
  const _ContactSection();

  @override
  Widget build(BuildContext context) {
    return const _AboutSection(
      icon: Icons.contact_phone_outlined,
      title: 'Contacts',
      body:
          'Email :\nagricheck05@gmail.com\n\nTéléphone :\n70000110\n60000110\n\nLocalisation :\nSébénikoro, Bamako, Mali',
    );
  }
}
