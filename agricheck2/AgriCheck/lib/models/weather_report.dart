class WeatherDay {
  const WeatherDay({
    required this.date,
    required this.minTemperature,
    required this.maxTemperature,
    required this.humidity,
    required this.windSpeed,
    required this.precipitation,
    required this.description,
  });

  final DateTime date;
  final double minTemperature;
  final double maxTemperature;
  final int humidity;
  final double windSpeed;
  final double precipitation;
  final String description;
}

class WeatherPeriod {
  const WeatherPeriod({
    required this.label,
    required this.time,
    required this.temperature,
    required this.humidity,
    required this.windSpeed,
    required this.precipitation,
    required this.description,
  });

  final String label;
  final DateTime time;
  final double temperature;
  final int humidity;
  final double windSpeed;
  final double precipitation;
  final String description;
}

class WeatherReport {
  const WeatherReport({
    required this.cityLabel,
    required this.temperature,
    required this.humidity,
    required this.windSpeed,
    required this.precipitation,
    required this.description,
    required this.periods,
    required this.days,
  });

  final String cityLabel;
  final double temperature;
  final int humidity;
  final double windSpeed;
  final double precipitation;
  final String description;
  final List<WeatherPeriod> periods;
  final List<WeatherDay> days;
}
