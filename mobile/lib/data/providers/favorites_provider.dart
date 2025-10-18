import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hive_flutter/hive_flutter.dart';

// Provider pour la liste des IDs de propriétés favorites
final favoritesProvider = StateNotifierProvider<FavoritesNotifier, Set<int>>(
  (ref) => FavoritesNotifier(),
);

// Provider pour vérifier si une propriété est favorite
final isFavoriteProvider = Provider.family<bool, int>((ref, propertyId) {
  final favorites = ref.watch(favoritesProvider);
  return favorites.contains(propertyId);
});

class FavoritesNotifier extends StateNotifier<Set<int>> {
  static const String _favoritesBoxName = 'favorites';
  Box<dynamic>? _favoritesBox;

  FavoritesNotifier() : super(<int>{}) {
    _loadFavorites();
  }

  // Charger les favoris depuis Hive
  Future<void> _loadFavorites() async {
    try {
      _favoritesBox = await Hive.openBox(_favoritesBoxName);
      final List<dynamic> savedFavorites = 
          _favoritesBox?.get('favorite_properties', defaultValue: <int>[]) ?? [];
      
      state = savedFavorites.cast<int>().toSet();
    } catch (e) {
      // Si erreur, initialiser avec un Set vide
      state = <int>{};
    }
  }

  // Sauvegarder les favoris dans Hive
  Future<void> _saveFavorites() async {
    try {
      await _favoritesBox?.put('favorite_properties', state.toList());
    } catch (e) {
      // Gérer l'erreur silencieusement
    }
  }

  // Toggle favori (ajouter ou retirer)
  Future<void> toggleFavorite(int propertyId) async {
    final newState = Set<int>.from(state);
    
    if (newState.contains(propertyId)) {
      newState.remove(propertyId);
    } else {
      newState.add(propertyId);
    }
    
    state = newState;
    await _saveFavorites();
  }

  // Ajouter un favori
  Future<void> addFavorite(int propertyId) async {
    if (!state.contains(propertyId)) {
      state = {...state, propertyId};
      await _saveFavorites();
    }
  }

  // Retirer un favori
  Future<void> removeFavorite(int propertyId) async {
    if (state.contains(propertyId)) {
      final newState = Set<int>.from(state);
      newState.remove(propertyId);
      state = newState;
      await _saveFavorites();
    }
  }

  // Vérifier si une propriété est favorite
  bool isFavorite(int propertyId) {
    return state.contains(propertyId);
  }

  // Obtenir tous les favoris
  Set<int> getAllFavorites() {
    return Set<int>.from(state);
  }

  // Effacer tous les favoris
  Future<void> clearAllFavorites() async {
    state = <int>{};
    await _saveFavorites();
  }
}