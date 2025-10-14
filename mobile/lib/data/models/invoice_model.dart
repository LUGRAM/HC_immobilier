class InvoiceModel {
  final int id;
  final String invoiceNumber;
  final String type;
  final String description;
  final double amount;
  final DateTime dueDate;
  final String status;
  final DateTime? paidAt;

  InvoiceModel({
    required this.id,
    required this.invoiceNumber,
    required this.type,
    required this.description,
    required this.amount,
    required this.dueDate,
    required this.status,
    this.paidAt,
  });

  bool get isPending => status == 'pending';
  bool get isPaid => status == 'paid';
  bool get isOverdue => status == 'overdue';

  String get typeLabel {
    switch (type) {
      case 'rent':
        return 'Loyer';
      case 'water':
        return 'Eau';
      case 'electricity':
        return 'Électricité';
      default:
        return 'Autre';
    }
  }

  factory InvoiceModel.fromJson(Map<String, dynamic> json) {
    return InvoiceModel(
      id: json['id'],
      invoiceNumber: json['invoice_number'],
      type: json['type'],
      description: json['description'],
      amount: double.parse(json['amount'].toString()),
      dueDate: DateTime.parse(json['due_date']),
      status: json['status'],
      paidAt: json['paid_at'] != null ? DateTime.parse(json['paid_at']) : null,
    );
  }
}