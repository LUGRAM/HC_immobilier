import '../../core/services/api_service.dart';
import '../../core/constants/api_endpoints.dart';
import '../models/lease_model.dart';

class LeaseRepository {
  final ApiService _apiService;

  LeaseRepository(this._apiService);

  Future<LeaseModel?> getActiveLease() async {
    try {
      final response = await _apiService.get(ApiEndpoints.activeLease);
      return LeaseModel.fromJson(response.data['data']);
    } catch (e) {
      return null;
    }
  }

  Future<LeaseModel> getLeaseDetail(int id) async {
    final response = await _apiService.get('/leases/$id');
    return LeaseModel.fromJson(response.data['data']);
  }
}