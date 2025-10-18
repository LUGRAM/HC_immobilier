import 'package:flutter/material.dart';
import '../../features/auth/screens/splash_screen.dart';
import '../../features/auth/screens/login_screen.dart';
import '../../features/auth/screens/register_screen.dart';
import '../../features/home/screens/home_screen.dart';
import '../../features/properties/screens/property_list_screen.dart';
import '../../features/properties/screens/property_detail_screen.dart';
import '../../features/appointments/screens/appointment_booking_screen.dart';
import '../../features/appointments/screens/appointment_detail_screen.dart';
import '../../features/appointments/screens/appointment_list_screen.dart';
import '../../features/payments/screens/payment_webview_screen.dart';
import '../../features/payments/screens/payment_success_screen.dart';
import '../../features/profile/screens/profile_screen.dart';
import '../../features/profile/screens/settings_screen.dart';
import '../../features/dashboard/screens/client_dashboard_screen.dart';
import '../../features/dashboard/screens/landlord_dashboard_screen.dart';
import '../../features/invoices/screens/invoice_list_screen.dart';
import '../../features/invoices/screens/invoice_detail_screen.dart';
import '../../features/leases/screens/lease_detail_screen.dart';
import '../../features/maintenance/screens/maintenance_list_screen.dart';

/// Système de navigation centralisé de l'application
class AppRouter {
  AppRouter._(); // Constructeur privé

  // ============ ROUTES CONSTANTES ============
  static const String splash = '/';
  static const String login = '/login';
  static const String register = '/register';
  static const String home = '/home';
  
  // Properties
  static const String propertyList = '/properties';
  static const String propertyDetail = '/property-detail';
  
  // Appointments
  static const String appointmentBooking = '/appointment-booking';
  static const String appointmentDetail = '/appointment-detail';
  static const String appointmentList = '/appointments';
  
  // Payments
  static const String paymentWebview = '/payment-webview';
  static const String paymentSuccess = '/payment-success';
  
  // Profile
  static const String profile = '/profile';
  static const String settings = '/settings';
  
  // Dashboard
  static const String clientDashboard = '/client-dashboard';
  static const String landlordDashboard = '/landlord-dashboard';
  
  // Invoices
  static const String invoiceList = '/invoices';
  static const String invoiceDetail = '/invoice-detail';
  
  // Leases
  static const String leaseDetail = '/lease-detail';
  
  // Maintenance
  static const String maintenanceList = '/maintenance';

  // ============ GÉNÉRATEUR DE ROUTES ============
  static Route<dynamic> generateRoute(RouteSettings settings) {
    // Récupération des arguments
    final args = settings.arguments as Map<String, dynamic>?;

    switch (settings.name) {
      // ============ AUTH ============
      case splash:
        return MaterialPageRoute(builder: (_) => const SplashScreen());

      case login:
        return MaterialPageRoute(builder: (_) => const LoginScreen());

      case register:
        return MaterialPageRoute(builder: (_) => const RegisterScreen());

      // ============ HOME ============
      case home:
        return MaterialPageRoute(builder: (_) => const HomeScreen());

      // ============ PROPERTIES ============
      case propertyList:
        return MaterialPageRoute(
          builder: (_) => PropertyListScreen(
            autoFocus: args?['autoFocus'] ?? false,
            initialType: args?['initialType'],
          ),
        );

      case propertyDetail:
        if (args == null || args['property'] == null) {
          return _errorRoute('Propriété manquante');
        }
        return MaterialPageRoute(
          builder: (_) => PropertyDetailScreen(
            property: args['property'],
          ),
        );

      // ============ APPOINTMENTS ============
      case appointmentBooking:
        if (args == null || args['property'] == null) {
          return _errorRoute('Propriété manquante');
        }
        return MaterialPageRoute(
          builder: (_) => AppointmentBookingScreen(
            property: args['property'],
          ),
        );

      case appointmentDetail:
        if (args == null || args['appointmentId'] == null) {
          return _errorRoute('ID rendez-vous manquant');
        }
        return MaterialPageRoute(
          builder: (_) => AppointmentDetailScreen(
            appointmentId: args['appointmentId'],
          ),
        );

      case appointmentList:
        return MaterialPageRoute(
          builder: (_) => const AppointmentListScreen(),
        );

      // ============ PAYMENTS ============
      case paymentWebview:
        if (args == null || 
            args['paymentUrl'] == null ||
            args['transactionId'] == null ||
            args['appointmentId'] == null ||
            args['amount'] == null) {
          return _errorRoute('Paramètres paiement manquants');
        }
        return MaterialPageRoute(
          builder: (_) => PaymentWebViewScreen(
            paymentUrl: args['paymentUrl'],
            transactionId: args['transactionId'],
            appointmentId: args['appointmentId'],
            amount: args['amount'],
          ),
        );

      case paymentSuccess:
        if (args == null ||
            args['transactionId'] == null ||
            args['amount'] == null ||
            args['appointmentId'] == null) {
          return _errorRoute('Paramètres succès manquants');
        }
        return MaterialPageRoute(
          builder: (_) => PaymentSuccessScreen(
            transactionId: args['transactionId'],
            amount: args['amount'],
            appointmentId: args['appointmentId'],
          ),
        );

      // ============ PROFILE ============
      case profile:
        return MaterialPageRoute(builder: (_) => const ProfileScreen());

      case settings:
        return MaterialPageRoute(builder: (_) => const SettingsScreen());

      // ============ DASHBOARD ============
      case clientDashboard:
        return MaterialPageRoute(
          builder: (_) => const ClientDashboardScreen(),
        );

      case landlordDashboard:
        return MaterialPageRoute(
          builder: (_) => const LandlordDashboardScreen(),
        );

      // ============ INVOICES ============
      case invoiceList:
        return MaterialPageRoute(builder: (_) => const InvoiceListScreen());

      case invoiceDetail:
        if (args == null || args['invoiceId'] == null) {
          return _errorRoute('ID facture manquant');
        }
        return MaterialPageRoute(
          builder: (_) => InvoiceDetailScreen(
            invoiceId: args['invoiceId'],
          ),
        );

      // ============ LEASES ============
      case leaseDetail:
        if (args == null || args['leaseId'] == null) {
          return _errorRoute('ID bail manquant');
        }
        return MaterialPageRoute(
          builder: (_) => LeaseDetailScreen(
            leaseId: args['leaseId'],
          ),
        );

      // ============ MAINTENANCE ============
      case maintenanceList:
        return MaterialPageRoute(
          builder: (_) => const MaintenanceListScreen(),
        );

      // ============ DEFAULT ============
      default:
        return _errorRoute('Route "${settings.name}" non trouvée');
    }
  }

