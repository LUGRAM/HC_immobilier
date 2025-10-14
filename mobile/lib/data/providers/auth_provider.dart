import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_riverpod/legacy.dart';
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

// State class
class AuthState {
  final UserModel? user;
  final bool isAuthenticated;
  final bool isLoading;
  final String? error;

  AuthState({
    this.user,
    this.isAuthenticated = false,
    this.isLoading = false,
    this.error,
  });

  AuthState copyWith({
    UserModel? user,
    bool? isAuthenticated,
    bool? isLoading,
    String? error,
  }) {
    return AuthState(
      user: user ?? this.user,
      isAuthenticated: isAuthenticated ?? this.isAuthenticated,
      isLoading: isLoading ?? this.isLoading,
      error: error,
    );
  }
}

// Notifier class
class AuthNotifier extends StateNotifier<AuthState> {
  final AuthRepository _authRepository;

  AuthNotifier(this._authRepository) : super(AuthState()) {
    _checkAuthStatus();
  }

  Future<void> _checkAuthStatus() async {
    state = state.copyWith(isLoading: true);
    
    final isAuth = await _authRepository.isAuthenticated();
    
    if (isAuth) {
      final user = await _authRepository.getCachedUser();
      state = state.copyWith(
        user: user,
        isAuthenticated: true,
        isLoading: false,
      );
    } else {
      state = state.copyWith(isLoading: false);
    }
  }

  Future<void> login(String email, String password) async {
    try {
      state = state.copyWith(isLoading: true, error: null);
      
      final result = await _authRepository.login(email, password);
      
      state = state.copyWith(
        user: result['user'],
        isAuthenticated: true,
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

  Future<void> register({
    required String firstName,
    required String lastName,
    required String email,
    required String phone,
    required String password,
  }) async {
    try {
      state = state.copyWith(isLoading: true, error: null);
      
      final result = await _authRepository.register(
        firstName: firstName,
        lastName: lastName,
        email: email,
        phone: phone,
        password: password,
      );
      
      state = state.copyWith(
        user: result['user'],
        isAuthenticated: true,
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

  Future<void> logout() async {
    await _authRepository.logout();
    state = AuthState();
  }

  Future<void> refreshUser() async {
    try {
      final user = await _authRepository.getCurrentUser();
      state = state.copyWith(user: user);
    } catch (e) {
      // Gérer l'erreur silencieusement
    }
  }
}