import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import '../constants/api_endpoints.dart';
import '../errors/excepions.dart';
import 'storage_service.dart';

class ApiService {
  late final Dio _dio;
  final StorageService _storage;

  ApiService(this._storage) {
    _dio = Dio(BaseOptions(
      baseUrl: ApiEndpoints.baseUrl,
      connectTimeout: const Duration(seconds: 30),
      receiveTimeout: const Duration(seconds: 30),
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    ));

    // Intercepteurs
    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        // Ajouter le token d'authentification
        final token = await _storage.getToken();
        if (token != null) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        
        if (kDebugMode) {
          print('REQUEST[${options.method}] => ${options.uri}');
        }
        
        return handler.next(options);
      },
      onResponse: (response, handler) {
        if (kDebugMode) {
          print('RESPONSE[${response.statusCode}] => ${response.requestOptions.uri}');
        }
        return handler.next(response);
      },
      onError: (error, handler) {
        if (kDebugMode) {
          print('ERROR[${error.response?.statusCode}] => ${error.requestOptions.uri}');
          print('Error data: ${error.response?.data}');
        }
        return handler.next(error);
      },
    ));
  }

  // GET request
  Future<Response> get(
    String endpoint, {
    Map<String, dynamic>? queryParameters,
  }) async {
    try {
      return await _dio.get(endpoint, queryParameters: queryParameters);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  // POST request
  Future<Response> post(
    String endpoint, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
  }) async {
    try {
      return await _dio.post(
        endpoint,
        data: data,
        queryParameters: queryParameters,
      );
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  // PUT request
  Future<Response> put(
    String endpoint, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
  }) async {
    try {
      return await _dio.put(
        endpoint,
        data: data,
        queryParameters: queryParameters,
      );
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  // DELETE request
  Future<Response> delete(
    String endpoint, {
    Map<String, dynamic>? queryParameters,
  }) async {
    try {
      return await _dio.delete(endpoint, queryParameters: queryParameters);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  // Upload file
  Future<Response> uploadFile(
    String endpoint,
    String filePath, {
    String fieldName = 'file',
    Map<String, dynamic>? additionalData,
  }) async {
    try {
      final formData = FormData.fromMap({
        fieldName: await MultipartFile.fromFile(filePath),
        if (additionalData != null) ...additionalData,
      });

      return await _dio.post(endpoint, data: formData);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  Exception _handleError(DioException error) {
    switch (error.type) {
      case DioExceptionType.connectionTimeout:
      case DioExceptionType.sendTimeout:
      case DioExceptionType.receiveTimeout:
        return ApiException('Délai de connexion dépassé');
      
      case DioExceptionType.badResponse:
        final statusCode = error.response?.statusCode;
        final message = error.response?.data['message'] ?? 'Erreur serveur';
        
        if (statusCode == 401) {
          return UnauthorizedException(message);
        } else if (statusCode == 403) {
          return ForbiddenException(message);
        } else if (statusCode == 404) {
          return NotFoundException(message);
        } else if (statusCode == 422) {
          return ValidationException(
            message,
            error.response?.data['errors'],
          );
        }
        return ApiException(message);
      
      case DioExceptionType.cancel:
        return ApiException('Requête annulée');
      
      default:
        return ApiException('Erreur de connexion. Vérifiez votre internet.');
    }
  }
}
