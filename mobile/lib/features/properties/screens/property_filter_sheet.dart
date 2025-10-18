import 'package:flutter/material.dart';
import '../../../core/constants/app_colors.dart';

class PropertyFilterSheet extends StatefulWidget {
  final String? selectedType;
  final double? minPrice;
  final double? maxPrice;
  final int? minBedrooms;
  final String? selectedCity;
  final String? selectedDistrict;
  final Function(Map<String, dynamic>) onApply;
  final VoidCallback onReset;

  const PropertyFilterSheet({
    super.key,
    this.selectedType,
    this.minPrice,
    this.maxPrice,
    this.minBedrooms,
    this.selectedCity,
    this.selectedDistrict,
    required this.onApply,
    required this.onReset,
  });

  @override
  State<PropertyFilterSheet> createState() => _PropertyFilterSheetState();
}

class _PropertyFilterSheetState extends State<PropertyFilterSheet> {
  late String? _selectedType;
  late RangeValues _priceRange;
  late int? _minBedrooms;
  late String? _selectedCity;
  late String? _selectedDistrict;

  final List<String> _propertyTypes = [
    'Tous',
    'Appartement',
    'Maison',
    'Villa',
    'Studio',
  ];

  final List<String> _cities = [
    'Libreville',
    'Port-Gentil',
    'Franceville',
    'Oyem',
    'Moanda',
  ];

  @override
  void initState() {
    super.initState();
    _selectedType = widget.selectedType;
    _priceRange = RangeValues(
      widget.minPrice ?? 0,
      widget.maxPrice ?? 1000000,
    );
    _minBedrooms = widget.minBedrooms;
    _selectedCity = widget.selectedCity;
    _selectedDistrict = widget.selectedDistrict;
  }

  void _apply() {
    widget.onApply({
      'type': _selectedType,
      'minPrice': _priceRange.start > 0 ? _priceRange.start : null,
      'maxPrice': _priceRange.end < 1000000 ? _priceRange.end : null,
      'minBedrooms': _minBedrooms,
      'city': _selectedCity,
      'district': _selectedDistrict,
    });
    Navigator.pop(context);
  }

