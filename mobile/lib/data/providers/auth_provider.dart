import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../repositories/auth_repository.dart';
import '../models/user_model.dart';
import 'base_provider.dart';

// Provider pour AuthRepository
final authRepositoryProvider = Provider<AuthRepository>((ref) {
  final apiService = ref.watch(apiServiceProvider);
  final storageService = ref.watch(storageServiceProvider);
  return AuthRepository(apiService, storageService);
});

// Provider pour l'état d'authentification
final authStateProvider = StateNotifierProvider<AuthNotifier, AuthState>((ref) {
  final authRepository = ref.watch(authRepositoryProvider);
  return AuthNotifier(authRepository);
});

// State class amélioré
class AuthState {
  final UserModel? user;
  final bool isAuthenticated;
  final bool isLoading;
  final String? error;
  final bool hasValidatedProperty;

  AuthState({
    this.user,
    this.isAuthenticated = false,
    this.isLoading = false,
    this.error,
    this.hasValidatedProperty = false,
  });

  AuthState copyWith({
    UserModel? user,
    bool? isAuthenticated,
    bool? isLoading,
    String? error,
    bool? hasValidatedProperty,
  }) {
    return AuthState(
      user: user ?? this.user,
      isAuthenticated: isAuthenticated ?? this.isAuthenticated,
      isLoading: isLoading ?? this.isLoading,
      error: error,
      hasValidatedProperty: hasValidatedProperty ?? this.hasValidatedProperty,
    );
  }

  // Vérifier si l'utilisateur est un client
  bool get isClient => user?.role == 'client';

  // Vérifier si l'utilisateur est un bailleur
  bool get isLandlord => user?.role == 'landlord';

  // Vérifier si le dashboard doit être débloqué
  bool get canAccessDashboard {
    if (isLandlord) return true; // Bailleurs ont toujours accès
    if (isClient) return hasValidatedProperty; // Clients après validation
    return false;
  }
}

class AuthNotifier extends StateNotifier<AuthState> {
  final AuthRepository _authRepository;

  AuthNotifier(this._authRepository) : super(AuthState()) {
    _checkAuthStatus();
  }

  // Vérifier le statut d'authentification au démarrage
  Future<void> _checkAuthStatus() async {
    state = state.copyWith(isLoading: true);
    try {
      final user = await _authRepository.getCurrentUser();
      if (user != null) {
        final hasValidated = user.validatedPropertyId != null;
        state = state.copyWith(
          user: user,
          isAuthenticated: true,
          isLoading: false,
          hasValidatedProperty: hasValidated,
        );
      } else {
        state = state.copyWith(isLoading: false);
      }
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: e.toString(),
      );
    }
  }

  // Login
  Future<void> login(String email, String password) async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final user = await _authRepository.login(email, password);
      final hasValidated = user.validatedPropertyId != null;
      
      state = state.copyWith(
        user: user,
        isAuthenticated: true,
        isLoading: false,
        hasValidatedProperty: hasValidated,
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: e.toString(),
      );
      rethrow;
    }
  }

  // Register
  Future<void> register({
    required String firstName,
    required String lastName,
    required String email,
    required String password,
    required String role,
    String? phoneNumber,
  }) async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final user = await _authRepository.register(
        firstName: firstName,
        lastName: lastName,
        email: email,
        password: password,
        role: role,
        phoneNumber: phoneNumber,
      );
      
      state = state.copyWith(
        user: user,
        isAuthenticated: true,
        isLoading: false,
        hasValidatedProperty: false, // Nouveau compte
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: e.toString(),
      );
      rethrow;
    }
  }

  // Logout
  Future<void> logout() async {
    state = state.copyWith(isLoading: true);
    try {
      await _authRepository.logout();
      state = AuthState(); // Reset complet
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: e.toString(),
      );
    }
  }

  // Rafraîchir les données utilisateur
  Future<void> refreshUser() async {
    try {
      final user = await _authRepository.getCurrentUser();
      if (user != null ) {
        final hasValidated = user.validatedPropertyId != null;
        state = state.copyWith(
          user: user,
          hasValidatedProperty: hasValidated,
        );
      }
    } catch (e) {
      state = state.copyWith(error: e.toString());
    }
  }

  // Mettre à jour le profil
  Future<void> updateProfile({
    String? firstName,
    String? lastName,
    String? phoneNumber,
    String? avatarUrl,
  }) async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final updatedUser = await _authRepository.updateProfile(
        firstName: firstName,
        lastName: lastName,
        phoneNumber: phoneNumber,
        avatarUrl: avatarUrl,
      );
      
      state = state.copyWith(
        user: updatedUser,
        isLoading: false,
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: e.toString(),
      );
      rethrow;
    }
  }

  // Marquer un bien comme validé (appelé après validation par bailleur)
  void markPropertyValidated(int propertyId) {
    if (state.user != null) {
      final updatedUser = state.user!.copyWith(
        validatedPropertyId: propertyId,
      );
      state = state.copyWith(
        user: updatedUser,
        hasValidatedProperty: true,
      );
    }
  }

  // Vérifier si peut accéder au dashboard
  bool canAccessDashboard() {
    return state.canAccessDashboard;
  }
}

// Provider helper pour vérifier l'accès dashboard
final canAccessDashboardProvider = Provider<bool>((ref) {
  final authState = ref.watch(authStateProvider);
  return authState.canAccessDashboard;
});