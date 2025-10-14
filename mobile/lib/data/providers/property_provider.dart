import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../repositories/property_repository.dart';
import '../models/property_model.dart';
import 'base_provider.dart';


final propertyRepositoryProvider = Provider<PropertyRepository>((ref) {
  final apiService = ref.watch(apiServiceProvider);
  return PropertyRepository(apiService);
});

final propertiesProvider = FutureProvider.autoDispose.family<
    Map<String, dynamic>,
    PropertyFilters
>((ref, filters) async {
  final repository = ref.watch(propertyRepositoryProvider);
  return repository.getProperties(
    page: filters.page,
    district: filters.district,
    minPrice: filters.minPrice,
    maxPrice: filters.maxPrice,
    bedrooms: filters.bedrooms,
    propertyType: filters.propertyType,
    search: filters.search,
  );
});

final propertyDetailProvider = FutureProvider.autoDispose.family<
    PropertyModel,
    int
>((ref, id) async {
  final repository = ref.watch(propertyRepositoryProvider);
  return repository.getPropertyDetail(id);
});

class PropertyFilters {
  final int page;
  final String? district;
  final double? minPrice;
  final double? maxPrice;
  final int? bedrooms;
  final String? propertyType;
  final String? search;

  PropertyFilters({
    this.page = 1,
    this.district,
    this.minPrice,
    this.maxPrice,
    this.bedrooms,
    this.propertyType,
    this.search,
  });
}