  // ============ ROUTE D'ERREUR ============
  static Route<dynamic> _errorRoute(String message) {
    return MaterialPageRoute(
      builder: (_) => Scaffold(
        appBar: AppBar(
          title: Text('Erreur'),
          backgroundColor: Colors.red,
          foregroundColor: Colors.white,
        ),
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(24.0),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(
                  Icons.error_outline,
                  size: 80,
                  color: Colors.red,
                ),
                SizedBox(height: 16),
                Text(
                  'Erreur de navigation',
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                SizedBox(height: 8),
                Text(
                  message,
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.grey[600],
                  ),
                ),
                SizedBox(height: 24),
                ElevatedButton(
                  onPressed: () {
                    Navigator.of(_).pushNamedAndRemoveUntil(
                      home,
                      (route) => false,
                    );
                  },
                  child: Text('Retour à l\'accueil'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  // ============ MÉTHODES D'AIDE À LA NAVIGATION ============
  
  /// Navigue vers une route avec arguments
  static Future<T?> push<T>(
    BuildContext context,
    String routeName, {
    Map<String, dynamic>? arguments,
  }) {
    return Navigator.pushNamed<T>(
      context,
      routeName,
      arguments: arguments,
    );
  }

  /// Remplace la route actuelle
  static Future<T?> pushReplacement<T, TO>(
    BuildContext context,
    String routeName, {
    Map<String, dynamic>? arguments,
  }) {
    return Navigator.pushReplacementNamed<T, TO>(
      context,
      routeName,
      arguments: arguments,
    );
  }

  /// Supprime toutes les routes et navigue
  static Future<T?> pushAndRemoveUntil<T>(
    BuildContext context,
    String routeName, {
    Map<String, dynamic>? arguments,
  }) {
    return Navigator.pushNamedAndRemoveUntil<T>(
      context,
      routeName,
      (route) => false,
      arguments: arguments,
    );
  }

  /// Retour à la route précédente
  static void pop<T>(BuildContext context, [T? result]) {
    Navigator.pop<T>(context, result);
  }

  /// Vérifie si une route peut être pop
  static bool canPop(BuildContext context) {
    return Navigator.canPop(context);
  }
}

/// Extension pour faciliter la navigation
extension NavigationExtension on BuildContext {
  /// Navigue vers une route
  Future<T?> navigateTo<T>(
    String routeName, {
    Map<String, dynamic>? arguments,
  }) {
    return AppRouter.push<T>(this, routeName, arguments: arguments);
  }

  /// Remplace la route actuelle
  Future<T?> replaceWith<T, TO>(
    String routeName, {
    Map<String, dynamic>? arguments,
  }) {
    return AppRouter.pushReplacement<T, TO>(
      this,
      routeName,
      arguments: arguments,
    );
  }

  /// Supprime toutes les routes et navigue
  Future<T?> clearAndNavigateTo<T>(
    String routeName, {
    Map<String, dynamic>? arguments,
  }) {
    return AppRouter.pushAndRemoveUntil<T>(
      this,
      routeName,
      arguments: arguments,
    );
  }

  /// Retour
  void goBack<T>([T? result]) {
    AppRouter.pop<T>(this, result);
  }
}