import '../../core/constants/api_endpoints.dart';
import '../../core/services/api_service.dart';

class SettingsRepository {
  final ApiService _apiService;

  SettingsRepository(this._apiService);

  /// Récupérer tous les paramètres
  Future<Map<String, dynamic>> getSettings() async {
    try {
      final response = await _apiService.get(ApiEndpoints.settings);
      return response.data['data'] as Map<String, dynamic>;
    } catch (e) {
      throw Exception('Erreur lors du chargement des paramètres: $e');
    }
  }

  /// Récupérer le prix des visites
  Future<double> getVisitPrice() async {
    try {
      final response = await _apiService.get(ApiEndpoints.visitPrice);
      return (response.data['data']['price'] as num).toDouble();
    } catch (e) {
      throw Exception('Erreur lors du chargement du prix: $e');
    }
  }
}