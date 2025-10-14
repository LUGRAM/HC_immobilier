import '../../core/services/api_service.dart';
import '../../core/services/storage_service.dart';
import '../../core/constants/api_endpoints.dart';
import '../models/user_model.dart';

class AuthRepository {
  final ApiService _apiService;
  final StorageService _storageService;

  AuthRepository(this._apiService, this._storageService);

  Future<Map<String, dynamic>> login(String email, String password) async {
    final response = await _apiService.post(
      ApiEndpoints.login,
      data: {
        'email': email,
        'password': password,
      },
    );

    final data = response.data['data'];
    final token = data['token'];
    final user = UserModel.fromJson(data['user']);

    await _storageService.saveToken(token);
    await _storageService.saveUser(user.toJson());

    return {'user': user, 'token': token};
  }

  Future<Map<String, dynamic>> register({
    required String firstName,
    required String lastName,
    required String email,
    required String phone,
    required String password,
    String role = 'client',
  }) async {
    final response = await _apiService.post(
      ApiEndpoints.register,
      data: {
        'first_name': firstName,
        'last_name': lastName,
        'email': email,
        'phone': phone,
        'password': password,
        'password_confirmation': password,
        'role': role,
      },
    );

    final data = response.data['data'];
    final token = data['token'];
    final user = UserModel.fromJson(data['user']);

    await _storageService.saveToken(token);
    await _storageService.saveUser(user.toJson());

    return {'user': user, 'token': token};
  }

  Future<void> logout() async {
    try {
      await _apiService.post(ApiEndpoints.logout);
    } catch (e) {
      // Continuer même si la requête échoue
    } finally {
      await _storageService.clearAll();
    }
  }

  Future<UserModel> getCurrentUser() async {
    final response = await _apiService.get(ApiEndpoints.me);
    final user = UserModel.fromJson(response.data['data']);
    await _storageService.saveUser(user.toJson());
    return user;
  }

  Future<UserModel?> getCachedUser() async {
    final userData = await _storageService.getUser();
    if (userData != null) {
      return UserModel.fromJson(userData);
    }
    return null;
  }

  Future<bool> isAuthenticated() async {
    final token = await _storageService.getToken();
    return token != null;
  }

  Future<void> verifyOtp(String phone, String otp) async {
    await _apiService.post(
      ApiEndpoints.verifyOtp,
      data: {
        'phone': phone,
        'otp': otp,
      },
    );
  }
}