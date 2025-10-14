import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'core/theme/app_theme.dart';
import 'core/services/notification_service.dart';
import 'data/providers/auth_provider.dart';
import 'data/providers/base_provider.dart';
import 'features/auth/screens/login_screen.dart';
import 'features/auth/screens/register_screen.dart';
import 'features/dashboard/screens/client_dashboard_screen.dart';
import 'features/dashboard/screens/landlord_dashboard_screen.dart';
import 'features/properties/screens/property_list_screen.dart';
import 'features/invoices/screens/invoice_list_screen.dart';
import 'features/expenses/screens/expense_list_screen.dart';
import 'features/profile/screens/profile_screen.dart';



void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  // Initialiser SharedPreferences
  final prefs = await SharedPreferences.getInstance();
  
  // Initialiser OneSignal
  await NotificationService().initialize('YOUR_ONESIGNAL_APP_ID');
  
  runApp(
    ProviderScope(
      overrides: [
        sharedPreferencesProvider.overrideWithValue(prefs),
      ],
      child: const MyApp(),
    ),
  );
}

class MyApp extends ConsumerWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authState = ref.watch(authStateProvider);

    return MaterialApp(
      title: 'Gestion Immobilière',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.lightTheme,
      home: authState.isLoading
          ? const Scaffold(
              body: Center(child: CircularProgressIndicator()),
            )
          : authState.isAuthenticated
              ? authState.user?.isClient ?? true
                  ? const ClientDashboardScreen()
                  : const LandlordDashboardScreen()
              : const LoginScreen(),
      routes: {
        '/login': (context) => const LoginScreen(),
        '/register': (context) => const RegisterScreen(),
        '/properties': (context) => const PropertyListScreen(),
        '/client-dashboard': (context) => const ClientDashboardScreen(),
        '/landlord-dashboard': (context) => const LandlordDashboardScreen(),
        '/invoices': (context) => const InvoiceListScreen(),
        '/expenses': (context) => const ExpenseListScreen(),
        '/profile': (context) => const ProfileScreen(),
      },
    );
  }
}