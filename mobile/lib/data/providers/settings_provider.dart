import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../repositories/settings_repository.dart';
import 'base_provider.dart';

// Provider pour SettingsRepository
final settingsRepositoryProvider = Provider<SettingsRepository>((ref) {
  final apiService = ref.watch(apiServiceProvider);
  return SettingsRepository(apiService);
});

// Provider pour les paramètres de l'application
final settingsProvider = FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final repository = ref.watch(settingsRepositoryProvider);
  return repository.getSettings();
});

// Provider pour le prix des visites
final visitPriceProvider = FutureProvider.autoDispose<double>((ref) async {
  final settings = await ref.watch(settingsProvider.future);
  return (settings['visit_price'] as num?)?.toDouble() ?? 2000.0;
});

// Provider pour la devise
final currencyProvider = FutureProvider.autoDispose<String>((ref) async {
  final settings = await ref.watch(settingsProvider.future);
  return settings['currency'] as String? ?? 'XOF';
});