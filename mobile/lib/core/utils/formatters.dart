import 'package:intl/intl.dart';

/// Classe utilitaire pour le formatage des données
/// Utilisée pour formater les montants, dates, numéros, etc.
class Formatters {
  // ==================== CURRENCY ====================
  
  /// Formate un montant en FCFA
  /// Exemple: 150000 -> "150 000 FCFA"
  static String currency(double amount, {bool showSymbol = true}) {
    final formatter = NumberFormat('#,###', 'fr_FR');
    final formatted = formatter.format(amount).replaceAll(',', ' ');
    
    return showSymbol ? '$formatted FCFA' : formatted;
  }

  /// Formate un montant avec séparateur de milliers
  /// Exemple: 150000 -> "150 000"
  static String amount(double amount) {
    final formatter = NumberFormat('#,###', 'fr_FR');
    return formatter.format(amount).replaceAll(',', ' ');
  }

  /// Formate un montant compact (K, M)
  /// Exemple: 1500000 -> "1.5M FCFA"
  static String compactCurrency(double amount) {
    if (amount >= 1000000) {
      final millions = amount / 1000000;
      return '${millions.toStringAsFixed(1)}M FCFA';
    } else if (amount >= 1000) {
      final thousands = amount / 1000;
      return '${thousands.toStringAsFixed(0)}K FCFA';
    }
    return '${amount.toInt()} FCFA';
  }

  // ==================== DATE ====================
  
  /// Formate une date en format français
  /// Exemple: DateTime(2025, 1, 15) -> "15 janvier 2025"
  static String date(DateTime date) {
    final formatter = DateFormat('dd MMMM yyyy', 'fr_FR');
    return formatter.format(date);
  }

  /// Formate une date en format court
  /// Exemple: DateTime(2025, 1, 15) -> "15/01/2025"
  static String shortDate(DateTime date) {
    final formatter = DateFormat('dd/MM/yyyy');
    return formatter.format(date);
  }

  /// Formate une date avec le jour de la semaine
  /// Exemple: DateTime(2025, 1, 15) -> "Mercredi 15 janvier 2025"
  static String fullDate(DateTime date) {
    final formatter = DateFormat('EEEE dd MMMM yyyy', 'fr_FR');
    return formatter.format(date);
  }

  /// Formate une date en format relatif (aujourd'hui, hier, demain)
  /// Exemple: aujourd'hui -> "Aujourd'hui", hier -> "Hier"
  static String relativeDate(DateTime date) {
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    final yesterday = today.subtract(const Duration(days: 1));
    final tomorrow = today.add(const Duration(days: 1));
    
    final targetDate = DateTime(date.year, date.month, date.day);
    
    if (targetDate == today) {
      return 'Aujourd\'hui';
    } else if (targetDate == yesterday) {
      return 'Hier';
    } else if (targetDate == tomorrow) {
      return 'Demain';
    } else if (targetDate.isAfter(today) && targetDate.isBefore(today.add(const Duration(days: 7)))) {
      return DateFormat('EEEE', 'fr_FR').format(date);
    } else {
      return shortDate(date);
    }
  }

  // ==================== TIME ====================
  
  /// Formate une heure
  /// Exemple: DateTime(2025, 1, 15, 14, 30) -> "14:30"
  static String time(DateTime dateTime) {
    final formatter = DateFormat('HH:mm');
    return formatter.format(dateTime);
  }

  /// Formate une date et heure
  /// Exemple: DateTime(2025, 1, 15, 14, 30) -> "15/01/2025 à 14:30"
  static String dateTime(DateTime dateTime) {
    return '${shortDate(dateTime)} à ${time(dateTime)}';
  }

  /// Formate une date et heure en format complet
  /// Exemple: DateTime(2025, 1, 15, 14, 30) -> "Mercredi 15 janvier 2025 à 14:30"
  static String fullDateTime(DateTime dateTime) {
    return '${fullDate(dateTime)} à ${time(dateTime)}';
  }

  // ==================== PHONE ====================
  
  /// Formate un numéro de téléphone
  /// Exemple: "0712345678" -> "07 12 34 56 78"
  static String phone(String phone) {
    // Supprimer tous les caractères non numériques
    final cleaned = phone.replaceAll(RegExp(r'[^0-9]'), '');
    
    // Si le numéro commence par 241, le garder
    if (cleaned.startsWith('241')) {
      final local = cleaned.substring(3);
      return '+241 ${local.substring(0, 2)} ${local.substring(2, 4)} ${local.substring(4, 6)} ${local.substring(6)}';
    }
    
    // Format local: 07 12 34 56 78
    if (cleaned.length == 9) {
      return '${cleaned.substring(0, 2)} ${cleaned.substring(2, 4)} ${cleaned.substring(4, 6)} ${cleaned.substring(6, 8)} ${cleaned.substring(8)}';
    }
    
    return phone; // Retourner tel quel si format non reconnu
  }

  // ==================== NUMBER ====================
  
