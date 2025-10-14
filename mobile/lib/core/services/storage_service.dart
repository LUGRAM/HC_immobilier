import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';

class StorageService {
  static const String _tokenKey = 'auth_token';
  static const String _userKey = 'user_data';
  static const String _onboardingKey = 'onboarding_completed';

  final SharedPreferences _prefs;

  StorageService(this._prefs);

  // Token management
  Future<void> saveToken(String token) async {
    await _prefs.setString(_tokenKey, token);
  }

  Future<String?> getToken() async {
    return _prefs.getString(_tokenKey);
  }

  Future<void> deleteToken() async {
    await _prefs.remove(_tokenKey);
  }

  // User data
  Future<void> saveUser(Map<String, dynamic> userData) async {
    await _prefs.setString(_userKey, jsonEncode(userData));
  }

  Future<Map<String, dynamic>?> getUser() async {
    final userString = _prefs.getString(_userKey);
    if (userString != null) {
      return jsonDecode(userString);
    }
    return null;
  }

  Future<void> deleteUser() async {
    await _prefs.remove(_userKey);
  }

  // Onboarding
  Future<void> setOnboardingCompleted() async {
    await _prefs.setBool(_onboardingKey, true);
  }

  bool isOnboardingCompleted() {
    return _prefs.getBool(_onboardingKey) ?? false;
  }

  // Clear all data
  Future<void> clearAll() async {
    await _prefs.clear();
  }
}
