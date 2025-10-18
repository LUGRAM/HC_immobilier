import 'package:flutter/material.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/constants/app_strings.dart';
import '../../../data/providers/property_provider.dart';

class PropertyFilterSheet extends StatefulWidget {
  final PropertyFilters currentFilters;
  final Function(PropertyFilters) onApply;

  const PropertyFilterSheet({
    super.key,
    required this.currentFilters,
    required this.onApply,
  });

  @override
  State<PropertyFilterSheet> createState() => _PropertyFilterSheetState();
}

class _PropertyFilterSheetState extends State<PropertyFilterSheet> {
  late String? _selectedPropertyType;
  late String? _selectedDistrict;
  late double _minPrice;
  late double _maxPrice;
  late int? _bedrooms;

  final List<String> _propertyTypes = [
    'Tous',
    'Appartement',
    'Maison',
    'Villa',
    'Studio',
  ];

  final List<String> _districts = [
    'Tous',
    'Libreville',
    'Owendo',
    'Akanda',
    'Ntoum',
    'PK5',
    'PK8',
    'PK12',
  ];

  @override
  void initState() {
    super.initState();
    _selectedPropertyType = widget.currentFilters.propertyType ?? 'Tous';
    _selectedDistrict = widget.currentFilters.district ?? 'Tous';
    _minPrice = widget.currentFilters.minPrice ?? 0;
    _maxPrice = widget.currentFilters.maxPrice ?? 1000000;
    _bedrooms = widget.currentFilters.bedrooms;
  }

  void _resetFilters() {
    setState(() {
      _selectedPropertyType = 'Tous';
      _selectedDistrict = 'Tous';
      _minPrice = 0;
      _maxPrice = 1000000;
      _bedrooms = null;
    });
  }

  void _applyFilters() {
    final filters = PropertyFilters(
      page: 1,
      propertyType: _selectedPropertyType == 'Tous' ? null : _selectedPropertyType,
      district: _selectedDistrict == 'Tous' ? null : _selectedDistrict,
      minPrice: _minPrice > 0 ? _minPrice : null,
      maxPrice: _maxPrice < 1000000 ? _maxPrice : null,
      bedrooms: _bedrooms,
      search: widget.currentFilters.search,
    );
    widget.onApply(filters);
    Navigator.pop(context);
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      child: SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Header
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  AppStrings.filters,
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                TextButton(
                  onPressed: _resetFilters,
                  child: Text(
                    AppStrings.resetFilters,
                    style: TextStyle(color: AppColors.error),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 24),

            // Type de bien
            const Text(
              'Type de bien',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w600,
              ),
            ),
            const SizedBox(height: 12),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: _propertyTypes.map((type) {
                final isSelected = _selectedPropertyType == type;
                return GestureDetector(
                  onTap: () => setState(() => _selectedPropertyType = type),
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 16,
                      vertical: 8,
                    ),
                    decoration: BoxDecoration(
                      color: isSelected ? AppColors.primary : Colors.white,
                      border: Border.all(
                        color: isSelected
                            ? AppColors.primary
                            : AppColors.textSecondary.withOpacity(0.3),
                      ),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      type,
                      style: TextStyle(
                        color: isSelected ? Colors.white : AppColors.textPrimary,
                        fontWeight: isSelected ? FontWeight.w600 : FontWeight.normal,
                      ),
                    ),
                  ),
                );
              }).toList(),
            ),

            const SizedBox(height: 24),

            // Quartier
            const Text(
              'Quartier',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w600,
              ),
            ),
            const SizedBox(height: 12),
            DropdownButtonFormField<String>(
              value: _selectedDistrict,
              decoration: const InputDecoration(
                border: OutlineInputBorder(),
                contentPadding: EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              ),
              items: _districts.map((district) {
                return DropdownMenuItem(
                  value: district,
                  child: Text(district),
                );
              }).toList(),
              onChanged: (value) {
                setState(() => _selectedDistrict = value);
              },
            ),

            const SizedBox(height: 24),

            // Fourchette de prix
            const Text(
              'Fourchette de prix',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w600,
              ),
            ),
            const SizedBox(height: 12),
            RangeSlider(
              values: RangeValues(_minPrice, _maxPrice),
              min: 0,
              max: 1000000,
              divisions: 20,
              labels: RangeLabels(
                '${_minPrice.toInt()} FCFA',
                '${_maxPrice.toInt()} FCFA',
              ),
              onChanged: (values) {
                setState(() {
                  _minPrice = values.start;
                  _maxPrice = values.end;
                });
              },
            ),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  '${_minPrice.toInt()} FCFA',
                  style: TextStyle(color: AppColors.textSecondary),
                ),
                Text(
                  '${_maxPrice.toInt()} FCFA',
                  style: TextStyle(color: AppColors.textSecondary),
                ),
              ],
            ),

            const SizedBox(height: 24),

            // Nombre de chambres
            const Text(
              'Nombre de chambres',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w600,
              ),
            ),
            const SizedBox(height: 12),
            Row(
              children: [1, 2, 3, 4, 5].map((num) {
                final isSelected = _bedrooms == num;
                return Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: GestureDetector(
                    onTap: () => setState(() {
                      _bedrooms = isSelected ? null : num;
                    }),
                    child: Container(
                      width: 50,
                      height: 50,
                      decoration: BoxDecoration(
                        color: isSelected ? AppColors.primary : Colors.white,
                        border: Border.all(
                          color: isSelected
                              ? AppColors.primary
                              : AppColors.textSecondary.withOpacity(0.3),
                        ),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Center(
                        child: Text(
                          num.toString(),
                          style: TextStyle(
                            color: isSelected ? Colors.white : AppColors.textPrimary,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                    ),
                  ),
                );
              }).toList(),
            ),

            const SizedBox(height: 32),

            // Bouton Appliquer
            SizedBox(
              height: 50,
              child: ElevatedButton(
                onPressed: _applyFilters,
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.primary,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                child: Text(
                  AppStrings.applyFilters,
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}