  void _reset() {
    setState(() {
      _selectedType = null;
      _priceRange = RangeValues(0, 1000000);
      _minBedrooms = null;
      _selectedCity = null;
      _selectedDistrict = null;
    });
    widget.onReset();
    Navigator.pop(context);
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      child: DraggableScrollableSheet(
        initialChildSize: 0.85,
        minChildSize: 0.5,
        maxChildSize: 0.95,
        builder: (context, scrollController) {
          return Column(
            children: [
              // ============ HEADER ============
              Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  border: Border(
                    bottom: BorderSide(color: Colors.grey[200]!),
                  ),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      'Filtres',
                      style: TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    IconButton(
                      icon: Icon(Icons.close),
                      onPressed: () => Navigator.pop(context),
                    ),
                  ],
                ),
              ),

              // ============ CONTENU ============
              Expanded(
                child: ListView(
                  controller: scrollController,
                  padding: const EdgeInsets.all(20),
                  children: [
                    // TYPE DE BIEN
                    _buildSectionTitle('Type de bien'),
                    SizedBox(height: 12),
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: _propertyTypes.map((type) {
                        final isSelected = _selectedType == type;
                        return FilterChip(
                          label: Text(type),
                          selected: isSelected,
                          onSelected: (selected) {
                            setState(() {
                              _selectedType = selected ? type : null;
                            });
                          },
                          backgroundColor: Colors.grey[100],
                          selectedColor: AppColors.primary,
                          labelStyle: TextStyle(
                            color: isSelected ? Colors.white : Colors.black,
                            fontWeight: isSelected ? FontWeight.w600 : FontWeight.normal,
                          ),
                        );
                      }).toList(),
                    ),

                    SizedBox(height: 32),

                    // PRIX
                    _buildSectionTitle(
                      'Prix (FCFA)',
                      subtitle: '${_priceRange.start.toInt()} - ${_priceRange.end.toInt()}',
                    ),
                    SizedBox(height: 12),
                    RangeSlider(
                      values: _priceRange,
                      min: 0,
                      max: 1000000,
                      divisions: 20,
                      activeColor: AppColors.primary,
                      labels: RangeLabels(
                        _priceRange.start.toInt().toString(),
                        _priceRange.end.toInt().toString(),
                      ),
                      onChanged: (values) {
                        setState(() => _priceRange = values);
                      },
                    ),

                    SizedBox(height: 32),

                    // CHAMBRES
                    _buildSectionTitle('Nombre de chambres minimum'),
                    SizedBox(height: 12),
                    Row(
                      children: [1, 2, 3, 4, 5].map((count) {
                        final isSelected = _minBedrooms == count;
                        return Expanded(
                          child: Padding(
                            padding: const EdgeInsets.symmetric(horizontal: 4),
                            child: InkWell(
                              onTap: () {
                                setState(() {
                                  _minBedrooms = isSelected ? null : count;
                                });
                              },
                              child: Container(
                                padding: EdgeInsets.symmetric(vertical: 12),
                                decoration: BoxDecoration(
                                  color: isSelected 
                                      ? AppColors.primary 
                                      : Colors.grey[100],
                                  borderRadius: BorderRadius.circular(12),
                                  border: Border.all(
                                    color: isSelected 
                                        ? AppColors.primary 
                                        : Colors.grey[300]!,
                                  ),
                                ),
                                child: Text(
                                  count == 5 ? '5+' : count.toString(),
                                  textAlign: TextAlign.center,
                                  style: TextStyle(
                                    color: isSelected ? Colors.white : Colors.black,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ),
                            ),
                          ),
                        );
                      }).toList(),
                    ),

                    SizedBox(height: 32),

                    // VILLE
                    _buildSectionTitle('Ville'),
                    SizedBox(height: 12),
                    DropdownButtonFormField<String>(
                      value: _selectedCity,
                      decoration: InputDecoration(
                        hintText: 'Sélectionner une ville',
                        filled: true,
                        fillColor: Colors.grey[100],
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                          borderSide: BorderSide.none,
                        ),
                      ),
                      items: [
                        DropdownMenuItem(value: null, child: Text('Toutes')),
                        ..._cities.map((city) {
                          return DropdownMenuItem(
                            value: city,
                            child: Text(city),
                          );
                        }),
                      ],
                      onChanged: (value) {
                        setState(() {
                          _selectedCity = value;
                          _selectedDistrict = null; // Reset district
                        });
                      },
                    ),

                    SizedBox(height: 32),

                    // QUARTIER (si ville sélectionnée)
                    if (_selectedCity != null) ...[
                      _buildSectionTitle('Quartier'),
                      SizedBox(height: 12),
                      DropdownButtonFormField<String>(
                        value: _selectedDistrict,
                        decoration: InputDecoration(
                          hintText: 'Sélectionner un quartier',
                          filled: true,
                          fillColor: Colors.grey[100],
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                            borderSide: BorderSide.none,
                          ),
                        ),
                        items: [
                          DropdownMenuItem(value: null, child: Text('Tous')),
                          // À remplacer par les vrais quartiers selon la ville
                          DropdownMenuItem(value: 'Centre-ville', child: Text('Centre-ville')),
                          DropdownMenuItem(value: 'Nombakélé', child: Text('Nombakélé')),
                          DropdownMenuItem(value: 'Batterie IV', child: Text('Batterie IV')),
                          DropdownMenuItem(value: 'Glass', child: Text('Glass')),
                        ],
                        onChanged: (value) {
                          setState(() => _selectedDistrict = value);
                        },
                      ),
                    ],
                  ],
                ),
              ),

              // ============ FOOTER ACTIONS ============
              Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: Colors.white,
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.05),
                      blurRadius: 10,
                      offset: Offset(0, -2),
                    ),
                  ],
                ),
                child: Row(
                  children: [
                    Expanded(
                      child: OutlinedButton(
                        onPressed: _reset,
                        style: OutlinedButton.styleFrom(
                          padding: EdgeInsets.symmetric(vertical: 16),
                          side: BorderSide(color: AppColors.primary),
                        ),
                        child: Text('Réinitialiser'),
                      ),
                    ),
                    SizedBox(width: 12),
                    Expanded(
                      flex: 2,
                      child: ElevatedButton(
                        onPressed: _apply,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AppColors.primary,
                          foregroundColor: Colors.white,
                          padding: EdgeInsets.symmetric(vertical: 16),
                        ),
                        child: Text('Appliquer'),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  Widget _buildSectionTitle(String title, {String? subtitle}) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          title,
          style: TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.bold,
            color: Colors.grey[900],
          ),
        ),
        if (subtitle != null)
          Text(
            subtitle,
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w600,
              color: AppColors.primary,
            ),
          ),
      ],
    );
  }
}