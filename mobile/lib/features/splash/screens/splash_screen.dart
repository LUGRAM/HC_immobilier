import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../../core/constants/app_colors.dart';
import '../../../data/providers/auth_provider.dart';

class SplashScreen extends ConsumerStatefulWidget {
  const SplashScreen({super.key});

  @override
  ConsumerState<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends ConsumerState<SplashScreen> {
  @override
  void initState() {
    super.initState();
    _navigateToNextScreen();
  }

  Future<void> _navigateToNextScreen() async {
    // Attendre 2 secondes pour afficher le splash
    await Future.delayed(const Duration(seconds: 2));

    if (!mounted) return;

    // Vérifier si l'utilisateur est connecté
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('auth_token');
    final hasSeenOnboarding = prefs.getBool('has_seen_onboarding') ?? false;

    if (!mounted) return;

    if (token != null && token.isNotEmpty) {
      // Utilisateur connecté - vérifier son rôle
      final userRole = prefs.getString('user_role') ?? 'client';
      
      if (userRole == 'landlord') {
        Navigator.pushReplacementNamed(context, '/landlord-dashboard');
      } else {
        Navigator.pushReplacementNamed(context, '/client-dashboard');
      }
    } else if (hasSeenOnboarding) {
      // Utilisateur non connecté mais a vu l'onboarding
      Navigator.pushReplacementNamed(context, '/login');
    } else {
      // Première utilisation - afficher l'onboarding
      Navigator.pushReplacementNamed(context, '/onboarding');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.primary,
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            // Logo de l'application
            Container(
              width: 120,
              height: 120,
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(24),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.1),
                    blurRadius: 20,
                    offset: const Offset(0, 10),
                  ),
                ],
              ),
              child: const Icon(
                Icons.home_rounded,
                size: 64,
                color: AppColors.primary,
              ),
            ),
            const SizedBox(height: 24),
            // Nom de l'application
            const Text(
              'HouseConnect',
              style: TextStyle(
                fontSize: 32,
                fontWeight: FontWeight.bold,
                color: Colors.white,
                letterSpacing: 1.2,
              ),
            ),
            const SizedBox(height: 8),
            // Slogan
            Text(
              'Trouvez votre logement idéal',
              style: TextStyle(
                fontSize: 16,
                color: Colors.white.withOpacity(0.9),
                fontWeight: FontWeight.w400,
              ),
            ),
            const SizedBox(height: 48),
            // Indicateur de chargement
            const CircularProgressIndicator(
              valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
            ),
          ],
        ),
      ),
    );
  }
}
