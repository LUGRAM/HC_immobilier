import 'package:flutter/material.dart';
import 'package:onesignal_flutter/onesignal_flutter.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../constants/api_endpoints.dart';
import 'api_service.dart';
import 'storage_service.dart';

class NotificationService {
  static final NotificationService _instance = NotificationService._internal();
  factory NotificationService() => _instance;
  NotificationService._internal();

  late final StorageService _storageService;
  late final ApiService _apiService;
  bool _isInitialized = false;

  Future<void> init() async {
    _storageService = StorageService(await SharedPreferences.getInstance());
    _apiService = ApiService(_storageService);
  }

  /// Initialiser OneSignal
  Future<void> initialize({
    required String appId,
    required BuildContext context,
  }) async {
    if (_isInitialized) return;

    try {
      // Configuration OneSignal
      OneSignal.Debug.setLogLevel(OSLogLevel.verbose);
      
      OneSignal.initialize(appId);

      // Demander la permission pour les notifications
      await OneSignal.Notifications.requestPermission(true);

      // Configurer les handlers
      _setupNotificationHandlers(context);

      // Enregistrer le token sur le backend
      await _registerDeviceToken();

      _isInitialized = true;
      debugPrint('✅ OneSignal initialisé avec succès');
    } catch (e) {
      debugPrint('❌ Erreur initialisation OneSignal: $e');
    }
  }

  /// Configurer les handlers de notifications
  void _setupNotificationHandlers(BuildContext context) {
    // Handler pour notifications reçues en foreground
    OneSignal.Notifications.addForegroundWillDisplayListener((event) {
      debugPrint('📬 Notification reçue en foreground: ${event.notification.title}');
      
      // Afficher la notification (optionnel)
      event.preventDefault();
      OneSignal.Notifications.displayNotification(event.notification.notificationId);
    });

    // Handler pour clic sur notification
    OneSignal.Notifications.addClickListener((event) {
      debugPrint('👆 Notification cliquée');
      _handleNotificationClick(context, event);
    });

    // Handler pour permission accordée
    OneSignal.Notifications.addPermissionObserver((state) {
      debugPrint('🔔 Permission notifications: $state');
    });
  }

  /// Gérer le clic sur notification
  void _handleNotificationClick(
    BuildContext context,
    OSNotificationClickEvent event,
  ) {
    final additionalData = event.notification.additionalData;
    
    if (additionalData == null) return;

    final type = additionalData['type'] as String?;
    final id = additionalData['id'] as int?;

    if (type == null || id == null) return;

    // Navigation basée sur le type
    switch (type) {
      case 'appointment_reminder':
        Navigator.pushNamed(
          context,
          '/appointment-detail',
          arguments: {'appointmentId': id},
        );
        break;

      case 'payment_confirmed':
        Navigator.pushNamed(
          context,
          '/payment-success',
          arguments: {'appointmentId': id},
        );
        break;

      case 'property_available':
        Navigator.pushNamed(
          context,
          '/property-detail',
          arguments: {'propertyId': id},
        );
        break;

      case 'maintenance_update':
        Navigator.pushNamed(
          context,
          '/maintenance-detail',
          arguments: {'maintenanceId': id},
        );
        break;

      case 'invoice_reminder':
        Navigator.pushNamed(
          context,
          '/invoice-detail',
          arguments: {'invoiceId': id},
        );
        break;

      default:
        debugPrint('⚠️ Type de notification inconnu: $type');
    }
  }

  /// Enregistrer le device token sur le backend
  Future<void> _registerDeviceToken() async {
    try {
      final playerId = OneSignal.User.pushSubscription.id;
      
      if (playerId == null) {
        debugPrint('⚠️ Player ID OneSignal non disponible');
        return;
      }

      await _apiService.post(
        ApiEndpoints.registerDeviceToken,
        data: {
          'player_id': playerId,
          'platform': _getPlatform(),
        },
      );

      debugPrint('✅ Device token enregistré: $playerId');
    } catch (e) {
      debugPrint('❌ Erreur enregistrement token: $e');
    }
  }

  /// Définir l'utilisateur externe (user_id backend)
  Future<void> setExternalUserId(String userId) async {
    try {
      OneSignal.login(userId);
      debugPrint('✅ External User ID défini: $userId');
    } catch (e) {
      debugPrint('❌ Erreur définition External User ID: $e');
    }
  }

  /// Supprimer l'utilisateur externe (logout)
  Future<void> removeExternalUserId() async {
    try {
      OneSignal.logout();
      debugPrint('✅ External User ID supprimé');
    } catch (e) {
      debugPrint('❌ Erreur suppression External User ID: $e');
    }
  }

  /// Définir des tags personnalisés
  Future<void> setTags(Map<String, String> tags) async {
    try {
      OneSignal.User.addTags(tags);
      debugPrint('✅ Tags définis: $tags');
    } catch (e) {
      debugPrint('❌ Erreur définition tags: $e');
    }
  }

  /// Obtenir le Player ID OneSignal
  String? getPlayerId() {
    return OneSignal.User.pushSubscription.id;
  }

  /// Vérifier si les notifications sont activées
  Future<bool> areNotificationsEnabled() async {
    try {
      final permission = await OneSignal.Notifications.permission;
      return permission;
    } catch (e) {
      debugPrint('❌ Erreur vérification permissions: $e');
      return false;
    }
  }

  /// Demander la permission pour les notifications
  Future<bool> requestPermission() async {
    try {
      final granted = await OneSignal.Notifications.requestPermission(true);
      return granted;
    } catch (e) {
      debugPrint('❌ Erreur demande permission: $e');
      return false;
    }
  }

  /// Activer/Désactiver les notifications
  Future<void> setNotificationsEnabled(bool enabled) async {
    try {
      if (enabled) {
        await OneSignal.Notifications.requestPermission(true);
      } else {
        // OneSignal ne permet pas de désactiver complètement,
        // mais on peut utiliser des tags pour filtrer
        await setTags({'notifications_disabled': 'true'});
      }
      debugPrint('✅ Notifications ${enabled ? "activées" : "désactivées"}');
    } catch (e) {
      debugPrint('❌ Erreur toggle notifications: $e');
    }
  }

  /// Envoyer une notification test (depuis le backend)
  Future<void> sendTestNotification() async {
    try {
      await _apiService.post(
        ApiEndpoints.sendTestNotification,
        data: {
          'player_id': getPlayerId(),
        },
      );
      debugPrint('✅ Notification test envoyée');
    } catch (e) {
      debugPrint('❌ Erreur envoi notification test: $e');
    }
  }

  /// Obtenir la plateforme actuelle
  String _getPlatform() {
    if (Theme.of(NavigationService.navigatorKey.currentContext!)
        .platform ==
        TargetPlatform.iOS) {
      return 'ios';
    }
    return 'android';
  }
}

// Service de navigation global pour accéder au contexte
class NavigationService {
  static GlobalKey<NavigatorState> navigatorKey = GlobalKey<NavigatorState>();
}
