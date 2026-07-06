import 'package:flutter/material.dart';

class AppTheme {
  static const Color leaf = Color(0xFF1B5E20);
  static const Color brightLeaf = Color(0xFF2E7D32);
  static const Color field = Color(0xFFE8F5E9);
  static const Color lightLeaf = Color(0xFF81C784);
  static const Color soil = Color(0xFF424242);
  static const Color warning = Color(0xFFF59E0B);

  static ThemeData light() {
    final scheme = ColorScheme.fromSeed(
      seedColor: leaf,
      brightness: Brightness.light,
      primary: leaf,
      secondary: brightLeaf,
      surface: Colors.white,
    );
    return ThemeData(
      colorScheme: scheme,
      useMaterial3: true,
      scaffoldBackgroundColor: Colors.transparent,
      appBarTheme: AppBarTheme(
        centerTitle: false,
        elevation: 0,
        backgroundColor: Colors.black.withValues(alpha: 0.18),
        foregroundColor: Colors.white,
        surfaceTintColor: Colors.transparent,
      ),
      cardTheme: CardThemeData(
        elevation: 0,
        color: Colors.white.withValues(alpha: 0.94),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
      ),
      navigationBarTheme: NavigationBarThemeData(
        backgroundColor: Colors.white.withValues(alpha: 0.94),
        indicatorColor: field,
        labelTextStyle: WidgetStateProperty.all(
          const TextStyle(fontWeight: FontWeight.w800),
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: leaf,
          foregroundColor: Colors.white,
          minimumSize: const Size.fromHeight(48),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: Colors.white.withValues(alpha: 0.95),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(8),
          borderSide: const BorderSide(color: Color(0xFFD8E7DB)),
        ),
      ),
    );
  }
}
