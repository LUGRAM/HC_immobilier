//appointments/screens/appointment_booking_screen.dart

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/services/payment_service.dart';
import '../../../../data/models/property_model.dart';
import '../../../../data/providers/appointment_provider.dart';
import '../../../../data/providers/settings_provider.dart';
import '../../../payments/screens/payment_webview_screen.dart';
import '../appointment_detail_screen.dart';

class AppointmentBookingScreen extends ConsumerStatefulWidget {
  final PropertyModel property;

  const AppointmentBookingScreen({
    super.key,
    required this.property,
  });

  @override
  ConsumerState<AppointmentBookingScreen> createState() =>
      _AppointmentBookingScreenState();
}

class _AppointmentBookingScreenState
    extends ConsumerState<AppointmentBookingScreen> {
  DateTime? _selectedDate;
  TimeSlot? _selectedTimeSlot;
  String _phoneNumber = '';
  final _phoneController = TextEditingController();
  final _formKey = GlobalKey<FormState>();
  bool _isLoading = false;

  @override
  void dispose() {
    _phoneController.dispose();
    super.dispose();
  }

  Future<void> _selectDate() async {
    final now = DateTime.now();
    final firstDate = now;
    final lastDate = now.add(const Duration(days: 60));

    final picked = await showDatePicker(
      context: context,
      initialDate: _selectedDate ?? firstDate,
      firstDate: firstDate,
      lastDate: lastDate,
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: ColorScheme.light(
              primary: AppColors.primary,
              onPrimary: Colors.white,
              surface: Colors.white,
              onSurface: AppColors.textPrimary,
            ),
          ),
          child: child!,
        );
      },
    );

    if (picked != null) {
      setState(() {
        _selectedDate = picked;
        _selectedTimeSlot = null; // Reset time slot
      });
      // Charger les créneaux horaires disponibles
      ref.invalidate(availableTimeSlotsProvider(_selectedDate!));
    }
  }

  Future<void> _confirmAndPay() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedDate == null || _selectedTimeSlot == null) {
      _showError('Veuillez sélectionner une date et une heure');
      return;
    }

    setState(() => _isLoading = true);

    try {
      // 1. Créer le rendez-vous
      final appointment = await ref
          .read(appointmentProvider.notifier)
          .createAppointment(
            propertyId: widget.property.id,
            date: _selectedDate!,
            time: _selectedTimeSlot!.time,
          );

      // 2. Initier le paiement
      final paymentService = PaymentService(ref.read(apiServiceProvider));
      final paymentData = await paymentService.initiateAppointmentPayment(
        appointmentId: appointment['id'],
        phoneNumber: _phoneController.text.trim(),
      );

      if (mounted) {
        // 3. Ouvrir WebView pour le paiement
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => PaymentWebViewScreen(
              paymentUrl: paymentData['payment_url'],
              transactionId: paymentData['transaction_id'],
              onPaymentComplete: (success, message) {
                _handlePaymentResult(
                  success,
                  message,
                  appointment['id'],
                );
              },
            ),
          ),
        );
      }
    } catch (e) {
      _showError(e.toString());
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  void _handlePaymentResult(bool success, String message, int appointmentId) {
    if (success) {
      // Navigation vers détails du rendez-vous
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(
          builder: (context) => AppointmentDetailScreen(
            appointmentId: appointmentId,
          ),
        ),
      );
      
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(message),
          backgroundColor: AppColors.success,
        ),
      );
    } else {
      _showError(message);
    }
  }

  void _showError(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: AppColors.error,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final settingsAsync = ref.watch(settingsProvider);

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: Icon(Icons.arrow_back, color: AppColors.textPrimary),
          onPressed: () => Navigator.pop(context),
        ),
        title: Text(
          'Réserver une visite',
          style: TextStyle(
            color: AppColors.textPrimary,
            fontSize: 18,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
      body: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Aperçu de la propriété
            _buildPropertyPreview(),

            // Formulaire
            Padding(
              padding: const EdgeInsets.all(20),
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Section Date
                    _buildSectionTitle('Date de visite'),
                    const SizedBox(height: 12),
                    _buildDateSelector(),

                    const SizedBox(height: 24),

                    // Section Heure
                    if (_selectedDate != null) ...[
                      _buildSectionTitle('Heure de visite'),
                      const SizedBox(height: 12),
                      _buildTimeSlotSelector(),
                      const SizedBox(height: 24),
                    ],

                    // Section Téléphone
                    _buildSectionTitle('Numéro de téléphone'),
                    const SizedBox(height: 12),
                    _buildPhoneField(),

                    const SizedBox(height: 24),

                    // Section Prix
                    settingsAsync.when(
                      data: (settings) => _buildPriceSection(settings),
                      loading: () => const CircularProgressIndicator(),
                      error: (_, __) => const SizedBox.shrink(),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
      bottomNavigationBar: _buildBottomBar(),
    );
  }

  Widget _buildPropertyPreview() {
    return Container(
      width: double.infinity,
      height: 120,
      margin: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: [
          // Image
          ClipRRect(
            borderRadius: const BorderRadius.horizontal(
              left: Radius.circular(16),
            ),
            child: Image.network(
              widget.property.images.first,
              width: 120,
              height: 120,
              fit: BoxFit.cover,
              errorBuilder: (context, error, stackTrace) {
                return Container(
                  width: 120,
                  color: AppColors.textSecondary.withOpacity(0.1),
                  child: Icon(
                    Icons.home_outlined,
                    size: 40,
                    color: AppColors.textSecondary,
                  ),
                );
              },
            ),
          ),

          // Détails
          Expanded(
            child: Padding(
              padding: const EdgeInsets.all(12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(
                    widget.property.title,
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w600,
                      color: AppColors.textPrimary,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      Icon(
                        Icons.location_on,
                        size: 14,
                        color: AppColors.textSecondary,
                      ),
                      const SizedBox(width: 4),
                      Expanded(
                        child: Text(
                          widget.property.district,
                          style: TextStyle(
                            fontSize: 13,
                            color: AppColors.textSecondary,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSectionTitle(String title) {
    return Text(
      title,
      style: TextStyle(
        fontSize: 16,
        fontWeight: FontWeight.w600,
        color: AppColors.textPrimary,
      ),
    );
  }

  Widget _buildDateSelector() {
    return GestureDetector(
      onTap: _selectDate,
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: _selectedDate != null
                ? AppColors.primary
                : AppColors.textSecondary.withOpacity(0.3),
            width: 1.5,
          ),
        ),
        child: Row(
          children: [
            Icon(
              Icons.calendar_today,
              color: _selectedDate != null
                  ? AppColors.primary
                  : AppColors.textSecondary,
              size: 20,
            ),
            const SizedBox(width: 12),
            Text(
              _selectedDate != null
                  ? DateFormat('EEEE d MMMM yyyy', 'fr_FR')
                      .format(_selectedDate!)
                  : 'Sélectionner une date',
              style: TextStyle(
                fontSize: 15,
                color: _selectedDate != null
                    ? AppColors.textPrimary
                    : AppColors.textSecondary,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildTimeSlotSelector() {
    final timeSlotsAsync =
        ref.watch(availableTimeSlotsProvider(_selectedDate!));

    return timeSlotsAsync.when(
      loading: () => const Center(child: CircularProgressIndicator()),
      error: (error, _) => Text('Erreur: $error'),
      data: (timeSlots) {
        if (timeSlots.isEmpty) {
          return Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: AppColors.warning.withOpacity(0.1),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Row(
              children: [
                Icon(
                  Icons.info_outline,
                  color: AppColors.warning,
                  size: 20,
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    'Aucun créneau disponible pour cette date',
                    style: TextStyle(
                      fontSize: 14,
                      color: AppColors.warning,
                    ),
                  ),
                ),
              ],
            ),
          );
        }

        return Wrap(
          spacing: 12,
          runSpacing: 12,
          children: timeSlots.map((slot) {
            final isSelected = _selectedTimeSlot == slot;
            return GestureDetector(
              onTap: slot.isAvailable
                  ? () => setState(() => _selectedTimeSlot = slot)
                  : null,
              child: Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 20,
                  vertical: 12,
                ),
                decoration: BoxDecoration(
                  color: !slot.isAvailable
                      ? AppColors.textSecondary.withOpacity(0.1)
                      : isSelected
                          ? AppColors.primary
                          : Colors.white,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                    color: !slot.isAvailable
                        ? AppColors.textSecondary.withOpacity(0.3)
                        : isSelected
                            ? AppColors.primary
                            : AppColors.textSecondary.withOpacity(0.3),
                    width: 1.5,
                  ),
                ),
                child: Text(
                  slot.time,
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: isSelected ? FontWeight.w600 : FontWeight.w500,
                    color: !slot.isAvailable
                        ? AppColors.textSecondary
                        : isSelected
                            ? Colors.white
                            : AppColors.textPrimary,
                  ),
                ),
              ),
            );
          }).toList(),
        );
      },
    );
  }

  Widget _buildPhoneField() {
    return TextFormField(
      controller: _phoneController,
      keyboardType: TextInputType.phone,
      style: TextStyle(
        fontSize: 15,
        color: AppColors.textPrimary,
      ),
      decoration: InputDecoration(
        hintText: '+241 XX XX XX XX',
        hintStyle: TextStyle(
          color: AppColors.textSecondary,
        ),
        prefixIcon: Icon(
          Icons.phone_outlined,
          color: AppColors.textSecondary,
        ),
        filled: true,
        fillColor: Colors.white,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(
            color: AppColors.textSecondary.withOpacity(0.3),
            width: 1.5,
          ),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(
            color: AppColors.textSecondary.withOpacity(0.3),
            width: 1.5,
          ),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(
            color: AppColors.primary,
            width: 1.5,
          ),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(
            color: AppColors.error,
            width: 1.5,
          ),
        ),
      ),
      validator: (value) {
        if (value == null || value.isEmpty) {
          return 'Veuillez entrer votre numéro de téléphone';
        }
        if (value.length < 9) {
          return 'Numéro invalide';
        }
        return null;
      },
      onChanged: (value) {
        setState(() => _phoneNumber = value);
      },
    );
  }

  Widget _buildPriceSection(Map<String, dynamic> settings) {
    final price = settings['visit_price'] ?? 0;
    final currency = settings['currency'] ?? 'XOF';

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.primary.withOpacity(0.1),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: AppColors.primary.withOpacity(0.3),
          width: 1.5,
        ),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Frais de visite',
                style: TextStyle(
                  fontSize: 14,
                  color: AppColors.textSecondary,
                ),
              ),
              const SizedBox(height: 4),
              Row(
                children: [
                  Text(
                    '${price.toStringAsFixed(0)}',
                    style: TextStyle(
                      fontSize: 24,
                      fontWeight: FontWeight.bold,
                      color: AppColors.primary,
                    ),
                  ),
                  const SizedBox(width: 4),
                  Text(
                    currency,
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w600,
                      color: AppColors.primary,
                    ),
                  ),
                ],
              ),
            ],
          ),
          Icon(
            Icons.payments_outlined,
            size: 32,
            color: AppColors.primary,
          ),
        ],
      ),
    );
  }

  Widget _buildBottomBar() {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 10,
            offset: const Offset(0, -4),
          ),
        ],
      ),
      child: SafeArea(
        child: SizedBox(
          width: double.infinity,
          height: 56,
          child: ElevatedButton(
            onPressed: _isLoading ||
                    _selectedDate == null ||
                    _selectedTimeSlot == null ||
                    _phoneNumber.isEmpty
                ? null
                : _confirmAndPay,
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.primary,
              foregroundColor: Colors.white,
              disabledBackgroundColor: AppColors.textSecondary.withOpacity(0.3),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(16),
              ),
              elevation: 0,
            ),
            child: _isLoading
                ? const SizedBox(
                    width: 24,
                    height: 24,
                    child: CircularProgressIndicator(
                      color: Colors.white,
                      strokeWidth: 2,
                    ),
                  )
                : const Text(
                    'Confirmer et Payer',
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
          ),
        ),
      ),
    );
  }
}

// Modèle pour les créneaux horaires
class TimeSlot {
  final String time;
  final bool isAvailable;

  TimeSlot({
    required this.time,
    required this.isAvailable,
  });
}

// Provider pour les créneaux disponibles
final availableTimeSlotsProvider =
    FutureProvider.family<List<TimeSlot>, DateTime>((ref, date) async {
  final repository = ref.watch(appointmentRepositoryProvider);
  final slots = await repository.getAvailableTimeSlots(date);
  
  return slots
      .map((slot) => TimeSlot(
            time: slot['time'],
            isAvailable: slot['is_available'],
          ))
      .toList();
});