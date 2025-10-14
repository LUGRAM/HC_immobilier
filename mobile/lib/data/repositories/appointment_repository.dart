import '../../core/services/api_service.dart';
import '../../core/constants/api_endpoints.dart';
import '../models/appointment_model.dart';
import '../models/lease_model.dart';

class AppointmentRepository {
  final ApiService _apiService;

  AppointmentRepository(this._apiService);

  Future<Map<String, dynamic>> createAppointment({
    required int propertyId,
    required DateTime scheduledAt,
    required String phoneNumber,
    String? notes,
  }) async {
    final response = await _apiService.post(
      ApiEndpoints.appointments,
      data: {
        'property_id': propertyId,
        'scheduled_at': scheduledAt.toIso8601String(),
        'phone_number': phoneNumber,
        if (notes != null) 'notes': notes,
      },
    );

    return response.data['data'];
  }

  Future<List<AppointmentModel>> getAppointments({
    String? status,
    bool? upcoming,
  }) async {
    final response = await _apiService.get(
      ApiEndpoints.appointments,
      queryParameters: {
        if (status != null) 'status': status,
        if (upcoming != null) 'upcoming': upcoming,
      },
    );

    final data = response.data['data'];
    return (data as List)
        .map((json) => AppointmentModel.fromJson(json))
        .toList();
  }

  Future<AppointmentModel> getAppointmentDetail(int id) async {
    final response = await _apiService.get(
      ApiEndpoints.appointmentDetail(id),
    );

    return AppointmentModel.fromJson(response.data['data']);
  }

  Future<void> cancelAppointment(int id) async {
    await _apiService.put(ApiEndpoints.cancelAppointment(id));
  }

  Future<LeaseModel> requestLease(int appointmentId) async {
    final response = await _apiService.post(
      ApiEndpoints.requestLease(appointmentId),
    );

    return LeaseModel.fromJson(response.data['data']);
  }
}
