import '../../core/constants/api_endpoints.dart';
import '../../core/services/api_service.dart';
import '../models/appointment_model.dart';

class AppointmentRepository {
  final ApiService _apiService;

  AppointmentRepository(this._apiService);

  /// Créer un rendez-vous
  Future<Map<String, dynamic>> createAppointment({
    required int propertyId,
    required DateTime date,
    required String time,
  }) async {
    try {
      final response = await _apiService.post(
        ApiEndpoints.createAppointment,
        data: {
          'property_id': propertyId,
          'date': date.toIso8601String().split('T')[0], // YYYY-MM-DD
          'time': time,
        },
      );
      return response.data['data'];
    } catch (e) {
      throw Exception('Erreur lors de la création du rendez-vous: $e');
    }
  }

  /// Obtenir les créneaux horaires disponibles pour une date
  Future<List<Map<String, dynamic>>> getAvailableTimeSlots(DateTime date) async {
    try {
      final response = await _apiService.get(
        ApiEndpoints.availableTimeSlots,
        queryParameters: {
          'date': date.toIso8601String().split('T')[0],
        },
      );
      return List<Map<String, dynamic>>.from(response.data['data']);
    } catch (e) {
      throw Exception('Erreur lors du chargement des créneaux: $e');
    }
  }

  /// Obtenir tous les rendez-vous de l'utilisateur
  Future<List<AppointmentModel>> getMyAppointments({
    String? status,
    int page = 1,
  }) async {
    try {
      final queryParams = {
        'page': page,
        if (status != null) 'status': status,
      };

      final response = await _apiService.get(
        ApiEndpoints.myAppointments,
        queryParameters: queryParams,
      );

      final List<dynamic> data = response.data['data'];
      return data.map((json) => AppointmentModel.fromJson(json)).toList();
    } catch (e) {
      throw Exception('Erreur lors du chargement des rendez-vous: $e');
    }
  }

  /// Obtenir les détails d'un rendez-vous
  Future<AppointmentModel> getAppointmentDetail(int id) async {
    try {
      final response = await _apiService.get(
        ApiEndpoints.getAppointmentDetailUrl(id),
      );
      return AppointmentModel.fromJson(response.data['data']);
    } catch (e) {
      throw Exception('Erreur lors du chargement du rendez-vous: $e');
    }
  }

  /// Annuler un rendez-vous
  Future<void> cancelAppointment(int id) async {
    try {
      await _apiService.delete(
        ApiEndpoints.getCancelAppointmentUrl(id),
      );
    } catch (e) {
      throw Exception('Erreur lors de l\'annulation: $e');
    }
  }

  /// Valider un rendez-vous (pour bailleur)
  Future<void> validateAppointment(int id) async {
    try {
      await _apiService.put(
        ApiEndpoints.getValidateAppointmentUrl(id),
      );
    } catch (e) {
      throw Exception('Erreur lors de la validation: $e');
    }
  }
}