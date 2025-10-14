import 'package:hc/data/models/property_model.dart';

class AppointmentModel {
  final int id;
  final int propertyId;
  final DateTime scheduledAt;
  final String status;
  final double amountPaid;
  final String? paymentReference;
  final PropertyModel? property;

  AppointmentModel({
    required this.id,
    required this.propertyId,
    required this.scheduledAt,
    required this.status,
    required this.amountPaid,
    this.paymentReference,
    this.property,
  });

  bool get isPaid => status == 'paid' || status == 'confirmed' || status == 'completed';
  bool get isCompleted => status == 'completed';
  bool get canCreateLease => isCompleted;

  factory AppointmentModel.fromJson(Map<String, dynamic> json) {
    return AppointmentModel(
      id: json['id'],
      propertyId: json['property_id'],
      scheduledAt: DateTime.parse(json['scheduled_at']),
      status: json['status'],
      amountPaid: double.parse(json['amount_paid'].toString()),
      paymentReference: json['payment_reference'],
      property: json['property'] != null 
          ? PropertyModel.fromJson(json['property'])
          : null,
    );
  }
}
