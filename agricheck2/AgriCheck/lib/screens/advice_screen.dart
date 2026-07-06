import 'package:flutter/material.dart';

import '../main.dart';
import '../models/advice_item.dart';
import '../widgets/empty_state.dart';
import '../widgets/section_header.dart';

class AdviceScreen extends StatefulWidget {
  const AdviceScreen({super.key});

  @override
  State<AdviceScreen> createState() => _AdviceScreenState();
}

class _AdviceScreenState extends State<AdviceScreen> {
  late Future<List<AdviceItem>> _future;

  @override
  void initState() {
    super.initState();
    _future = AgricheckScope.read(context).buildAgricheckAdvice();
  }

  void _reload() {
    setState(
      () => _future = AgricheckScope.read(context).buildAgricheckAdvice(),
    );
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<List<AdviceItem>>(
      future: _future,
      builder: (context, snapshot) {
        return ListView(
          padding: const EdgeInsets.all(16),
          children: <Widget>[
            DecoratedBox(
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: <Color>[Color(0xFFFFF7D6), Color(0xFFE8F5E9)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: Color(0xFFFDE68A)),
              ),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Row(
                  children: <Widget>[
                    const Icon(
                      Icons.lightbulb_outline,
                      color: Color(0xFFEAB308),
                      size: 34,
                    ),
                    const SizedBox(width: 12),
                    const Expanded(
                      child: Text(
                        'Conseils agricoles adaptes a vos cultures et analyses.',
                      ),
                    ),
                    IconButton(
                      tooltip: 'Actualiser',
                      onPressed: _reload,
                      icon: const Icon(Icons.refresh),
                    ),
                  ],
                ),
              ),
            ),
            const SectionHeader('Conseils Agricoles'),
            if (snapshot.connectionState == ConnectionState.waiting)
              const Center(
                child: Padding(
                  padding: EdgeInsets.all(24),
                  child: CircularProgressIndicator(),
                ),
              )
            else if (snapshot.hasError)
              EmptyState(
                icon: Icons.cloud_off_outlined,
                title: 'Conseils indisponibles',
                message: snapshot.error.toString(),
              )
            else if ((snapshot.data ?? const <AdviceItem>[]).isEmpty)
              const EmptyState(
                icon: Icons.lightbulb_outline,
                title: 'Aucun conseil pour le moment',
                message:
                    'Les conseils venant de la base Agricheck apparaitront ici.',
              )
            else
              ...snapshot.data!.map((item) => _AdviceCard(item: item)),
          ],
        );
      },
    );
  }
}

class _AdviceCard extends StatelessWidget {
  const _AdviceCard({required this.item});

  final AdviceItem item;

  @override
  Widget build(BuildContext context) {
    final visual = _visualFor(item);
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        color: visual.background,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: visual.border),
      ),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: visual.iconColor.withValues(alpha: 0.16),
          child: Icon(visual.icon, color: visual.iconColor),
        ),
        title: Text(
          item.title,
          style: TextStyle(
            color: visual.titleColor,
            fontWeight: FontWeight.w900,
          ),
        ),
        subtitle: Padding(
          padding: const EdgeInsets.only(top: 6),
          child: Text(
            [
              if (item.crop.isNotEmpty) item.crop,
              if (item.category.isNotEmpty) item.category,
              item.message,
            ].join('\n'),
          ),
        ),
      ),
    );
  }

  _AdviceVisual _visualFor(AdviceItem item) {
    final text = '${item.title} ${item.category}'.toLowerCase();
    if (text.contains('meteo')) {
      return const _AdviceVisual(
        icon: Icons.cloud_outlined,
        iconColor: Color(0xFF0284C7),
        titleColor: Color(0xFF075985),
        background: Color(0xFFE0F2FE),
        border: Color(0xFFBAE6FD),
      );
    }
    if (text.contains('maladie') || text.contains('risque')) {
      return const _AdviceVisual(
        icon: Icons.healing_outlined,
        iconColor: Color(0xFFEA580C),
        titleColor: Color(0xFF9A3412),
        background: Color(0xFFFFEDD5),
        border: Color(0xFFFED7AA),
      );
    }
    if (text.contains('plante') || text.contains('culture')) {
      return const _AdviceVisual(
        icon: Icons.eco_outlined,
        iconColor: Color(0xFF16A34A),
        titleColor: Color(0xFF166534),
        background: Color(0xFFDCFCE7),
        border: Color(0xFFBBF7D0),
      );
    }
    return const _AdviceVisual(
      icon: Icons.lightbulb_outline,
      iconColor: Color(0xFFEAB308),
      titleColor: Color(0xFF854D0E),
      background: Color(0xFFFEF9C3),
      border: Color(0xFFFDE68A),
    );
  }
}

class _AdviceVisual {
  const _AdviceVisual({
    required this.icon,
    required this.iconColor,
    required this.titleColor,
    required this.background,
    required this.border,
  });

  final IconData icon;
  final Color iconColor;
  final Color titleColor;
  final Color background;
  final Color border;
}
