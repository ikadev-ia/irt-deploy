import 'package:flutter/material.dart';

import '../main.dart';
import '../models/weather_report.dart';
import '../theme/app_theme.dart';
import '../widgets/empty_state.dart';
import '../widgets/section_header.dart';

class WeatherScreen extends StatefulWidget {
  const WeatherScreen({super.key});

  @override
  State<WeatherScreen> createState() => _WeatherScreenState();
}

class _WeatherScreenState extends State<WeatherScreen> {
  WeatherReport? _report;
  bool _isLoading = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _fetch());
  }

  Future<void> _fetch() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });
    try {
      final report = await AgricheckScope.of(context).fetchWeather();
      if (!mounted) {
        return;
      }
      setState(() => _report = report);
    } catch (error) {
      if (!mounted) {
        return;
      }
      setState(() => _error = error.toString());
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: <Widget>[
        FilledButton.icon(
          onPressed: _isLoading ? null : _fetch,
          icon: _isLoading
              ? const SizedBox(
                  width: 18,
                  height: 18,
                  child: CircularProgressIndicator(strokeWidth: 2),
                )
              : const Icon(Icons.cloud_sync_outlined),
          label: Text(_isLoading ? 'Chargement...' : 'Actualiser la meteo'),
        ),
        if (_error != null) ...<Widget>[
          const SizedBox(height: 12),
          Card(
            child: ListTile(
              leading: const Icon(Icons.error_outline, color: AppTheme.warning),
              title: const Text('Meteo indisponible'),
              subtitle: Text(_error!),
            ),
          ),
        ],
        if (_report == null && _error == null)
          const EmptyState(
            icon: Icons.cloud_outlined,
            title: 'Meteo agricole',
            message: 'Actualisez les previsions agricoles sur 7 jours.',
          )
        else if (_report != null) ...<Widget>[
          const SectionHeader('Conditions actuelles'),
          _CurrentWeatherCard(report: _report!),
          if (_report!.periods.isNotEmpty) ...<Widget>[
            const SectionHeader('Aujourd hui'),
            Wrap(
              spacing: 10,
              runSpacing: 10,
              children: _report!.periods
                  .map((period) => _PeriodCard(period: period))
                  .toList(),
            ),
          ],
          const SectionHeader('Previsions 7 jours'),
          ..._report!.days.map((day) {
            final visual = _visualFor(
              day.description,
              maxTemperature: day.maxTemperature,
              precipitation: day.precipitation,
            );
            return Container(
              margin: const EdgeInsets.only(bottom: 8),
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
                  _formatDay(day.date),
                  style: const TextStyle(fontWeight: FontWeight.w800),
                ),
                subtitle: Text(
                  [
                    day.description,
                    'Humidite ${day.humidity} % - Pluie ${day.precipitation.toStringAsFixed(1)} mm',
                  ].join('\n'),
                ),
                trailing: Text(
                  '${day.minTemperature.round()} / ${day.maxTemperature.round()} C',
                  style: const TextStyle(fontWeight: FontWeight.w900),
                ),
              ),
            );
          }),
        ],
      ],
    );
  }

  String _formatDay(DateTime date) {
    const names = <String>['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
    return '${names[date.weekday - 1]} ${date.day.toString().padLeft(2, '0')}/${date.month.toString().padLeft(2, '0')}';
  }

  _WeatherVisual _visualFor(
    String description, {
    double maxTemperature = 0,
    double precipitation = 0,
  }) {
    final lower = description.toLowerCase();
    if (lower.contains('orage')) {
      return const _WeatherVisual(
        icon: Icons.thunderstorm_outlined,
        iconColor: Color(0xFF6D28D9),
        background: Color(0xFFF3E8FF),
        border: Color(0xFFD8B4FE),
      );
    }
    if (lower.contains('pluie') || precipitation > 0.5) {
      return const _WeatherVisual(
        icon: Icons.water_drop_outlined,
        iconColor: Color(0xFF0284C7),
        background: Color(0xFFE0F2FE),
        border: Color(0xFFBAE6FD),
      );
    }
    if (maxTemperature >= 38) {
      return const _WeatherVisual(
        icon: Icons.local_fire_department_outlined,
        iconColor: Color(0xFFF97316),
        background: Color(0xFFFFEDD5),
        border: Color(0xFFFED7AA),
      );
    }
    if (lower.contains('nuage')) {
      return const _WeatherVisual(
        icon: Icons.cloud_outlined,
        iconColor: Color(0xFF64748B),
        background: Color(0xFFF1F5F9),
        border: Color(0xFFCBD5E1),
      );
    }
    return const _WeatherVisual(
      icon: Icons.wb_sunny_outlined,
      iconColor: Color(0xFFEAB308),
      background: Color(0xFFFEF9C3),
      border: Color(0xFFFDE68A),
    );
  }
}

