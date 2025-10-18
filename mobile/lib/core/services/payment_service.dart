import 'package:dio/dio.dart';
import '../constants/api_endpoints.dart';
import 'api_service.dart';

class PaymentService {
  final ApiService _apiService;

  PaymentService(this._apiService);

  /// Initier un paiement pour un rendez-vous
  Future<Map<String, dynamic>> initiateAppointmentPayment({
    required int appointmentId,
    required String phoneNumber,
  }) async {
    try {
      final response = await _apiService.post(
        ApiEndpoints.initiatePayment,
        data: {
          'appointment_id': appointmentId,
          'phone_number': phoneNumber,
          'payment_method': 'mobile_money',
        },
      );

      if (response.statusCode == 200 || response.statusCode == 201) {
        return {
          'success': true,
          'payment_url': response.data['data']['payment_url'],
          'transaction_id': response.data['data']['transaction_id'],
          'payment_token': response.data['data']['payment_token'],
        };
      } else {
        throw Exception('Erreur lors de l\'initiation du paiement');
      }
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  /// Initier un paiement pour une facture
  Future<Map<String, dynamic>> initiateInvoicePayment({
    required int invoiceId,
    required String phoneNumber,
  }) async {
    try {
      final response = await _apiService.post(
        ApiEndpoints.initiateInvoicePayment,
        data: {
          'invoice_id': invoiceId,
          'phone_number': phoneNumber,
          'payment_method': 'mobile_money',
        },
      );

      if (response.statusCode == 200 || response.statusCode == 201) {
        return {
          'success': true,
          'payment_url': response.data['data']['payment_url'],
          'transaction_id': response.data['data']['transaction_id'],
          'payment_token': response.data['data']['payment_token'],
        };
      } else {
        throw Exception('Erreur lors de l\'initiation du paiement');
      }
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  /// Vérifier le statut d'un paiement
  Future<Map<String, dynamic>> checkPaymentStatus(String transactionId) async {
    try {
      final response = await _apiService.get(
        '${ApiEndpoints.checkPaymentStatus}/$transactionId',
      );

      if (response.statusCode == 200) {
        return {
          'status': response.data['data']['status'],
          'amount': response.data['data']['amount'],
          'payment_method': response.data['data']['method'],
          'paid_at': response.data['data']['paid_at'],
        };
      } else {
        throw Exception('Erreur lors de la vérification du paiement');
      }
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  /// Obtenir l'historique des paiements
  Future<List<Map<String, dynamic>>> getPaymentHistory({
    int page = 1,
    int perPage = 20,
  }) async {
    try {
      final response = await _apiService.get(
        ApiEndpoints.paymentHistory,
        queryParameters: {
          'page': page,
          'per_page': perPage,
        },
      );

      if (response.statusCode == 200) {
        final List<dynamic> payments = response.data['data'];
        return payments.cast<Map<String, dynamic>>();
      } else {
        throw Exception('Erreur lors du chargement de l\'historique');
      }
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  /// Récupérer les paramètres de paiement (montant visite, etc.)
  Future<Map<String, dynamic>> getPaymentSettings() async {
    try {
      final response = await _apiService.get(ApiEndpoints.settings);

      if (response.statusCode == 200) {
        return {
          'visit_price': response.data['data']['visit_price'],
          'currency': response.data['data']['currency'],
        };
      } else {
        throw Exception('Erreur lors du chargement des paramètres');
      }
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  String _handleError(DioException e) {
    if (e.response != null) {
      final data = e.response!.data;
      if (data is Map && data.containsKey('message')) {
        return data['message'];
      }
      return 'Erreur: ${e.response!.statusCode}';
    } else if (e.type == DioExceptionType.connectionTimeout) {
      return 'Délai de connexion dépassé';
    } else if (e.type == DioExceptionType.receiveTimeout) {
      return 'Délai de réception dépassé';
    } else if (e.type == DioExceptionType.connectionError) {
      return 'Erreur de connexion. Vérifiez votre internet.';
    }
    return 'Une erreur est survenue';
  }
}