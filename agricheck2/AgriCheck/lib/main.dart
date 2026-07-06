import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:flutter_dotenv/flutter_dotenv.dart';


import 'models/advice_item.dart';
import 'models/app_notification.dart';
import 'models/api_config.dart';
import 'models/chat_message.dart';
import 'models/diagnosis_result.dart';
import 'models/user_profile.dart';
import 'models/weather_report.dart';
import 'screens/about_screen.dart';
import 'screens/auth_screen.dart';
import 'screens/shell_screen.dart';
import 'screens/splash_screen.dart';
import 'services/agricheck_api_service.dart';
import 'services/api_exceptions.dart';
import 'services/agricheck_advice_engine.dart';
import 'services/ai_diagnosis_service.dart';
import 'services/assistant_service.dart';
import 'services/config_store.dart';
import 'services/history_store.dart';
import 'services/location_service.dart';
import 'services/openweather_client.dart';
import 'services/session_store.dart';
import 'theme/app_theme.dart';
import 'widgets/agricheck_background.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await dotenv.load();
  final appState = AgricheckAppState();
  await appState.load();
  debugPrint(dotenv.env['GEMINI_API_KEY']);

  runApp(
      AgricheckScope(
          state: appState,
          child: const AgricheckApp())
    );

}

class AgricheckApp extends StatelessWidget {
  const AgricheckApp({super.key});


  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'Agricheck',
      theme: AppTheme.light(),
      routes: <String, WidgetBuilder>{
        '/auth': (_) => const AuthScreen(),
        '/app': (_) => const ShellScreen(),
        '/about': (_) => const AboutScreen(),
      },
      builder: (context, child) =>
          AgricheckBackground(child: child ?? const SizedBox.shrink()),
      home: const SplashScreen(),
    );
  }
}

class AgricheckScope extends InheritedNotifier<AgricheckAppState> {
  const AgricheckScope({
    required AgricheckAppState state,
    required super.child,
    super.key,
  }) : super(notifier: state);

  static AgricheckAppState of(BuildContext context) {
    final scope = context.dependOnInheritedWidgetOfExactType<AgricheckScope>();
    assert(scope != null, 'AgricheckScope manquant dans l arbre Flutter.');
    return scope!.notifier!;
  }

  static AgricheckAppState read(BuildContext context) {
    final element = context
        .getElementForInheritedWidgetOfExactType<AgricheckScope>();
    final scope = element?.widget as AgricheckScope?;
    assert(scope != null, 'AgricheckScope manquant dans l arbre Flutter.');
    return scope!.notifier!;
  }
}

class AgricheckAppState extends ChangeNotifier {
  final ConfigStore _configStore = ConfigStore();
  final SessionStore _sessionStore = SessionStore();
  final HistoryStore _historyStore = HistoryStore();
  final AgricheckApiService _apiService = AgricheckApiService();
  final AgricheckAdviceEngine _adviceEngine = AgricheckAdviceEngine();
  final AiDiagnosisService _diagnosisService = AiDiagnosisService();
  final OpenWeatherClient _weatherClient = OpenWeatherClient();
  final AssistantService _assistantService = AssistantService();
  final LocationService _locationService = LocationService();

  ApiConfig _config = const ApiConfig();
  String _authToken = '';
  UserProfile? _user;
  List<DiagnosisResult> _history = const <DiagnosisResult>[];
  WeatherReport? _lastWeather;

  ApiConfig get config => _config;

  UserProfile? get user => _user;

  bool get isAuthenticated => _authToken.trim().isNotEmpty && _user != null;

  List<DiagnosisResult> get history => _history;

  WeatherReport? get lastWeather => _lastWeather;

  DiagnosisResult? get latestAnalysis =>
      _history.isEmpty ? null : _history.first;

  int get analysesCount => _history.length;

  int get diseasesCount => _history.where((item) => !item.isHealthy).length;

  Future<void> load() async {
    _config = await _configStore.load();
    final session = await _sessionStore.load();
    _authToken = session.token;
    _user = session.user;
    if (_authToken.startsWith('local-')) {
      _authToken = '';
      _user = null;
      await _sessionStore.clear();
    }
    _history = await _historyStore.load();
  }

