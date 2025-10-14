class PropertyModel {
  final int id;
  final String title;
  final String description;
  final String address;
  final String district;
  final String city;
  final double monthlyRent;
  final int bedrooms;
  final int bathrooms;
  final double? surfaceArea;
  final String propertyType;
  final String status;
  final List<String> amenities;
  final List<PropertyImage> images;
  final int viewsCount;

  PropertyModel({
    required this.id,
    required this.title,
    required this.description,
    required this.address,
    required this.district,
    required this.city,
    required this.monthlyRent,
    required this.bedrooms,
    required this.bathrooms,
    this.surfaceArea,
    required this.propertyType,
    required this.status,
    required this.amenities,
    required this.images,
    required this.viewsCount,
  });

  bool get isAvailable => status == 'available';
  
  String? get primaryImageUrl => 
      images.isNotEmpty ? images.first.url : null;

  factory PropertyModel.fromJson(Map<String, dynamic> json) {
    return PropertyModel(
      id: json['id'],
      title: json['title'],
      description: json['description'],
      address: json['address'],
      district: json['district'],
      city: json['city'],
      monthlyRent: double.parse(json['monthly_rent'].toString()),
      bedrooms: json['bedrooms'],
      bathrooms: json['bathrooms'],
      surfaceArea: json['surface_area'] != null 
          ? double.parse(json['surface_area'].toString())
          : null,
      propertyType: json['property_type'],
      status: json['status'],
      amenities: List<String>.from(json['amenities'] ?? []),
      images: (json['images'] as List?)
          ?.map((img) => PropertyImage.fromJson(img))
          .toList() ?? [],
      viewsCount: json['views_count'] ?? 0,
    );
  }
}

class PropertyImage {
  final int id;
  final String url;
  final bool isPrimary;

  PropertyImage({
    required this.id,
    required this.url,
    required this.isPrimary,
  });

  factory PropertyImage.fromJson(Map<String, dynamic> json) {
    return PropertyImage(
      id: json['id'],
      url: json['url'],
      isPrimary: json['is_primary'] ?? false,
    );
  }
}