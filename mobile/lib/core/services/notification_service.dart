import 'package:onesignal_flutter/onesignal_flutter.dart';
import 'package:flutter/foundation.dart';

class NotificationService {
  static final NotificationService _instance = NotificationService._internal();
  factory NotificationService() => _instance;
  NotificationService._internal();

  Future<void> initialize(String appId) async {
    try {
      OneSignal.shared.setLogLevel(OSLogLevel.verbose, OSLogLevel.none);
      OneSignal.shared.setAppId(appId);

      // Demander la permission
      await OneSignal.shared.promptUserForPushNotificationPermission();

      // Gérer les notifications reçues
      OneSignal.shared.setNotificationWillShowInForegroundHandler(
        (OSNotificationReceivedEvent event) {
          if (kDebugMode) {
            print('Notification received: ${event.notification.body}');
          }
          event.complete(event.notification);
        },
      );

      // Gérer les clics sur notifications
      OneSignal.shared.setNotificationOpenedHandler(
        (OSNotificationOpenedResult result) {
          if (kDebugMode) {
            print('Notification opened: ${result.notification.body}');
          }
          // Navigation selon le type de notification
          _handleNotificationClick(result.notification.additionalData);
        },
      );

    } catch (e) {
      if (kDebugMode) {
        print('Error initializing OneSignal: $e');
      }
    }
  }

  Future<String?> getPlayerId() async {
    final status = await OneSignal.shared.getDeviceState();
    return status?.userId;
  }

  void _handleNotificationClick(Map<String, dynamic>? data) {
    if (data == null) return;

    final type = data['type'];
    
    // Navigation vers l'écran approprié selon le type
    switch (type) {
      case 'appointment_reminder':
        // Naviguer vers détails du rendez-vous
        break;
      case 'new_invoice':
        // Naviguer vers liste des factures
        break;
      case 'payment_received':
        // Naviguer vers historique des paiements
        break;
    }
  }
}