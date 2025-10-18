/// Classe utilitaire pour la validation des formulaires
/// Contient toutes les validations nécessaires pour l'app HouseConnect
class Validators {
  // ==================== EMAIL ====================
  
  /// Valide un email
  static String? email(String? value) {
    if (value == null || value.isEmpty) {
      return 'L\'email est obligatoire';
    }
    
    final emailRegex = RegExp(
      r'^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$',
    );
    
    if (!emailRegex.hasMatch(value.trim())) {
      return 'Email invalide';
    }
    
    return null;
  }

  // ==================== PASSWORD ====================
  
  /// Valide un mot de passe (min 8 caractères, 1 majuscule, 1 chiffre)
  static String? password(String? value) {
    if (value == null || value.isEmpty) {
      return 'Le mot de passe est obligatoire';
    }
    
    if (value.length < 8) {
      return 'Le mot de passe doit contenir au moins 8 caractères';
    }
    
    if (!RegExp(r'[A-Z]').hasMatch(value)) {
      return 'Le mot de passe doit contenir au moins une majuscule';
    }
    
    if (!RegExp(r'[0-9]').hasMatch(value)) {
      return 'Le mot de passe doit contenir au moins un chiffre';
    }
    
    return null;
  }

  /// Valide la confirmation du mot de passe
  static String? confirmPassword(String? value, String? password) {
    if (value == null || value.isEmpty) {
      return 'Veuillez confirmer le mot de passe';
    }
    
    if (value != password) {
      return 'Les mots de passe ne correspondent pas';
    }
    
    return null;
  }

  // ==================== PHONE ====================
  
  /// Valide un numéro de téléphone (Gabon, format international)
  static String? phone(String? value) {
    if (value == null || value.isEmpty) {
      return 'Le numéro de téléphone est obligatoire';
    }
    
    // Supprimer les espaces et caractères spéciaux
    final cleaned = value.replaceAll(RegExp(r'[\s\-\(\)]'), '');
    
    // Formats acceptés:
    // +24107XXXXXXX, 24107XXXXXXX, 07XXXXXXX, 01XXXXXXX, 04XXXXXXX, 05XXXXXXX, 06XXXXXXX
    final phoneRegex = RegExp(
      r'^(\+?241)?(0[1-7])[0-9]{7}$',
    );
    
    if (!phoneRegex.hasMatch(cleaned)) {
      return 'Numéro de téléphone invalide (ex: 07 12 34 56 78)';
    }
    
    return null;
  }

  // ==================== NAME ====================
  
  /// Valide un nom (min 2 caractères, lettres uniquement)
  static String? name(String? value, {String field = 'nom'}) {
    if (value == null || value.isEmpty) {
      return 'Le $field est obligatoire';
    }
    
    if (value.trim().length < 2) {
      return 'Le $field doit contenir au moins 2 caractères';
    }
    
    if (!RegExp(r'^[a-zA-ZÀ-ÿ\s\-]+$').hasMatch(value)) {
      return 'Le $field ne peut contenir que des lettres';
    }
    
    return null;
  }

  // ==================== REQUIRED ====================
  
  /// Valide qu'un champ n'est pas vide
  static String? required(String? value, {String field = 'champ'}) {
    if (value == null || value.trim().isEmpty) {
      return 'Le $field est obligatoire';
    }
    return null;
  }

  // ==================== NUMBER ====================
  
  /// Valide qu'une valeur est un nombre positif
  static String? positiveNumber(String? value, {String field = 'montant'}) {
    if (value == null || value.isEmpty) {
      return 'Le $field est obligatoire';
    }
    
    final number = double.tryParse(value.replaceAll(' ', ''));
    
    if (number == null) {
      return 'Le $field doit être un nombre valide';
    }
    
    if (number <= 0) {
      return 'Le $field doit être supérieur à 0';
    }
    
    return null;
  }

