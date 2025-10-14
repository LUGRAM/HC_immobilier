import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/constants/app_colors.dart';
import '../../../data/models/property_model.dart';
import '../../../data/providers/property_provider.dart';
import '../widgets/property_card.dart';

class PropertyListScreen extends ConsumerStatefulWidget {
  const PropertyListScreen({super.key});

  @override
  ConsumerState<PropertyListScreen> createState() => _PropertyListScreenState();
}

class _PropertyListScreenState extends ConsumerState<PropertyListScreen> {
  final _searchController = TextEditingController();
  PropertyFilters _filters = PropertyFilters();

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  void _showFilterSheet() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => PropertyFilterSheet(
        currentFilters: _filters,
        onApply: (filters) {
          setState(() {
            _filters = filters;
          });
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final propertiesAsync = ref.watch(propertiesProvider(_filters));

    return Scaffold(
      appBar: AppBar(
        title: const Text('Biens Disponibles'),
        actions: [
          IconButton(
            icon: const Icon(Icons.filter_list),
            onPressed: _showFilterSheet,
          ),
        ],
      ),
      body: Column(
        children: [
          // Search Bar
          Padding(
            padding: const EdgeInsets.all(16),
            child: TextField(
              controller: _searchController,
              decoration: InputDecoration(
                hintText: 'Rechercher un bien...',
                prefixIcon: const Icon(Icons.search),
                suffixIcon: IconButton(
                  icon: const Icon(Icons.clear),
                  onPressed: () {
                    _searchController.clear();
                    setState(() {
                      _filters = PropertyFilters();
                    });
                  },
                ),
              ),
              onSubmitted: (value) {
                setState(() {
                  _filters = PropertyFilters(search: value);
                });
              },
            ),
          ),
          
          // Properties List
          Expanded(
            child: propertiesAsync.when(
              data: (result) {
                final properties = result['properties'] as List<PropertyModel>;
                
                if (properties.isEmpty) {
                  return Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(
                          Icons.home_outlined,
                          size: 80,
                          color: AppColors.textLight,
                        ),
                        const SizedBox(height: 16),
                        Text(
                          'Aucun bien disponible',
                          style: TextStyle(
                            color: AppColors.textSecondary,
                            fontSize: 16,
                          ),
                        ),
                      ],
                    ),
                  );
                }
                
                return RefreshIndicator(
                  onRefresh: () async {
                    ref.invalidate(propertiesProvider(_filters));
                  },
                  child: ListView.builder(
                    padding: const EdgeInsets.all(16),
                    itemCount: properties.length,
                    itemBuilder: (context, index) {
                      return PropertyCard(
                        property: properties[index],
                        onTap: () {
                          Navigator.of(context).pushNamed(
                            '/property-detail',
                            arguments: properties[index].id,
                          );
                        },
                      );
                    },
                  ),
                );
              },
              loading: () => const Center(
                child: CircularProgressIndicator(),
              ),
              error: (error, stack) => Center(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(
                      Icons.error_outline,
                      size: 60,
                      color: AppColors.error,
                    ),
                    const SizedBox(height: 16),
                    Text(
                      'Erreur de chargement',
                      style: TextStyle(
                        color: AppColors.textSecondary,
                        fontSize: 16,
                      ),
                    ),
                    const SizedBox(height: 8),
                    ElevatedButton(
                      onPressed: () {
                        ref.invalidate(propertiesProvider(_filters));
                      },
                      child: const Text('Réessayer'),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
