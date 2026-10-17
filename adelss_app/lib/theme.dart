import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class AdelssColors {
  static const ink = Color(0xFF0B1F3A);
  static const inkSoft = Color(0xFF16345C);
  static const gold = Color(0xFFC4A35A);
  static const cream = Color(0xFFF6F3EE);
  static const paper = Color(0xFFFFFFFF);
  static const muted = Color(0xFF667085);
  static const line = Color(0xFFE7E1D8);
  static const success = Color(0xFF2F6F4E);
  static const danger = Color(0xFFB42318);
  static const pending = Color(0xFFB45309);
}

class AdelssTheme {
  static ThemeData light() {
    final base = GoogleFonts.plusJakartaSansTextTheme();
    final textTheme = base.copyWith(
      displaySmall: base.displaySmall?.copyWith(
        fontWeight: FontWeight.w700,
        color: AdelssColors.ink,
        letterSpacing: -0.6,
      ),
      headlineMedium: base.headlineMedium?.copyWith(
        fontWeight: FontWeight.w700,
        color: AdelssColors.ink,
        letterSpacing: -0.4,
      ),
      headlineSmall: base.headlineSmall?.copyWith(
        fontWeight: FontWeight.w700,
        color: AdelssColors.ink,
      ),
      titleLarge: base.titleLarge?.copyWith(
        fontWeight: FontWeight.w700,
        color: AdelssColors.ink,
      ),
      titleMedium: base.titleMedium?.copyWith(
        fontWeight: FontWeight.w600,
        color: AdelssColors.ink,
      ),
      bodyLarge: base.bodyLarge?.copyWith(color: AdelssColors.ink, height: 1.4),
      bodyMedium: base.bodyMedium?.copyWith(color: AdelssColors.muted, height: 1.4),
      labelLarge: base.labelLarge?.copyWith(fontWeight: FontWeight.w600),
    );

    return ThemeData(
      useMaterial3: true,
      colorScheme: const ColorScheme.light(
        primary: AdelssColors.ink,
        onPrimary: Colors.white,
        secondary: AdelssColors.gold,
        onSecondary: AdelssColors.ink,
        surface: AdelssColors.paper,
        onSurface: AdelssColors.ink,
        error: AdelssColors.danger,
      ),
      scaffoldBackgroundColor: AdelssColors.cream,
      textTheme: textTheme,
      appBarTheme: AppBarTheme(
        backgroundColor: AdelssColors.cream,
        foregroundColor: AdelssColors.ink,
        elevation: 0,
        scrolledUnderElevation: 0,
        centerTitle: false,
        titleTextStyle: textTheme.titleLarge,
      ),
      dividerColor: AdelssColors.line,
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: AdelssColors.paper,
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
        hintStyle: const TextStyle(color: AdelssColors.muted),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: const BorderSide(color: AdelssColors.line),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: const BorderSide(color: AdelssColors.line),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: const BorderSide(color: AdelssColors.ink, width: 1.4),
        ),
      ),
      cardTheme: CardThemeData(
        color: AdelssColors.paper,
        elevation: 0,
        margin: EdgeInsets.zero,
        clipBehavior: Clip.antiAlias,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(20),
          side: const BorderSide(color: AdelssColors.line),
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: AdelssColors.ink,
          foregroundColor: Colors.white,
          minimumSize: const Size.fromHeight(52),
          textStyle: const TextStyle(fontWeight: FontWeight.w700, fontSize: 16),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: AdelssColors.ink,
          minimumSize: const Size.fromHeight(52),
          side: const BorderSide(color: AdelssColors.line),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(foregroundColor: AdelssColors.inkSoft),
      ),
      chipTheme: ChipThemeData(
        backgroundColor: AdelssColors.cream,
        side: const BorderSide(color: AdelssColors.line),
        labelStyle: textTheme.labelLarge,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      ),
      navigationBarTheme: NavigationBarThemeData(
        backgroundColor: AdelssColors.paper,
        elevation: 0,
        height: 72,
        indicatorColor: AdelssColors.ink.withValues(alpha: 0.08),
        labelTextStyle: WidgetStateProperty.resolveWith((states) {
          final selected = states.contains(WidgetState.selected);
          return TextStyle(
            fontSize: 12,
            fontWeight: selected ? FontWeight.w700 : FontWeight.w500,
            color: selected ? AdelssColors.ink : AdelssColors.muted,
          );
        }),
        iconTheme: WidgetStateProperty.resolveWith((states) {
          final selected = states.contains(WidgetState.selected);
          return IconThemeData(
            color: selected ? AdelssColors.ink : AdelssColors.muted,
            size: 24,
          );
        }),
      ),
      floatingActionButtonTheme: const FloatingActionButtonThemeData(
        backgroundColor: AdelssColors.ink,
        foregroundColor: Colors.white,
      ),
    );
  }
}
