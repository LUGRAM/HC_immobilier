import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/utils/formatters.dart';
import '../../../data/providers/expense_provider.dart';
import '../../../data/models/expense_model.dart';
import 'add_expense_screen.dart';
import 'expense_statistics_screen.dart';

/// Écran de liste des dépenses quotidiennes
class ExpenseListScreen extends ConsumerStatefulWidget {
  const ExpenseListScreen({super.key});

  @override
  ConsumerState<ExpenseListScreen> createState() => _ExpenseListScreenState();
}

class _ExpenseListScreenState extends ConsumerState<ExpenseListScreen> {
  String _selectedPeriod = 'month'; // week, month, year
  String? _selectedCategory;

  final Map<String, IconData> _categoryIcons = {
    'nourriture': Icons.restaurant,
    'transport': Icons.directions_car,
    'logement': Icons.home,
    'sante': Icons.local_hospital,
    'loisirs': Icons.sports_esports,
    'education': Icons.school,
    'vetements': Icons.checkroom,
    'autre': Icons.more_horiz,
  };

  final Map<String, Color> _categoryColors = {
    'nourriture': Colors.orange,
    'transport': Colors.blue,
    'logement': Colors.green,
    'sante': Colors.red,
    'loisirs': Colors.purple,
    'education': Colors.indigo,
    'vetements': Colors.pink,
    'autre': Colors.grey,
  };

