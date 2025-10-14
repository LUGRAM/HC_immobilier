import 'property_model.dart';

class LeaseModel {
  final int id;
  final int propertyId;
  final DateTime startDate;
  final DateTime? endDate;
  final double monthlyRent;
  final String status;
  final PropertyModel? property;

  LeaseModel({
    required this.id,
    required this.propertyId,
    required this.startDate,
    this.endDate,
    required this.monthlyRent,
    required this.status,
    this.property,
  });

  bool get isActive => status == 'active';
  bool get isPendingApproval => status == 'pending_approval';

  factory LeaseModel.fromJson(Map<String, dynamic> json) {
    return LeaseModel(
      id: json['id'],
      propertyId: json['property_id'],
      startDate: DateTime.parse(json['start_date']),
      endDate: json['end_date'] != null ? DateTime.parse(json['end_date']) : null,
      monthlyRent: double.parse(json['monthly_rent'].toString()),
      status: json['status'],
      property: json['property'] != null 
          ? PropertyModel.fromJson(json['property'])
          : null,
    );
  }
}