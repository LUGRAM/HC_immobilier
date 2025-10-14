import '../../core/services/api_service.dart';
import '../../core/constants/api_endpoints.dart';
import '../models/invoice_model.dart';

class InvoiceRepository {
  final ApiService _apiService;

  InvoiceRepository(this._apiService);

  Future<List<InvoiceModel>> getInvoices({
    String? status,
    String? type,
  }) async {
    final response = await _apiService.get(
      ApiEndpoints.invoices,
      queryParameters: {
        if (status != null) 'status': status,
        if (type != null) 'type': type,
      },
    );

    final data = response.data['data'];
    return (data as List)
        .map((json) => InvoiceModel.fromJson(json))
        .toList();
  }

  Future<InvoiceModel> getInvoiceDetail(int id) async {
    final response = await _apiService.get(
      ApiEndpoints.invoiceDetail(id),
    );

    return InvoiceModel.fromJson(response.data['data']);
  }

  Future<Map<String, dynamic>> initiatePayment({
    required int invoiceId,
    required String phoneNumber,
  }) async {
    final response = await _apiService.post(
      ApiEndpoints.payInvoice(invoiceId),
      data: {'phone_number': phoneNumber},
    );

    return response.data['data'];
  }
}