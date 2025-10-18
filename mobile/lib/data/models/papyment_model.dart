import 'package:hive/hive.dart';
import 'package:json_annotation/json_annotation.dart';

part 'papyment_model.g.dart';
/// Modèle pour les paiements
/// Utilisé pour les paiements de loyer, rendez-vous, etc.
@HiveType(typeId: 4) // TypeId unique pour Hive
@JsonSerializable()
class PaymentModel {
  @HiveField(0)
  final int id;

  @HiveField(1)
  @JsonKey(name: 'invoice_id')
  final int? invoiceId;

  @HiveField(2)
  @JsonKey(name: 'appointment_id')
  final int? appointmentId;

  @HiveField(3)
  @JsonKey(name: 'user_id')
  final int userId;

  @HiveField(4)
  final double amount;

  @HiveField(5)
  @JsonKey(name: 'payment_method')
  final String paymentMethod; // mobile_money, bank_transfer, cash, etc.

  @HiveField(6)
  @JsonKey(name: 'payment_provider')
  final String? paymentProvider; // cinetpay, orange_money, mtn, moov, etc.

  @HiveField(7)
  final String status; // pending, paid, failed, refunded

  @HiveField(8)
  @JsonKey(name: 'transaction_id')
  final String? transactionId;

  @HiveField(9)
  @JsonKey(name: 'payment_token')
  final String? paymentToken;

  @HiveField(10)
  @JsonKey(name: 'payment_url')
  final String? paymentUrl;

  @HiveField(11)
  @JsonKey(name: 'phone_number')
  final String? phoneNumber;

  @HiveField(12)
  final String? reference;

  @HiveField(13)
  final String? notes;

  @HiveField(14)
  @JsonKey(name: 'paid_at')
  final DateTime? paidAt;

  @HiveField(15)
  @JsonKey(name: 'created_at')
  final DateTime createdAt;

  @HiveField(16)
  @JsonKey(name: 'updated_at')
  final DateTime updatedAt;

  PaymentModel({
    required this.id,
    this.invoiceId,
    this.appointmentId,
    required this.userId,
    required this.amount,
    required this.paymentMethod,
    this.paymentProvider,
    required this.status,
    this.transactionId,
    this.paymentToken,
    this.paymentUrl,
    this.phoneNumber,
    this.reference,
    this.notes,
    this.paidAt,
    required this.createdAt,
    required this.updatedAt,
  });

  // ==================== JSON SERIALIZATION ====================

  factory PaymentModel.fromJson(Map<String, dynamic> json) =>
      _$PaymentModelFromJson(json);

  Map<String, dynamic> toJson() => _$PaymentModelToJson(this);

  // ==================== HELPERS ====================

  /// Vérifie si le paiement est en attente
  bool get isPending => status.toLowerCase() == 'pending';

  /// Vérifie si le paiement est payé
  bool get isPaid => status.toLowerCase() == 'paid';

  /// Vérifie si le paiement a échoué
  bool get isFailed => status.toLowerCase() == 'failed';

  /// Vérifie si le paiement a été remboursé
  bool get isRefunded => status.toLowerCase() == 'refunded';

  /// Retourne le type de paiement (facture ou rendez-vous)
  String get paymentType {
    if (invoiceId != null) return 'invoice';
    if (appointmentId != null) return 'appointment';
    return 'other';
  }

  /// Retourne le libellé du moyen de paiement
  String get paymentMethodLabel {
    switch (paymentMethod.toLowerCase()) {
      case 'mobile_money':
        return 'Mobile Money';
      case 'bank_transfer':
        return 'Virement bancaire';
      case 'cash':
        return 'Espèces';
      case 'check':
        return 'Chèque';
      default:
        return paymentMethod;
    }
  }

