import '../../core/services/api_service.dart';
import '../../core/constants/api_endpoints.dart';
import '../models/property_model.dart';

class PropertyRepository {
  final ApiService _apiService;

  PropertyRepository(this._apiService);

  Future<Map<String, dynamic>> getProperties({
    int page = 1,
    String? district,
    double? minPrice,
    double? maxPrice,
    int? bedrooms,
    String? propertyType,
    String? search,
  }) async {
    final response = await _apiService.get(
      ApiEndpoints.properties,
      queryParameters: {
        'page': page,
        if (district != null) 'district': district,
        if (minPrice != null) 'min_price': minPrice,
        if (maxPrice != null) 'max_price': maxPrice,
        if (bedrooms != null) 'bedrooms': bedrooms,
        if (propertyType != null) 'property_type': propertyType,
        if (search != null) 'search': search,
      },
    );

    final data = response.data['data'];
    final properties = (data as List)
        .map((json) => PropertyModel.fromJson(json))
        .toList();

    return {
      'properties': properties,
      'current_page': response.data['meta']['current_page'],
      'last_page': response.data['meta']['last_page'],
    };
  }

  Future<PropertyModel> getPropertyDetail(int id) async {
    final response = await _apiService.get(
      ApiEndpoints.propertyDetail(id),
    );

    return PropertyModel.fromJson(response.data['data']);
  }

  Future<List<String>> getDistricts() async {
    final response = await _apiService.get(ApiEndpoints.districts);
    return List<String>.from(response.data['data']);
  }

  Future<double> getVisitPrice() async {
    final response = await _apiService.get(ApiEndpoints.visitSettings);
    return double.parse(response.data['data']['visit_price'].toString());
  }
}
