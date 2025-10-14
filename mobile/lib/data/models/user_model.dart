class UserModel {
  final int id;
  final String firstName;
  final String lastName;
  final String email;
  final String phone;
  final String role;
  final String? profilePhoto;
  final bool isActive;

  UserModel({
    required this.id,
    required this.firstName,
    required this.lastName,
    required this.email,
    required this.phone,
    required this.role,
    this.profilePhoto,
    required this.isActive,
  });

  String get fullName => '$firstName $lastName';

  bool get isClient => role == 'client';
  bool get isLandlord => role == 'landlord';
  bool get isAdmin => role == 'admin';

  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: json['id'],
      firstName: json['first_name'],
      lastName: json['last_name'],
      email: json['email'],
      phone: json['phone'],
      role: json['role'],
      profilePhoto: json['profile_photo'],
      isActive: json['is_active'] ?? true,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'first_name': firstName,
      'last_name': lastName,
      'email': email,
      'phone': phone,
      'role': role,
      'profile_photo': profilePhoto,
      'is_active': isActive,
    };
  }
}