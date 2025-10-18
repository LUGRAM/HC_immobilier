import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../repositories/appointment_repository.dart';
import '../models/appointment_model.dart';
import 'base_provider.dart';

// Provider pour AppointmentRepository
final appointmentRepositoryProvider = Provider<AppointmentRepository>((ref) {
  final apiService = ref.watch(apiServiceProvider);
  return AppointmentRepository(apiService);
});

// Provider pour les rendez-vous de l'utilisateur
final myAppointmentsProvider = FutureProvider.autoDispose.family<
    List<AppointmentModel>,
    String?
>((ref, status) async {
  final repository = ref.watch(appointmentRepositoryProvider);
  return repository.getMyAppointments(status: status);
});

// Provider pour les détails d'un rendez-vous
final appointmentDetailProvider = FutureProvider.autoDispose.family<
    AppointmentModel,
    int
>((ref, id) async {
  final repository = ref.watch(appointmentRepositoryProvider);
  return repository.getAppointmentDetail(id);
});

// StateNotifier pour la gestion des rendez-vous
class AppointmentNotifier extends StateNotifier<AsyncValue<void>> {
  final AppointmentRepository _repository;

  AppointmentNotifier(this._repository) : super(const AsyncValue.data(null));

  /// Créer un rendez-vous
  Future<Map<String, dynamic>> createAppointment({
    required int propertyId,
    required DateTime date,
    required String time,
  }) async {
    state = const AsyncValue.loading();
    try {
      final result = await _repository.createAppointment(
        propertyId: propertyId,
        date: date,
        time: time,
      );
      state = const AsyncValue.data(null);
      return result;
    } catch (e, st) {
      state = AsyncValue.error(e, st);
      rethrow;
    }
  }

  /// Annuler un rendez-vous
  Future<void> cancelAppointment(int id) async {
    state = const AsyncValue.loading();
    try {
      await _repository.cancelAppointment(id);
      state = const AsyncValue.data(null);
    } catch (e, st) {
      state = AsyncValue.error(e, st);
      rethrow;
    }
  }

  /// Valider un rendez-vous (pour bailleur)
  Future<void> validateAppointment(int id) async {
    state = const AsyncValue.loading();
    try {
      await _repository.validateAppointment(id);
      state = const AsyncValue.data(null);
    } catch (e, st) {
      state = AsyncValue.error(e, st);
      rethrow;
    }
  }
}

// Provider pour le notifier
final appointmentProvider = StateNotifierProvider<AppointmentNotifier, AsyncValue<void>>((ref) {
  final repository = ref.watch(appointmentRepositoryProvider);
  return AppointmentNotifier(repository);
});