  /// Formate un nombre avec séparateur de milliers
  /// Exemple: 150000 -> "150 000"
  static String number(int number) {
    final formatter = NumberFormat('#,###', 'fr_FR');
    return formatter.format(number).replaceAll(',', ' ');
  }

  /// Formate un pourcentage
  /// Exemple: 0.75 -> "75%"
  static String percentage(double value) {
    return '${(value * 100).toStringAsFixed(0)}%';
  }

  // ==================== DURATION ====================
  
  /// Formate une durée en texte lisible
  /// Exemple: Duration(hours: 2, minutes: 30) -> "2h 30min"
  static String duration(Duration duration) {
    final hours = duration.inHours;
    final minutes = duration.inMinutes.remainder(60);
    
    if (hours > 0 && minutes > 0) {
      return '${hours}h ${minutes}min';
    } else if (hours > 0) {
      return '${hours}h';
    } else {
      return '${minutes}min';
    }
  }

  /// Formate une durée relative (il y a X temps)
  /// Exemple: maintenant - 2 heures -> "Il y a 2 heures"
  static String timeAgo(DateTime dateTime) {
    final now = DateTime.now();
    final difference = now.difference(dateTime);
    
    if (difference.inDays > 365) {
      final years = (difference.inDays / 365).floor();
      return 'Il y a $years ${years > 1 ? 'ans' : 'an'}';
    } else if (difference.inDays > 30) {
      final months = (difference.inDays / 30).floor();
      return 'Il y a $months mois';
    } else if (difference.inDays > 0) {
      return 'Il y a ${difference.inDays} ${difference.inDays > 1 ? 'jours' : 'jour'}';
    } else if (difference.inHours > 0) {
      return 'Il y a ${difference.inHours} ${difference.inHours > 1 ? 'heures' : 'heure'}';
    } else if (difference.inMinutes > 0) {
      return 'Il y a ${difference.inMinutes} ${difference.inMinutes > 1 ? 'minutes' : 'minute'}';
    } else {
      return 'À l\'instant';
    }
  }

  // ==================== TEXT ====================
  
  /// Capitalise la première lettre
  /// Exemple: "bonjour" -> "Bonjour"
  static String capitalize(String text) {
    if (text.isEmpty) return text;
    return text[0].toUpperCase() + text.substring(1).toLowerCase();
  }

  /// Capitalise chaque mot
  /// Exemple: "hello world" -> "Hello World"
  static String titleCase(String text) {
    if (text.isEmpty) return text;
    
    return text.split(' ').map((word) {
      if (word.isEmpty) return word;
      return word[0].toUpperCase() + word.substring(1).toLowerCase();
    }).join(' ');
  }

  /// Tronque un texte avec ellipsis
  /// Exemple: "Ceci est un long texte" -> "Ceci est un..."
  static String truncate(String text, int maxLength, {String ellipsis = '...'}) {
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength - ellipsis.length) + ellipsis;
  }

  // ==================== ADDRESS ====================
  
  /// Formate une adresse complète
  static String address({
    required String street,
    required String city,
    String? district,
    String? country,
  }) {
    final parts = [
      street,
      if (district != null) district,
      city,
      if (country != null) country,
    ];
    
    return parts.join(', ');
  }

  // ==================== STATUS ====================
  
  /// Formate un statut en texte lisible
  static String status(String status) {
    final statusMap = {
      'pending': 'En attente',
      'confirmed': 'Confirmé',
      'completed': 'Terminé',
      'cancelled': 'Annulé',
      'paid': 'Payé',
      'unpaid': 'Impayé',
      'overdue': 'En retard',
      'active': 'Actif',
      'inactive': 'Inactif',
      'available': 'Disponible',
      'rented': 'Loué',
      'under_maintenance': 'En maintenance',
    };
    
    return statusMap[status.toLowerCase()] ?? capitalize(status);
  }

  // ==================== FILE SIZE ====================
  
  /// Formate une taille de fichier
  /// Exemple: 1536000 -> "1.5 MB"
  static String fileSize(int bytes) {
    if (bytes < 1024) {
      return '$bytes B';
    } else if (bytes < 1024 * 1024) {
      final kb = bytes / 1024;
      return '${kb.toStringAsFixed(1)} KB';
    } else if (bytes < 1024 * 1024 * 1024) {
      final mb = bytes / (1024 * 1024);
      return '${mb.toStringAsFixed(1)} MB';
    } else {
      final gb = bytes / (1024 * 1024 * 1024);
      return '${gb.toStringAsFixed(1)} GB';
    }
  }

  // ==================== PROPERTY TYPE ====================
  
  /// Formate un type de propriété
  static String propertyType(String type) {
    final typeMap = {
      'apartment': 'Appartement',
      'house': 'Maison',
      'villa': 'Villa',
      'studio': 'Studio',
      'duplex': 'Duplex',
      'land': 'Terrain',
      'office': 'Bureau',
      'shop': 'Commerce',
    };
    
    return typeMap[type.toLowerCase()] ?? capitalize(type);
  }
}