  @override
  Widget build(BuildContext context) {
    final expensesAsync = ref.watch(expenseProvider);

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Mes dépenses'),
        backgroundColor: Colors.white,
        foregroundColor: AppColors.textPrimary,
        elevation: 0,
        actions: [
          IconButton(
            icon: const Icon(Icons.bar_chart),
            onPressed: () {
              Navigator.of(context).push(
                MaterialPageRoute(
                  builder: (context) => const ExpenseStatisticsScreen(),
                ),
              );
            },
          ),
        ],
      ),
      body: expensesAsync.when(
        data: (expenses) => _buildExpensesList(expenses),
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, stack) => Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.error_outline, size: 64, color: Colors.red),
              const SizedBox(height: 16),
              Text('Erreur: $error'),
              const SizedBox(height: 16),
              ElevatedButton(
                onPressed: () => ref.refresh(expenseProvider),
                child: const Text('Réessayer'),
              ),
            ],
          ),
        ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () async {
          final result = await Navigator.of(context).push(
            MaterialPageRoute(
              builder: (context) => const AddExpenseScreen(),
            ),
          );

          if (result == true) {
            ref.refresh(expenseProvider);
          }
        },
        backgroundColor: AppColors.primary,
        icon: const Icon(Icons.add),
        label: const Text('Ajouter'),
      ),
    );
  }

  Widget _buildExpensesList(List<ExpenseModel> allExpenses) {
    // Filtrer par période
    final filteredExpenses = _filterByPeriod(allExpenses);

    // Filtrer par catégorie si sélectionnée
    final expenses = _selectedCategory != null
        ? filteredExpenses.where((e) => e.category == _selectedCategory).toList()
        : filteredExpenses;

    // Calculer le total
    final total = expenses.fold<double>(0, (sum, e) => sum + e.amount);

    // Grouper par jour
    final groupedByDay = _groupByDay(expenses);

    return Column(
      children: [
        // En-tête avec résumé
        Container(
          color: Colors.white,
          padding: const EdgeInsets.all(24),
          child: Column(
            children: [
              // Sélecteur de période
              _buildPeriodSelector(),
              const SizedBox(height: 24),

              // Total
              Column(
                children: [
                  const Text(
                    'Total dépensé',
                    style: TextStyle(
                      fontSize: 14,
                      color: AppColors.textSecondary,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    Formatters.currency(total),
                    style: TextStyle(
                      fontSize: 32,
                      fontWeight: FontWeight.bold,
                      color: AppColors.primary,
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 24),

              // Filtres par catégorie
              _buildCategoryFilters(),
            ],
          ),
        ),

        const SizedBox(height: 8),

        // Liste des dépenses groupées par jour
        Expanded(
          child: expenses.isEmpty
              ? _buildEmptyState()
              : RefreshIndicator(
                  onRefresh: () async {
                    ref.refresh(expenseProvider);
                  },
                  child: ListView.builder(
                    padding: const EdgeInsets.only(bottom: 80),
                    itemCount: groupedByDay.length,
                    itemBuilder: (context, index) {
                      final date = groupedByDay.keys.elementAt(index);
                      final dayExpenses = groupedByDay[date]!;
                      final dayTotal = dayExpenses.fold<double>(
                        0,
                        (sum, e) => sum + e.amount,
                      );

                      return _buildDayGroup(date, dayExpenses, dayTotal);
                    },
                  ),
                ),
        ),
      ],
    );
  }

  Widget _buildPeriodSelector() {
    return Row(
      children: [
        _buildPeriodButton('Semaine', 'week'),
        const SizedBox(width: 8),
        _buildPeriodButton('Mois', 'month'),
        const SizedBox(width: 8),
        _buildPeriodButton('Année', 'year'),
      ],
    );
  }

  Widget _buildPeriodButton(String label, String value) {
    final isSelected = _selectedPeriod == value;

    return Expanded(
      child: GestureDetector(
        onTap: () {
          setState(() {
            _selectedPeriod = value;
          });
        },
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 12),
          decoration: BoxDecoration(
            color: isSelected ? AppColors.primary : Colors.grey.shade100,
            borderRadius: BorderRadius.circular(12),
          ),
          child: Text(
            label,
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w600,
              color: isSelected ? Colors.white : AppColors.textSecondary,
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildCategoryFilters() {
    return SizedBox(
      height: 40,
      child: ListView(
        scrollDirection: Axis.horizontal,
        children: [
          _buildCategoryChip('Toutes', null),
          ..._categoryIcons.keys.map((category) {
            return _buildCategoryChip(
              Formatters.capitalize(category),
              category,
            );
          }),
        ],
      ),
    );
  }

  Widget _buildCategoryChip(String label, String? category) {
    final isSelected = _selectedCategory == category;

    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: FilterChip(
        label: Text(label),
        selected: isSelected,
        onSelected: (selected) {
          setState(() {
            _selectedCategory = selected ? category : null;
          });
        },
        backgroundColor: Colors.grey.shade100,
        selectedColor: AppColors.primary.withOpacity(0.2),
        checkmarkColor: AppColors.primary,
        labelStyle: TextStyle(
          color: isSelected ? AppColors.primary : AppColors.textSecondary,
          fontWeight: isSelected ? FontWeight.w600 : FontWeight.normal,
        ),
      ),
    );
  }

  Widget _buildDayGroup(DateTime date, List<ExpenseModel> expenses, double total) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      color: Colors.white,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // En-tête du jour
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                Text(
                  Formatters.relativeDate(date),
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w600,
                    color: AppColors.textPrimary,
                  ),
                ),
                const Spacer(),
                Text(
                  Formatters.currency(total),
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: AppColors.primary,
                  ),
                ),
              ],
            ),
          ),

          // Liste des dépenses du jour
          ...expenses.map((expense) => _buildExpenseItem(expense)),
        ],
      ),
    );
  }

  Widget _buildExpenseItem(ExpenseModel expense) {
    final icon = _categoryIcons[expense.category] ?? Icons.category;
    final color = _categoryColors[expense.category] ?? Colors.grey;

    return Dismissible(
      key: Key(expense.id.toString()),
      direction: DismissDirection.endToStart,
      background: Container(
        color: Colors.red,
        alignment: Alignment.centerRight,
        padding: const EdgeInsets.only(right: 20),
        child: const Icon(Icons.delete, color: Colors.white),
      ),
      confirmDismiss: (direction) async {
        return await showDialog(
          context: context,
          builder: (context) => AlertDialog(
            title: const Text('Supprimer la dépense'),
            content: const Text('Voulez-vous vraiment supprimer cette dépense ?'),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(context, false),
                child: const Text('Annuler'),
              ),
              TextButton(
                onPressed: () => Navigator.pop(context, true),
                child: const Text('Supprimer', style: TextStyle(color: Colors.red)),
              ),
            ],
          ),
        );
      },
      onDismissed: (direction) async {
        await ref.read(expenseProvider.notifier).deleteExpense(expense.id);
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Dépense supprimée')),
          );
        }
      },
      child: ListTile(
        leading: Container(
          width: 48,
          height: 48,
          decoration: BoxDecoration(
            color: color.withOpacity(0.1),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Icon(icon, color: color, size: 24),
        ),
        title: Text(
          expense.description ?? Formatters.capitalize(expense.category),
          style: const TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.w500,
          ),
        ),
        subtitle: Text(
          Formatters.time(expense.date),
          style: TextStyle(
            fontSize: 13,
            color: AppColors.textSecondary,
          ),
        ),
        trailing: Text(
          Formatters.currency(expense.amount),
          style: const TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.bold,
            color: AppColors.textPrimary,
          ),
        ),
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(
            Icons.receipt_long_outlined,
            size: 80,
            color: Colors.grey.shade300,
          ),
          const SizedBox(height: 16),
          const Text(
            'Aucune dépense',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.w600,
              color: AppColors.textSecondary,
            ),
          ),
          const SizedBox(height: 8),
          const Text(
            'Commencez à suivre vos dépenses',
            style: TextStyle(
              fontSize: 14,
              color: AppColors.textSecondary,
            ),
          ),
        ],
      ),
    );
  }

  List<ExpenseModel> _filterByPeriod(List<ExpenseModel> expenses) {
    final now = DateTime.now();

    switch (_selectedPeriod) {
      case 'week':
        final startOfWeek = now.subtract(Duration(days: now.weekday - 1));
        return expenses.where((e) => e.date.isAfter(startOfWeek)).toList();

      case 'month':
        final startOfMonth = DateTime(now.year, now.month, 1);
        return expenses.where((e) => e.date.isAfter(startOfMonth)).toList();

      case 'year':
        final startOfYear = DateTime(now.year, 1, 1);
        return expenses.where((e) => e.date.isAfter(startOfYear)).toList();

      default:
        return expenses;
    }
  }

  Map<DateTime, List<ExpenseModel>> _groupByDay(List<ExpenseModel> expenses) {
    final Map<DateTime, List<ExpenseModel>> grouped = {};

    for (final expense in expenses) {
      final date = DateTime(
        expense.date.year,
        expense.date.month,
        expense.date.day,
      );

      if (!grouped.containsKey(date)) {
        grouped[date] = [];
      }
      grouped[date]!.add(expense);
    }

    // Trier par date décroissante
    final sortedKeys = grouped.keys.toList()..sort((a, b) => b.compareTo(a));
    return {for (var key in sortedKeys) key: grouped[key]!};
  }
}