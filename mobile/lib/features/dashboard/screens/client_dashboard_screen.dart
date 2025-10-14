import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/constants/app_colors.dart';
import '../../../data/models/invoice_model.dart';
import '../../../data/providers/auth_provider.dart';
import '../../../data/models/appointment_model.dart';
import '../../../data/models/lease_model.dart';
import '../../../data/providers/dashboard_provider.dart';

class ClientDashboardScreen extends ConsumerWidget {
  const ClientDashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final dashboardAsync = ref.watch(clientDashboardProvider);
    final user = ref.watch(authStateProvider).user;

    return Scaffold(
      appBar: AppBar(
        title: Text('Bonjour ${user?.firstName ?? ""}'),
        actions: [
          IconButton(
            icon: const Icon(Icons.notifications_outlined),
            onPressed: () {
              Navigator.of(context).pushNamed('/notifications');
            },
          ),
        ],
      ),
      body: dashboardAsync.when(
        data: (data) {
          final hasActiveLease = data['has_active_lease'] as bool;
          
          if (!hasActiveLease) {
            return _buildNoLeaseView(context, data);
          }
          
          return _buildDashboardView(context, data);
        },
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, stack) => Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.error_outline, size: 60, color: AppColors.error),
              const SizedBox(height: 16),
              Text('Erreur: $error'),
              const SizedBox(height: 16),
              ElevatedButton(
                onPressed: () => ref.invalidate(clientDashboardProvider),
                child: const Text('Réessayer'),
              ),
            ],
          ),
        ),
      ),
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: 0,
        type: BottomNavigationBarType.fixed,
        selectedItemColor: AppColors.primary,
        items: const [
          BottomNavigationBarItem(
            icon: Icon(Icons.dashboard_outlined),
            label: 'Tableau de bord',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.receipt_long_outlined),
            label: 'Factures',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.account_balance_wallet_outlined),
            label: 'Dépenses',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.person_outline),
            label: 'Profil',
          ),
        ],
        onTap: (index) {
          switch (index) {
            case 1:
              Navigator.of(context).pushNamed('/invoices');
              break;
            case 2:
              Navigator.of(context).pushNamed('/expenses');
              break;
            case 3:
              Navigator.of(context).pushNamed('/profile');
              break;
          }
        },
      ),
    );
  }

  Widget _buildNoLeaseView(BuildContext context, Map<String, dynamic> data) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Card(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                children: [
                  Icon(
                    Icons.info_outline,
                    size: 60,
                    color: AppColors.warning,
                  ),
                  const SizedBox(height: 16),
                  const Text(
                    'Aucun bail actif',
                    style: TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'Vous devez d\'abord valider une visite pour accéder au tableau de bord complet',
                    textAlign: TextAlign.center,
                    style: TextStyle(color: AppColors.textSecondary),
                  ),
                  const SizedBox(height: 24),
                  ElevatedButton(
                    onPressed: () {
                      Navigator.of(context).pushNamed('/properties');
                    },
                    child: const Text('Explorer les biens'),
                  ),
                ],
              ),
            ),
          ),
          
          const SizedBox(height: 24),
          
          // Rendez-vous à venir
          const Text(
            'Vos rendez-vous',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
            ),
          ),
          
          const SizedBox(height: 12),
          
          if (data['upcoming_appointments'] != null &&
              (data['upcoming_appointments'] as List).isNotEmpty)
            ...(data['upcoming_appointments'] as List).map((appointment) {
              final apt = AppointmentModel.fromJson(appointment);
              return _buildAppointmentCard(context, apt);
            })
          else
            Card(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Center(
                  child: Text(
                    'Aucun rendez-vous prévu',
                    style: TextStyle(color: AppColors.textSecondary),
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildDashboardView(BuildContext context, Map<String, dynamic> data) {
    final lease = LeaseModel.fromJson(data['lease']);
    final pendingInvoices = (data['pending_invoices'] as List)
        .map((json) => InvoiceModel.fromJson(json))
        .toList();
    final overdueCount = data['overdue_invoices'] as int;
    final totalExpensesMonth = data['total_expenses_month'] as num;

    return RefreshIndicator(
      onRefresh: () async {
        // Rafraîchir les données
      },
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Informations du bail
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Container(
                          width: 80,
                          height: 80,
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(12),
                            color: AppColors.primary.withOpacity(0.1),
                          ),
                          child: lease.property?.primaryImageUrl != null
                              ? ClipRRect(
                                  borderRadius: BorderRadius.circular(12),
                                  child: Image.network(
                                    lease.property!.primaryImageUrl!,
                                    fit: BoxFit.cover,
                                  ),
                                )
                              : const Icon(
                                  Icons.home_outlined,
                                  size: 40,
                                  color: AppColors.primary,
                                ),
                        ),
                        const SizedBox(width: 16),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                'Mon logement',
                                style: TextStyle(
                                  color: AppColors.textSecondary,
                                  fontSize: 12,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                lease.property?.title ?? 'Bien loué',
                                style: const TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.bold,
                                ),
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                              ),
                              const SizedBox(height: 4),
                              Text(
                                '${lease.monthlyRent.toStringAsFixed(0)} FCFA/mois',
                                style: const TextStyle(
                                  color: AppColors.primary,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
            
            const SizedBox(height: 16),
            
            // Statistiques rapides
            Row(
              children: [
                Expanded(
                  child: _buildStatCard(
                    context,
                    'Factures en attente',
                    pendingInvoices.length.toString(),
                    Icons.receipt_long_outlined,
                    AppColors.warning,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: _buildStatCard(
                    context,
                    'En retard',
                    overdueCount.toString(),
                    Icons.warning_outlined,
                    AppColors.error,
                  ),
                ),
              ],
            ),
            
            const SizedBox(height: 12),
            
            Row(
              children: [
                Expanded(
                  child: _buildStatCard(
                    context,
                    'Dépenses ce mois',
                    '${totalExpensesMonth.toStringAsFixed(0)} FCFA',
                    Icons.account_balance_wallet_outlined,
                    AppColors.info,
                  ),
                ),
              ],
            ),
            
            const SizedBox(height: 24),
            
            // Factures en attente
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'Factures en attente',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                TextButton(
                  onPressed: () {
                    Navigator.of(context).pushNamed('/invoices');
                  },
                  child: const Text('Voir tout'),
                ),
              ],
            ),
            
            const SizedBox(height: 12),
            
            if (pendingInvoices.isEmpty)
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(24),
                  child: Center(
                    child: Text(
                      'Aucune facture en attente',
                      style: TextStyle(color: AppColors.textSecondary),
                    ),
                  ),
                ),
              )
            else
              ...pendingInvoices.take(3).map((invoice) {
                return _buildInvoiceCard(context, invoice);
              }),
          ],
        ),
      ),
    );
  }

  Widget _buildStatCard(
    BuildContext context,
    String label,
    String value,
    IconData icon,
    Color color,
  ) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: color.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Icon(icon, color: color, size: 20),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Text(
              label,
              style: const TextStyle(
                color: AppColors.textSecondary,
                fontSize: 12,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              value,
              style: const TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildInvoiceCard(BuildContext context, InvoiceModel invoice) {
    Color statusColor;
    switch (invoice.status) {
      case 'paid':
        statusColor = AppColors.success;
        break;
      case 'overdue':
        statusColor = AppColors.error;
        break;
      default:
        statusColor = AppColors.warning;
    }

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: () {
          Navigator.of(context).pushNamed(
            '/invoice-detail',
            arguments: invoice.id,
          );
        },
        borderRadius: BorderRadius.circular(16),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: statusColor.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(
                  Icons.receipt_outlined,
                  color: statusColor,
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      invoice.typeLabel,
                      style: const TextStyle(
                        fontWeight: FontWeight.w600,
                        fontSize: 16,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'Échéance: ${invoice.dueDate.day}/${invoice.dueDate.month}/${invoice.dueDate.year}',
                      style: TextStyle(
                        color: AppColors.textSecondary,
                        fontSize: 12,
                      ),
                    ),
                  ],
                ),
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(
                    '${invoice.amount.toStringAsFixed(0)} FCFA',
                    style: const TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 16,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 4,
                    ),
                    decoration: BoxDecoration(
                      color: statusColor.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Text(
                      invoice.status.toUpperCase(),
                      style: TextStyle(
                        color: statusColor,
                        fontSize: 10,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildAppointmentCard(BuildContext context, AppointmentModel appointment) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: AppColors.primary.withOpacity(0.1),
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Icon(
                Icons.event_outlined,
                color: AppColors.primary,
              ),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    appointment.property?.title ?? 'Visite',
                    style: const TextStyle(
                      fontWeight: FontWeight.w600,
                      fontSize: 16,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 4),
                  Text(
                    '${appointment.scheduledAt.day}/${appointment.scheduledAt.month} à ${appointment.scheduledAt.hour}h${appointment.scheduledAt.minute.toString().padLeft(2, '0')}',
                    style: const TextStyle(
                      color: AppColors.textSecondary,
                      fontSize: 12,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}