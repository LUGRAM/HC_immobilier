import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fl_chart/fl_chart.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/utils/formatters.dart';
import '../../../data/providers/expense_provider.dart';
import '../../../data/models/expense_model.dart';

/// Écran de statistiques et visualisations des dépenses
class ExpenseStatisticsScreen extends ConsumerStatefulWidget {
  const ExpenseStatisticsScreen({super.key});

  @override
  ConsumerState<ExpenseStatisticsScreen> createState() =>
      _ExpenseStatisticsScreenState();
}

class _ExpenseStatisticsScreenState
    extends ConsumerState<ExpenseStatisticsScreen> {
  String _selectedPeriod = 'month';

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
        title: const Text('Statistiques'),
        backgroundColor: Colors.white,
        foregroundColor: AppColors.textPrimary,
        elevation: 0,
      ),
      body: expensesAsync.when(
        data: (expenses) => _buildStatistics(expenses),
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, stack) => Center(child: Text('Erreur: $error')),
      ),
    );
  }

  Widget _buildStatistics(List<ExpenseModel> allExpenses) {
    final expenses = _filterByPeriod(allExpenses);
    final total = expenses.fold<double>(0, (sum, e) => sum + e.amount);
    final categoryTotals = _calculateCategoryTotals(expenses);
    final dailyAverages = _calculateDailyAverages(expenses);

    return SingleChildScrollView(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Sélecteur de période
          Container(
            color: Colors.white,
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                _buildPeriodButton('Semaine', 'week'),
                const SizedBox(width: 8),
                _buildPeriodButton('Mois', 'month'),
                const SizedBox(width: 8),
                _buildPeriodButton('Année', 'year'),
              ],
            ),
          ),

          const SizedBox(height: 8),

          // Résumé global
          Container(
            color: Colors.white,
            padding: const EdgeInsets.all(24),
            child: Column(
              children: [
                const Text(
                  'Total des dépenses',
                  style: TextStyle(
                    fontSize: 14,
                    color: AppColors.textSecondary,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  Formatters.currency(total),
                  style: TextStyle(
                    fontSize: 36,
                    fontWeight: FontWeight.bold,
                    color: AppColors.primary,
                  ),
                ),
                const SizedBox(height: 24),
                Row(
                  children: [
                    Expanded(
                      child: _buildStatCard(
                        'Nombre',
                        '${expenses.length}',
                        Icons.receipt_long,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: _buildStatCard(
                        'Moyenne/jour',
                        Formatters.compactCurrency(
                          dailyAverages['average'] ?? 0,
                        ),
                        Icons.trending_up,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),

          const SizedBox(height: 8),

          // Graphique en camembert par catégorie
          if (categoryTotals.isNotEmpty) ...[
            Container(
              color: Colors.white,
              padding: const EdgeInsets.all(24),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Répartition par catégorie',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: AppColors.textPrimary,
                    ),
                  ),
                  const SizedBox(height: 24),
                  SizedBox(
                    height: 250,
                    child: PieChart(
                      PieChartData(
                        sections: _buildPieChartSections(categoryTotals, total),
                        centerSpaceRadius: 60,
                        sectionsSpace: 2,
                        borderData: FlBorderData(show: false),
                      ),
                    ),
                  ),
                  const SizedBox(height: 24),
                  _buildCategoryLegend(categoryTotals, total),
                ],
              ),
            ),
            const SizedBox(height: 8),
          ],

          // Top catégories
          Container(
            color: Colors.white,
            padding: const EdgeInsets.all(24),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Top catégories',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 16),
                ..._buildTopCategories(categoryTotals, total),
              ],
            ),
          ),

          const SizedBox(height: 24),
        ],
      ),
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

  Widget _buildStatCard(String label, String value, IconData icon) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.grey.shade50,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 24, color: AppColors.primary),
          const SizedBox(height: 12),
          Text(
            value,
            style: const TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: AppColors.textPrimary,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            label,
            style: TextStyle(
              fontSize: 12,
              color: AppColors.textSecondary,
            ),
          ),
        ],
      ),
    );
  }

  List<PieChartSectionData> _buildPieChartSections(
    Map<String, double> categoryTotals,
    double total,
  ) {
    return categoryTotals.entries.map((entry) {
      final percentage = (entry.value / total) * 100;
      final color = _categoryColors[entry.key] ?? Colors.grey;

      return PieChartSectionData(
        value: entry.value,
        title: '${percentage.toStringAsFixed(0)}%',
        color: color,
        radius: 70,
        titleStyle: const TextStyle(
          fontSize: 14,
          fontWeight: FontWeight.bold,
          color: Colors.white,
        ),
      );
    }).toList();
  }

  Widget _buildCategoryLegend(
    Map<String, double> categoryTotals,
    double total,
  ) {
    return Wrap(
      spacing: 8,
      runSpacing: 8,
      children: categoryTotals.entries.map((entry) {
        final color = _categoryColors[entry.key] ?? Colors.grey;

        return Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
          decoration: BoxDecoration(
            color: color.withOpacity(0.1),
            borderRadius: BorderRadius.circular(20),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 12,
                height: 12,
                decoration: BoxDecoration(
                  color: color,
                  shape: BoxShape.circle,
                ),
              ),
              const SizedBox(width: 6),
              Text(
                Formatters.capitalize(entry.key),
                style: const TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
        );
      }).toList(),
    );
  }

  List<Widget> _buildTopCategories(
    Map<String, double> categoryTotals,
    double total,
  ) {
    final sorted = categoryTotals.entries.toList()
      ..sort((a, b) => b.value.compareTo(a.value));

    return sorted.take(5).map((entry) {
      final color = _categoryColors[entry.key] ?? Colors.grey;
      final percentage = (entry.value / total) * 100;

      return Padding(
        padding: const EdgeInsets.only(bottom: 16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  Formatters.capitalize(entry.key),
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w500,
                  ),
                ),
                Text(
                  Formatters.currency(entry.value),
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Stack(
              children: [
                Container(
                  height: 8,
                  decoration: BoxDecoration(
                    color: Colors.grey.shade200,
                    borderRadius: BorderRadius.circular(4),
                  ),
                ),
                FractionallySizedBox(
                  widthFactor: percentage / 100,
                  child: Container(
                    height: 8,
                    decoration: BoxDecoration(
                      color: color,
                      borderRadius: BorderRadius.circular(4),
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      );
    }).toList();
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

  Map<String, double> _calculateCategoryTotals(List<ExpenseModel> expenses) {
    final Map<String, double> totals = {};

    for (final expense in expenses) {
      totals[expense.category] = (totals[expense.category] ?? 0) + expense.amount;
    }

    return totals;
  }

  Map<String, double> _calculateDailyAverages(List<ExpenseModel> expenses) {
    if (expenses.isEmpty) return {'average': 0, 'days': 0};

    final now = DateTime.now();
    int days;

    switch (_selectedPeriod) {
      case 'week':
        days = 7;
        break;
      case 'month':
        days = DateTime(now.year, now.month + 1, 0).day;
        break;
      case 'year':
        days = 365;
        break;
      default:
        days = 30;
    }

    final total = expenses.fold<double>(0, (sum, e) => sum + e.amount);
    return {'average': total / days, 'days': days.toDouble()};
  }
}