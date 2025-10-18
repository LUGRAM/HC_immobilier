import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'core/constants/app_theme.dart';
import 'core/constants/app_router.dart';
import 'data/providers/auth_provider.dart';
import 'features/auth/screens/splash_screen.dart';
import 'features/home/screens/home_screen.dart';
import 'features/auth/screens/login_screen.dart';

/// Widget racine de l'application HouseConnect
class App extends ConsumerWidget {
  const App({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authState = ref.watch(authStateProvider);

    return MaterialApp(
      // ==================== CONFIGURATION GÉNÉRALE ====================
      title: 'HouseConnect',
      debugShowCheckedModeBanner: false,

      // ==================== THÈME ====================
      theme: AppTheme.lightTheme,
      darkTheme: AppTheme.darkTheme,
      themeMode: ThemeMode.light, // Forcer le mode clair pour l'instant

      // ==================== LOCALISATION ====================
      locale: const Locale('fr', 'FR'),
      supportedLocales: const [
        Locale('fr', 'FR'), // Français
      ],
      localizationsDelegates: const [
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],

      // ==================== ROUTING ====================
      onGenerateRoute: AppRouter.onGenerateRoute,
      
      // ==================== PAGE INITIALE ====================
      home: _getInitialScreen(authState),

      // ==================== BUILDERS ====================
      builder: (context, child) {
        return MediaQuery(
          // Empêcher le scaling du texte par les paramètres système
          data: MediaQuery.of(context).copyWith(
            textScaleFactor: MediaQuery.of(context).textScaleFactor.clamp(0.8, 1.2),
          ),
          child: child!,
        );
      },
    );
  }

  /// Détermine l'écran initial en fonction de l'état d'authentification
  Widget _getInitialScreen(AuthState authState) {
    // Si en cours de chargement, afficher le splash screen
    if (authState.isLoading) {
      return const SplashScreen();
    }

    // Si authentifié
    if (authState.isAuthenticated && authState.user != null) {
      final user = authState.user!;

      // Si c'est un client et qu'il n'a pas validé de bien, rediriger vers PropertyListScreen
      if (user.isClient && !user.hasValidatedProperty) {
        return Scaffold(
          body: Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(Icons.home_outlined, size: 80, color: Colors.grey),
                const SizedBox(height: 24),
                const Text(
                  'Réservez une visite pour accéder\nà toutes les fonctionnalités',
                  textAlign: TextAlign.center,
                  style: TextStyle(fontSize: 16, color: Colors.grey),
                ),
                const SizedBox(height: 32),
                ElevatedButton(
                  onPressed: () {
                    // Navigation vers PropertyListScreen
                    Navigator.of(context as BuildContext).pushReplacementNamed(
                      AppRouter.propertyList,
                    );
                  },
                  child: const Text('Explorer les biens'),
                ),
              ],
            ),
          ),
        );
      }

      // Sinon, afficher le HomeScreen
      return const HomeScreen();
    }

    // Si non authentifié, afficher le splash qui redirigera vers login
    return const SplashScreen();
  }
}

/// Widget pour gérer les erreurs globales
class ErrorBoundary extends StatelessWidget {
  final Widget child;

  const ErrorBoundary({super.key, required this.child});

  @override
  Widget build(BuildContext context) {
    return child;
  }
}