  /// Retourne le libellé du statut
  String get statusLabel {
    switch (status.toLowerCase()) {
      case 'pending':
        return 'En attente';
      case 'paid':
        return 'Payé';
      case 'failed':
        return 'Échoué';
      case 'refunded':
        return 'Remboursé';
      case 'cancelled':
        return 'Annulé';
      default:
        return status;
    }
  }

  /// Retourne la couleur associée au statut
  String get statusColor {
    switch (status.toLowerCase()) {
      case 'paid':
        return '#10B981'; // Green
      case 'pending':
        return '#F59E0B'; // Orange
      case 'failed':
        return '#EF4444'; // Red
      case 'refunded':
        return '#6B7280'; // Gray
      case 'cancelled':
        return '#9CA3AF'; // Light gray
      default:
        return '#6B7280';
    }
  }

  // ==================== COPY WITH ====================

  PaymentModel copyWith({
    int? id,
    int? invoiceId,
    int? appointmentId,
    int? userId,
    double? amount,
    String? paymentMethod,
    String? paymentProvider,
    String? status,
    String? transactionId,
    String? paymentToken,
    String? paymentUrl,
    String? phoneNumber,
    String? reference,
    String? notes,
    DateTime? paidAt,
    DateTime? createdAt,
    DateTime? updatedAt,
  }) {
    return PaymentModel(
      id: id ?? this.id,
      invoiceId: invoiceId ?? this.invoiceId,
      appointmentId: appointmentId ?? this.appointmentId,
      userId: userId ?? this.userId,
      amount: amount ?? this.amount,
      paymentMethod: paymentMethod ?? this.paymentMethod,
      paymentProvider: paymentProvider ?? this.paymentProvider,
      status: status ?? this.status,
      transactionId: transactionId ?? this.transactionId,
      paymentToken: paymentToken ?? this.paymentToken,
      paymentUrl: paymentUrl ?? this.paymentUrl,
      phoneNumber: phoneNumber ?? this.phoneNumber,
      reference: reference ?? this.reference,
      notes: notes ?? this.notes,
      paidAt: paidAt ?? this.paidAt,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
    );
  }

  @override
  String toString() {
    return 'PaymentModel(id: $id, amount: $amount, status: $status, paymentMethod: $paymentMethod)';
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;

    return other is PaymentModel && other.id == id;
  }

  @override
  int get hashCode => id.hashCode;
}

/// Extension pour les listes de paiements
extension PaymentListExtension on List<PaymentModel> {
  /// Filtre les paiements payés
  List<PaymentModel> get paid =>
      where((payment) => payment.isPaid).toList();

  /// Filtre les paiements en attente
  List<PaymentModel> get pending =>
      where((payment) => payment.isPending).toList();

  /// Filtre les paiements échoués
  List<PaymentModel> get failed =>
      where((payment) => payment.isFailed).toList();

  /// Calcule le montant total
  double get totalAmount =>
      fold(0.0, (sum, payment) => sum + payment.amount);

  /// Calcule le montant total payé
  double get totalPaidAmount =>
      paid.fold(0.0, (sum, payment) => sum + payment.amount);

  /// Calcule le montant total en attente
  double get totalPendingAmount =>
      pending.fold(0.0, (sum, payment) => sum + payment.amount);

  /// Groupe les paiements par mois
  Map<String, List<PaymentModel>> groupByMonth() {
    final Map<String, List<PaymentModel>> grouped = {};

    for (final payment in this) {
      final monthKey =
          '${payment.createdAt.year}-${payment.createdAt.month.toString().padLeft(2, '0')}';

      if (!grouped.containsKey(monthKey)) {
        grouped[monthKey] = [];
      }
      grouped[monthKey]!.add(payment);
    }

    return grouped;
  }

  /// Trie les paiements par date (plus récent en premier)
  List<PaymentModel> sortByDate() {
    final sorted = List<PaymentModel>.from(this);
    sorted.sort((a, b) => b.createdAt.compareTo(a.createdAt));
    return sorted;
  }
}