  Future<void> saveConfig(ApiConfig config) async {
    _config = config;
    await _configStore.save(config);
    notifyListeners();
  }

  Future<void> resetLocalBackend() {
    const defaults = ApiConfig();
    return saveConfig(
      _config.copyWith(
        backendBaseUrl: defaults.backendBaseUrl,
        useBackendProxy: true,
      ),
    );
  }

  Future<void> login({
    required String identifier,
    required String password,
  }) async {
    final result = await _apiService.login(
      config: _config,
      identifier: identifier,
      password: password,
    );
    await _saveSession(result);
  }

  Future<void> register({
    required String fullName,
    required String phone,
    required String email,
    required String password,
  }) async {
    final result = await _apiService.register(
      config: _config,
      fullName: fullName,
      phone: phone,
      email: email,
      password: password,
    );
    await _saveSession(result);
  }

  Future<void> requestPasswordReset(String identifier) async {
    try {
      await _apiService.requestPasswordReset(
        config: _config,
        identifier: identifier,
      );
    } catch (_) {
      return;
    }
  }

  Future<void> logout() async {
    _authToken = '';
    _user = null;
    await _sessionStore.clear();
    notifyListeners();
  }

  Future<void> _saveSession(AuthResult result) async {
    _authToken = result.token;
    _user = result.user;
    await _sessionStore.save(token: result.token, user: result.user);
    notifyListeners();
  }

  Future<DiagnosisResult> diagnose(XFile image) async {
    final configWithLocation = await _locationService
        .withCurrentLocation(_config)
        .timeout(const Duration(seconds: 6), onTimeout: () => _config);
    final result = await _diagnosisService
        .diagnose(
          image: image,
          config: configWithLocation,
          authToken: _authToken,
        )
        .timeout(
          const Duration(seconds: 95),
          onTimeout: () => throw const RemoteApiException(
            'Analyse arretee: verifiez la connexion et les cles IA configurees dans Agricheck Admin.',
          ),
        );
    _history = <DiagnosisResult>[result, ..._history].take(80).toList();
    await _historyStore.save(_history);
    notifyListeners();
    return result;
  }
  Future<void> addHistory(DiagnosisResult result) async {
    _history = <DiagnosisResult>[result, ..._history].take(80).toList();
    await _historyStore.save(_history);
    notifyListeners();
  }

  Future<WeatherReport> fetchWeather() {
    return _locationService
        .withCurrentLocation(_config)
        .timeout(const Duration(seconds: 6), onTimeout: () => _config)
        .then((config) => _weatherClient.fetch(config, authToken: _authToken))
        .then((report) {
          _lastWeather = report;
          notifyListeners();
          return report;
        })
        .timeout(
          const Duration(seconds: 18),
          onTimeout: () => throw const RemoteApiException(
            'Meteo indisponible: verifiez la connexion Internet du telephone.',
          ),
        );
  }

  Future<List<AdviceItem>> buildAgricheckAdvice() async {
    WeatherReport? weather = _lastWeather;
    try {
      weather = await fetchWeather();
    } catch (_) {
      weather = _lastWeather;
    }
    return _adviceEngine.build(history: _history, weather: weather);
  }

  Future<String> askAssistant(String message, List<ChatMessage> history) async {
    WeatherReport? weather = _lastWeather;
    if (weather == null) {
      try {
        weather = await fetchWeather().timeout(const Duration(seconds: 8));
      } catch (_) {
        weather = null;
      }
    }
    return _assistantService.ask(
      message: message,
      history: history,
      config: _config,
      authToken: _authToken,
      latestAnalysis: latestAnalysis,
      weather: weather,
      analysisCount: _history.length,
    );
  }

  Future<List<AdviceItem>> fetchAdvice() {
    final crop = _history.isEmpty ? '' : _history.first.plantName;
    return _apiService.fetchAdvice(
      config: _config,
      token: _authToken,
      crop: crop,
    );
  }

  Future<List<AppNotification>> fetchNotifications() {
    return _apiService.fetchNotifications(config: _config, token: _authToken);
  }
}
