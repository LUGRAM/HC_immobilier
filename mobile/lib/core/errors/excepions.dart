class ApiException implements Exception {
  final String message;
  
  ApiException(this.message);
  
  @override
  String toString() => message;
}

class UnauthorizedException extends ApiException {
  UnauthorizedException(super.message);
}

class ForbiddenException extends ApiException {
  ForbiddenException(super.message);
}

class NotFoundException extends ApiException {
  NotFoundException(super.message);
}

class ValidationException extends ApiException {
  final Map<String, dynamic>? errors;
  
  ValidationException(super.message, this.errors);
}