class _WeatherVisual {
  const _WeatherVisual({
    required this.icon,
    required this.iconColor,
    required this.background,
    required this.border,
  });

  final IconData icon;
  final Color iconColor;
  final Color background;
  final Color border;
}

class _CurrentWeatherCard extends StatelessWidget {
  const _CurrentWeatherCard({required this.report});

  final WeatherReport report;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: <Color>[Color(0xFF0A6B38), Color(0xFF8CC63F)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Text(report.cityLabel, style: const TextStyle(color: Colors.white70)),
          const SizedBox(height: 8),
          Text(
            '${report.temperature.toStringAsFixed(1)} C',
            style: Theme.of(context).textTheme.displaySmall?.copyWith(
              color: Colors.white,
              fontWeight: FontWeight.w900,
            ),
          ),
          Text(
            report.description,
            style: const TextStyle(color: Colors.white, fontSize: 16),
          ),
          const SizedBox(height: 14),
          Row(
            children: <Widget>[
              _Metric(
                icon: Icons.water_drop_outlined,
                text: '${report.humidity} %',
              ),
              _Metric(
                icon: Icons.air,
                text: '${report.windSpeed.toStringAsFixed(1)} m/s',
              ),
              _Metric(
                icon: Icons.grain,
                text: '${report.precipitation.toStringAsFixed(1)} mm',
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _PeriodCard extends StatelessWidget {
  const _PeriodCard({required this.period});

  final WeatherPeriod period;

  @override
  Widget build(BuildContext context) {
    final color = _colorFor(period.label);
    return SizedBox(
      width: (MediaQuery.of(context).size.width - 42) / 2,
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: color,
          borderRadius: BorderRadius.circular(8),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            Row(
              children: <Widget>[
                Icon(_iconFor(period.label), size: 20),
                const SizedBox(width: 6),
                Text(
                  period.label,
                  style: const TextStyle(fontWeight: FontWeight.w900),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Text(
              '${period.temperature.toStringAsFixed(1)} C',
              style: Theme.of(
                context,
              ).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w900),
            ),
            Text(period.description),
            const SizedBox(height: 6),
            Text('Humidite ${period.humidity} %'),
            Text('Pluie ${period.precipitation.toStringAsFixed(1)} mm'),
          ],
        ),
      ),
    );
  }

  Color _colorFor(String label) {
    switch (label) {
      case 'Matin':
        return const Color(0xFFFFF4CC);
      case 'Midi':
        return const Color(0xFFFFE0B2);
      case 'Soir':
        return const Color(0xFFDFF0E3);
      default:
        return const Color(0xFFDDEBFF);
    }
  }

  IconData _iconFor(String label) {
    switch (label) {
      case 'Matin':
        return Icons.wb_twilight_outlined;
      case 'Midi':
        return Icons.wb_sunny_outlined;
      case 'Soir':
        return Icons.nights_stay_outlined;
      default:
        return Icons.dark_mode_outlined;
    }
  }
}

class _Metric extends StatelessWidget {
  const _Metric({required this.icon, required this.text});

  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Row(
        children: <Widget>[
          Icon(icon, color: Colors.white, size: 18),
          const SizedBox(width: 4),
          Flexible(
            child: Text(
              text,
              style: const TextStyle(color: Colors.white),
              overflow: TextOverflow.ellipsis,
            ),
          ),
        ],
      ),
    );
  }
}
