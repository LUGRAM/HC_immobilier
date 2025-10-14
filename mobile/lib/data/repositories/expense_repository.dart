import '../../core/services/api_service.dart';
import '../../core/constants/api_endpoints.dart';
import '../models/expense_model.dart';

class ExpenseRepository {
  final ApiService _apiService;

  ExpenseRepository(this._apiService);

  Future<List<ExpenseModel>> getExpenses({
    String? category,
    DateTime? startDate,
    DateTime? endDate,
  }) async {
    final response = await _apiService.get(
      ApiEndpoints.expenses,
      queryParameters: {
        if (category != null) 'category': category,
        if (startDate != null) 'start_date': startDate.toIso8601String(),
        if (endDate != null) 'end_date': endDate.toIso8601String(),
      },
    );

    final data = response.data['data'];
    return (data as List)
        .map((json) => ExpenseModel.fromJson(json))
        .toList();
  }

  Future<ExpenseModel> createExpense(ExpenseModel expense) async {
    final response = await _apiService.post(
      ApiEndpoints.expenses,
      data: expense.toJson(),
    );

    return ExpenseModel.fromJson(response.data['data']);
  }

  Future<void> deleteExpense(int id) async {
    await _apiService.delete('${ApiEndpoints.expenses}/$id');
  }

  Future<Map<String, dynamic>> getSummary() async {
    final response = await _apiService.get(ApiEndpoints.expenseSummary);
    return response.data['data'];
  }

  Future<List<Map<String, dynamic>>> getByCategory() async {
    final response = await _apiService.get(ApiEndpoints.expensesByCategory);
    return List<Map<String, dynamic>>.from(response.data['data']);
  }
}