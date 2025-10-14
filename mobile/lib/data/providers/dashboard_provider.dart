import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/constants/api_endpoints.dart';

import 'base_provider.dart';
final clientDashboardProvider = FutureProvider.autoDispose((ref) async {
  final apiService = ref.watch(apiServiceProvider);
  final response = await apiService.get(ApiEndpoints.clientDashboard);
  return response.data['data'];
});

final landlordDashboardProvider = FutureProvider.autoDispose((ref) async {
  final apiService = ref.watch(apiServiceProvider);
  final response = await apiService.get(ApiEndpoints.landlordDashboard);
  return response.data['data'];
});