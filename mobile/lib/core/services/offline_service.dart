import 'package:hive/hive.dart';
import 'package:connectivity_plus/connectivity_plus.dart';
import '../../data/models/property_model.dart';
import '../../data/models/appointment_model.dart';
import '../../data/models/user_model.dart';

class OfflineService {
  static const String _propertiesBox = 'properties';
  static const String _appointmentsBox = 'appointments';
  static const String _userBox = 'user';
  static const String _pendingActionsBox = 'pending_actions';

  final Connectivity _connectivity = Connectivity();

  /// Initialise Hive et ouvre les boxes
  Future<void> initialize() async {
    // Enregistrer les adapters
    if (!Hive.isAdapterRegistered(0)) {
      Hive.registerAdapter(PropertyAdapter());
    }
    if (!Hive.isAdapterRegistered(1)) {
      Hive.registerAdapter(AppointmentAdapter());
    }
    if (!Hive.isAdapterRegistered(2)) {
      Hive.registerAdapter(UserAdapter());
    }

    // Ouvrir les boxes
    await Hive.openBox<Property>(_propertiesBox);
    await Hive.openBox<Appointment>(_appointmentsBox);
    await Hive.openBox<User>(_userBox);
    await Hive.openBox<Map>(_pendingActionsBox);
  }

  /// Vérifie la connexion internet
  Future<bool> isOnline() async {
    var connectivityResult = await _connectivity.checkConnectivity();
    return connectivityResult != ConnectivityResult.none;
  }

  /// Stream de la connexion internet
  Stream<bool> get onConnectivityChanged {
    return _connectivity.onConnectivityChanged.map((result) {
      return result != ConnectivityResult.none;
    });
  }

  // ==================== PROPERTIES ====================

  /// Sauvegarde les propriétés en cache local
  Future<void> cacheProperties(List<Property> properties) async {
    final box = Hive.box<Property>(_propertiesBox);
    await box.clear();
    for (var property in properties) {
      await box.put(property.id, property);
    }
  }

  /// Récupère les propriétés du cache
  List<Property> getCachedProperties() {
    final box = Hive.box<Property>(_propertiesBox);
    return box.values.toList();
  }

  /// Récupère une propriété spécifique du cache
  Property? getCachedProperty(int id) {
    final box = Hive.box<Property>(_propertiesBox);
    return box.get(id);
  }

  /// Ajoute/Met à jour une propriété dans le cache
  Future<void> updateCachedProperty(Property property) async {
    final box = Hive.box<Property>(_propertiesBox);
    await box.put(property.id, property);
  }

  /// Supprime une propriété du cache
  Future<void> removeCachedProperty(int id) async {
    final box = Hive.box<Property>(_propertiesBox);
    await box.delete(id);
  }

  // ==================== APPOINTMENTS ====================

  /// Sauvegarde les rendez-vous en cache
  Future<void> cacheAppointments(List<Appointment> appointments) async {
    final box = Hive.box<Appointment>(_appointmentsBox);
    await box.clear();
    for (var appointment in appointments) {
      await box.put(appointment.id, appointment);
    }
  }

  /// Récupère les rendez-vous du cache
  List<Appointment> getCachedAppointments() {
    final box = Hive.box<Appointment>(_appointmentsBox);
    return box.values.toList();
  }

  /// Ajoute un rendez-vous au cache
  Future<void> addCachedAppointment(Appointment appointment) async {
    final box = Hive.box<Appointment>(_appointmentsBox);
    await box.put(appointment.id, appointment);
  }

  // ==================== USER ====================

  /// Sauvegarde l'utilisateur en cache
  Future<void> cacheUser(User user) async {
    final box = Hive.box<User>(_userBox);
    await box.put('current_user', user);
  }

  /// Récupère l'utilisateur du cache
  User? getCachedUser() {
    final box = Hive.box<User>(_userBox);
    return box.get('current_user');
  }

  /// Supprime l'utilisateur du cache (logout)
  Future<void> clearUserCache() async {
    final box = Hive.box<User>(_userBox);
    await box.clear();
  }

  // ==================== PENDING ACTIONS ====================

  /// Ajoute une action en attente (pour sync quand online)
  Future<void> addPendingAction(Map<String, dynamic> action) async {
    final box = Hive.box<Map>(_pendingActionsBox);
    final id = DateTime.now().millisecondsSinceEpoch.toString();
    await box.put(id, action);
  }

  /// Récupère toutes les actions en attente
  List<Map<String, dynamic>> getPendingActions() {
    final box = Hive.box<Map>(_pendingActionsBox);
    return box.values.map((e) => Map<String, dynamic>.from(e)).toList();
  }

  /// Supprime une action en attente après sync
  Future<void> removePendingAction(String id) async {
    final box = Hive.box<Map>(_pendingActionsBox);
    await box.delete(id);
  }

  /// Supprime toutes les actions en attente
  Future<void> clearPendingActions() async {
    final box = Hive.box<Map>(_pendingActionsBox);
    await box.clear();
  }

  /// Compte le nombre d'actions en attente
  int getPendingActionsCount() {
    final box = Hive.box<Map>(_pendingActionsBox);
    return box.length;
  }

  // ==================== SYNC ====================

  /// Synchronise les données quand online
  Future<void> syncPendingActions(
    Future<void> Function(Map<String, dynamic>) onSync,
  ) async {
    if (!await isOnline()) {
      throw Exception('No internet connection');
    }

    final pendingActions = getPendingActions();
    final box = Hive.box<Map>(_pendingActionsBox);

    for (var entry in box.toMap().entries) {
      try {
        await onSync(Map<String, dynamic>.from(entry.value));
        await box.delete(entry.key);
      } catch (e) {
        print('Failed to sync action ${entry.key}: $e');
        // Ne pas supprimer l'action, elle sera réessayée plus tard
      }
    }
  }

  // ==================== CACHE MANAGEMENT ====================

  /// Vide tout le cache
  Future<void> clearAllCache() async {
    await Hive.box<Property>(_propertiesBox).clear();
    await Hive.box<Appointment>(_appointmentsBox).clear();
    await Hive.box<User>(_userBox).clear();
    await Hive.box<Map>(_pendingActionsBox).clear();
  }

  /// Obtient la taille du cache
  Future<int> getCacheSize() async {
    int size = 0;
    size += Hive.box<Property>(_propertiesBox).length;
    size += Hive.box<Appointment>(_appointmentsBox).length;
    size += Hive.box<User>(_userBox).length;
    size += Hive.box<Map>(_pendingActionsBox).length;
    return size;
  }

  /// Nettoie le cache ancien (> 7 jours)
  Future<void> cleanOldCache() async {
    final box = Hive.box<Property>(_propertiesBox);
    final now = DateTime.now();
    final entriesToDelete = <dynamic>[];

    for (var entry in box.toMap().entries) {
      final property = entry.value;
      // Si la propriété a plus de 7 jours, la supprimer
      if (property.cachedAt != null) {
        final diff = now.difference(property.cachedAt!).inDays;
        if (diff > 7) {
          entriesToDelete.add(entry.key);
        }
      }
    }

    for (var key in entriesToDelete) {
      await box.delete(key);
    }
  }

  /// Ferme toutes les boxes
  Future<void> close() async {
    await Hive.close();
  }
}