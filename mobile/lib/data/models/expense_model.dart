class ExpenseModel {
  final int id;
  final String category;
  final String description;
  final double amount;
  final DateTime expenseDate;
  final String? notes;

  ExpenseModel({
    required this.id,
    required this.category,
    required this.description,
    required this.amount,
    required this.expenseDate,
    this.notes,
  });

  String get categoryLabel {
    switch (category) {
      case 'food':
        return 'Nourriture';
      case 'transport':
        return 'Transport';
      case 'health':
        return 'Santé';
      case 'entertainment':
        return 'Loisirs';
      case 'shopping':
        return 'Shopping';
      default:
        return 'Autre';
    }
  }

  factory ExpenseModel.fromJson(Map<String, dynamic> json) {
    return ExpenseModel(
      id: json['id'],
      category: json['category'],
      description: json['description'],
      amount: double.parse(json['amount'].toString()),
      expenseDate: DateTime.parse(json['expense_date']),
      notes: json['notes'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'category': category,
      'description': description,
      'amount': amount,
      'expense_date': expenseDate.toIso8601String(),
      'notes': notes,
    };
  }
}