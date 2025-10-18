class UserModel {
  final int id;
  final String firstName;
  final String lastName;
  final String email;
  final String? phoneNumber;
  final String role; // 'client', 'landlord', 'admin'
  final String? avatarUrl;
  final bool isVerified;
  final int? validatedPropertyId; // ID du bien validé (pour clients)
  final DateTime createdAt;
  final DateTime updatedAt;

  UserModel({
    required this.id,
    required this.firstName,
    required this.lastName,
    required this.email,
    this.phoneNumber,
    required this.role,
    this.avatarUrl,
    this.isVerified = false,
    this.validatedPropertyId,
    required this.createdAt,
    required this.updatedAt,
  });

  // Getters utiles
  String get fullName => '$firstName $lastName';
  
  bool get isClient => role == 'client';
  bool get isLandlord => role == 'landlord';
  bool get isAdmin => role == 'admin';
  
  bool get hasValidatedProperty => validatedPropertyId != null;

  // Factory depuis JSON
  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: json['id'] as int,
      firstName: json['first_name'] as String,
      lastName: json['last_name'] as String,
      email: json['email'] as String,
      phoneNumber: json['phone_number'] as String?,
      role: json['role'] as String,
      avatarUrl: json['avatar_url'] as String?,
      isVerified: json['is_verified'] as bool? ?? false,
      validatedPropertyId: json['validated_property_id'] as int?,
      createdAt: DateTime.parse(json['created_at'] as String),
      updatedAt: DateTime.parse(json['updated_at'] as String),
    );
  }

  // Convertir en JSON
  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'first_name': firstName,
      'last_name': lastName,
      'email': email,
      'phone_number': phoneNumber,
      'role': role,
      'avatar_url': avatarUrl,
      'is_verified': isVerified,
      'validated_property_id': validatedPropertyId,
      'created_at': createdAt.toIso8601String(),
      'updated_at': updatedAt.toIso8601String(),
    };
  }

  // CopyWith pour modifications immutables
  UserModel copyWith({
    int? id,
    String? firstName,
    String? lastName,
    String? email,
    String? phoneNumber,
    String? role,
    String? avatarUrl,
    bool? isVerified,
    int? validatedPropertyId,
    DateTime? createdAt,
    DateTime? updatedAt,
  }) {
    return UserModel(
      id: id ?? this.id,
      firstName: firstName ?? this.firstName,
      lastName: lastName ?? this.lastName,
      email: email ?? this.email,
      phoneNumber: phoneNumber ?? this.phoneNumber,
      role: role ?? this.role,
      avatarUrl: avatarUrl ?? this.avatarUrl,
      isVerified: isVerified ?? this.isVerified,
      validatedPropertyId: validatedPropertyId ?? this.validatedPropertyId,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
    );
  }

  @override
  String toString() {
    return 'UserModel(id: $id, fullName: $fullName, email: $email, role: $role, hasValidatedProperty: $hasValidatedProperty)';
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is UserModel && other.id == id;
  }

  @override
  int get hashCode => id.hashCode;
}