  /// Valide un montant (permet les décimales)
  static String? amount(String? value) {
    return positiveNumber(value, field: 'montant');
  }

  // ==================== OTP ====================
  
  /// Valide un code OTP (6 chiffres)
  static String? otp(String? value) {
    if (value == null || value.isEmpty) {
      return 'Le code est obligatoire';
    }
    
    if (!RegExp(r'^[0-9]{6}$').hasMatch(value)) {
      return 'Le code doit contenir 6 chiffres';
    }
    
    return null;
  }

  // ==================== ADDRESS ====================
  
  /// Valide une adresse
  static String? address(String? value) {
    if (value == null || value.isEmpty) {
      return 'L\'adresse est obligatoire';
    }
    
    if (value.trim().length < 5) {
      return 'L\'adresse doit contenir au moins 5 caractères';
    }
    
    return null;
  }

  // ==================== DESCRIPTION ====================
  
  /// Valide une description (optionnelle mais avec longueur min si remplie)
  static String? description(String? value, {int minLength = 10}) {
    if (value == null || value.isEmpty) {
      return null; // Optionnel
    }
    
    if (value.trim().length < minLength) {
      return 'La description doit contenir au moins $minLength caractères';
    }
    
    return null;
  }

  // ==================== CUSTOM VALIDATORS ====================
  
  /// Valide un prix de loyer (min 50,000 FCFA)
  static String? rentPrice(String? value) {
    final error = positiveNumber(value, field: 'loyer');
    if (error != null) return error;
    
    final amount = double.parse(value!.replaceAll(' ', ''));
    
    if (amount < 50000) {
      return 'Le loyer minimum est de 50 000 FCFA';
    }
    
    return null;
  }

  /// Valide une surface (en m²)
  static String? surface(String? value) {
    final error = positiveNumber(value, field: 'surface');
    if (error != null) return error;
    
    final surface = double.parse(value!.replaceAll(' ', ''));
    
    if (surface < 10) {
      return 'La surface minimum est de 10 m²';
    }
    
    if (surface > 10000) {
      return 'La surface maximum est de 10 000 m²';
    }
    
    return null;
  }

  /// Valide un nombre de pièces
  static String? rooms(String? value) {
    if (value == null || value.isEmpty) {
      return 'Le nombre de pièces est obligatoire';
    }
    
    final number = int.tryParse(value);
    
    if (number == null) {
      return 'Le nombre de pièces doit être un entier';
    }
    
    if (number < 1 || number > 20) {
      return 'Le nombre de pièces doit être entre 1 et 20';
    }
    
    return null;
  }

  // ==================== DATE VALIDATION ====================
  
  /// Valide qu'une date n'est pas dans le passé
  static String? futureDate(DateTime? value) {
    if (value == null) {
      return 'La date est obligatoire';
    }
    
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    final selectedDate = DateTime(value.year, value.month, value.day);
    
    if (selectedDate.isBefore(today)) {
      return 'La date ne peut pas être dans le passé';
    }
    
    return null;
  }

  /// Valide qu'une date est dans le futur (pour les rendez-vous)
  static String? appointmentDate(DateTime? value) {
    final error = futureDate(value);
    if (error != null) return error;
    
    // Vérifier que ce n'est pas un dimanche
    if (value!.weekday == DateTime.sunday) {
      return 'Les rendez-vous ne sont pas disponibles le dimanche';
    }
    
    // Vérifier que c'est dans les 90 prochains jours
    final maxDate = DateTime.now().add(const Duration(days: 90));
    if (value.isAfter(maxDate)) {
      return 'Les rendez-vous sont limités à 90 jours à l\'avance';
    }
    
    return null;
  }

  // ==================== COMPOSITE VALIDATORS ====================
  
  /// Combine plusieurs validateurs
  static String? combine(List<String? Function()> validators) {
    for (final validator in validators) {
      final error = validator();
      if (error != null) return error;
    }
    return null;
  }
}