import 'property_model.dart';
import 'user_model.dart';

class AppointmentModel {
  final int id;
  final int propertyId;
  final int userId;
  final DateTime date;
  final String time;
  final String status; // pending, confirmed, completed, cancelled
  final String paymentStatus; // pending, paid, failed
  final double? amount;
  final String? notes;
  final PropertyModel? property;
  final UserModel? user;
  final DateTime createdAt;
  final DateTime updatedAt;

  AppointmentModel({
    required this.id,
    required this.propertyId,
    required this.userId,
    required this.date,
    required this.time,
    required this.status,
    required this.paymentStatus,
    this.amount,
    this.notes,
    this.property,
    this.user,
    required this.createdAt,
    required this.updatedAt,
  });

  // Getters utiles
  bool get isPending => status == 'pending';
  bool get isConfirmed => status == 'confirmed';
  bool get isCompleted => status == 'completed';
  bool get isCancelled => status == 'cancelled';
  bool get isValidated => status == 'validated';

  bool get isPaid => paymentStatus == 'paid';
  bool get isPaymentPending => paymentStatus == 'pending';

  String get statusLabel {
    switch (status) {
      case 'pending':
        return 'En attente';
      case 'confirmed':
        return 'Confirmé';
      case 'completed':
        return 'Terminé';
      case 'cancelled':
        return 'Annulé';
      case 'validated':
        return 'Validé';
      default:
        return status;
    }
  }

  String get paymentStatusLabel {
    switch (paymentStatus) {
      case 'pending':
        return 'En attente';
      case 'paid':
        return 'Payé';
      case 'failed':
        return 'Échoué';
      default:
        return paymentStatus;
    }
  }

  DateTime get dateTime {
    final timeParts = time.split(':');
    return DateTime(
      date.year,
      date.month,
      date.day,
      int.parse(timeParts[0]),
      int.parse(timeParts[1]),
    );
  }

  // Factory depuis JSON
  factory AppointmentModel.fromJson(Map<String, dynamic> json) {
    return AppointmentModel(
      id: json['id'] as int,
      propertyId: json['property_id'] as int,
      userId: json['user_id'] as int,
      date: DateTime.parse(json['date'] as String),
      time: json['time'] as String,
      status: json['status'] as String,
      paymentStatus: json['payment_status'] as String? ?? 'pending',
      amount: (json['amount'] as num?)?.toDouble(),
      notes: json['notes'] as String?,
      property: json['property'] != null
          ? PropertyModel.fromJson(json['property'] as Map<String, dynamic>)
          : null,
      user: json['user'] != null
          ? UserModel.fromJson(json['user'] as Map<String, dynamic>)
          : null,
      createdAt: DateTime.parse(json['created_at'] as String),
      updatedAt: DateTime.parse(json['updated_at'] as String),
    );
  }

  // Convertir en JSON
  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'property_id': propertyId,
      'user_id': userId,
      'date': date.toIso8601String().split('T')[0],
      'time': time,
      'status': status,
      'payment_status': paymentStatus,
      'amount': amount,
      'notes': notes,
      'created_at': createdAt.toIso8601String(),
      'updated_at': updatedAt.toIso8601String(),
    };
  }

  // CopyWith
  AppointmentModel copyWith({
    int? id,
    int? propertyId,
    int? userId,
    DateTime? date,
    String? time,
    String? status,
    String? paymentStatus,
    double? amount,
    String? notes,
    PropertyModel? property,
    UserModel? user,
    DateTime? createdAt,
    DateTime? updatedAt,
  }) {
    return AppointmentModel(
      id: id ?? this.id,
      propertyId: propertyId ?? this.propertyId,
      userId: userId ?? this.userId,
      date: date ?? this.date,
      time: time ?? this.time,
      status: status ?? this.status,
      paymentStatus: paymentStatus ?? this.paymentStatus,
      amount: amount ?? this.amount,
      notes: notes ?? this.notes,
      property: property ?? this.property,
      user: user ?? this.user,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
    );
  }

  @override
  String toString() {
    return 'AppointmentModel(id: $id, date: $date, time: $time, status: $status)';
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is AppointmentModel && other.id == id;
  }

  @override
  int get hashCode => id.hashCode;
}