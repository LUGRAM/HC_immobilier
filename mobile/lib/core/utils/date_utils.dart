/// Classe utilitaire pour les manipulations de dates
/// Extensions et helpers pour faciliter le travail avec les dates
class DateUtils {
  // ==================== DATE COMPARISONS ====================
  
  /// Vérifie si deux dates sont le même jour
  static bool isSameDay(DateTime date1, DateTime date2) {
    return date1.year == date2.year &&
        date1.month == date2.month &&
        date1.day == date2.day;
  }

  /// Vérifie si une date est aujourd'hui
  static bool isToday(DateTime date) {
    return isSameDay(date, DateTime.now());
  }

  /// Vérifie si une date est hier
  static bool isYesterday(DateTime date) {
    final yesterday = DateTime.now().subtract(const Duration(days: 1));
    return isSameDay(date, yesterday);
  }

  /// Vérifie si une date est demain
  static bool isTomorrow(DateTime date) {
    final tomorrow = DateTime.now().add(const Duration(days: 1));
    return isSameDay(date, tomorrow);
  }

  /// Vérifie si une date est dans le passé
  static bool isPast(DateTime date) {
    return date.isBefore(DateTime.now());
  }

  /// Vérifie si une date est dans le futur
  static bool isFuture(DateTime date) {
    return date.isAfter(DateTime.now());
  }

  /// Vérifie si une date est dans la semaine en cours
  static bool isThisWeek(DateTime date) {
    final now = DateTime.now();
    final startOfWeek = now.subtract(Duration(days: now.weekday - 1));
    final endOfWeek = startOfWeek.add(const Duration(days: 6));
    
    return date.isAfter(startOfWeek.subtract(const Duration(days: 1))) &&
        date.isBefore(endOfWeek.add(const Duration(days: 1)));
  }

  /// Vérifie si une date est dans le mois en cours
  static bool isThisMonth(DateTime date) {
    final now = DateTime.now();
    return date.year == now.year && date.month == now.month;
  }

  /// Vérifie si une date est dans l'année en cours
  static bool isThisYear(DateTime date) {
    return date.year == DateTime.now().year;
  }

  // ==================== DATE MANIPULATION ====================
  
  /// Retourne le début de la journée (00:00:00)
  static DateTime startOfDay(DateTime date) {
    return DateTime(date.year, date.month, date.day);
  }

  /// Retourne la fin de la journée (23:59:59)
  static DateTime endOfDay(DateTime date) {
    return DateTime(date.year, date.month, date.day, 23, 59, 59);
  }

  /// Retourne le début de la semaine (lundi 00:00:00)
  static DateTime startOfWeek(DateTime date) {
    final daysToSubtract = date.weekday - 1;
    final startOfWeek = date.subtract(Duration(days: daysToSubtract));
    return DateTime(startOfWeek.year, startOfWeek.month, startOfWeek.day);
  }

  /// Retourne la fin de la semaine (dimanche 23:59:59)
  static DateTime endOfWeek(DateTime date) {
    final daysToAdd = 7 - date.weekday;
    final endOfWeek = date.add(Duration(days: daysToAdd));
    return DateTime(endOfWeek.year, endOfWeek.month, endOfWeek.day, 23, 59, 59);
  }

  /// Retourne le début du mois (1er jour à 00:00:00)
  static DateTime startOfMonth(DateTime date) {
    return DateTime(date.year, date.month, 1);
  }

  /// Retourne la fin du mois (dernier jour à 23:59:59)
  static DateTime endOfMonth(DateTime date) {
    final nextMonth = DateTime(date.year, date.month + 1, 1);
    final lastDay = nextMonth.subtract(const Duration(days: 1));
    return DateTime(lastDay.year, lastDay.month, lastDay.day, 23, 59, 59);
  }

  /// Retourne le début de l'année (1er janvier à 00:00:00)
  static DateTime startOfYear(DateTime date) {
    return DateTime(date.year, 1, 1);
  }

  /// Retourne la fin de l'année (31 décembre à 23:59:59)
  static DateTime endOfYear(DateTime date) {
    return DateTime(date.year, 12, 31, 23, 59, 59);
  }

  // ==================== DATE CALCULATIONS ====================
  
  /// Ajoute des jours à une date
  static DateTime addDays(DateTime date, int days) {
    return date.add(Duration(days: days));
  }

  /// Soustrait des jours à une date
  static DateTime subtractDays(DateTime date, int days) {
    return date.subtract(Duration(days: days));
  }

  /// Ajoute des mois à une date
  static DateTime addMonths(DateTime date, int months) {
    int targetMonth = date.month + months;
    int targetYear = date.year;
    
    while (targetMonth > 12) {
      targetMonth -= 12;
      targetYear++;
    }
    
    while (targetMonth < 1) {
      targetMonth += 12;
      targetYear--;
    }
    
    // Gérer le cas où le jour n'existe pas dans le mois cible
    final maxDay = DateTime(targetYear, targetMonth + 1, 0).day;
    final targetDay = date.day > maxDay ? maxDay : date.day;
    
    return DateTime(
      targetYear,
      targetMonth,
      targetDay,
      date.hour,
      date.minute,
      date.second,
    );
  }

  /// Soustrait des mois à une date
  static DateTime subtractMonths(DateTime date, int months) {
    return addMonths(date, -months);
  }

  /// Calcule la différence en jours entre deux dates
  static int daysBetween(DateTime date1, DateTime date2) {
    final start = startOfDay(date1);
    final end = startOfDay(date2);
    return end.difference(start).inDays;
  }

  /// Calcule la différence en mois entre deux dates
  static int monthsBetween(DateTime date1, DateTime date2) {
    return (date2.year - date1.year) * 12 + date2.month - date1.month;
  }

  // ==================== BUSINESS DAYS ====================
  
  /// Vérifie si un jour est un jour ouvrable (lundi-samedi)
  static bool isBusinessDay(DateTime date) {
    // Au Gabon, les jours ouvrables sont du lundi au samedi
    return date.weekday != DateTime.sunday;
  }

  /// Vérifie si un jour est un week-end (dimanche)
  static bool isWeekend(DateTime date) {
    return date.weekday == DateTime.sunday;
  }

  /// Retourne le prochain jour ouvrable
  static DateTime nextBusinessDay(DateTime date) {
    DateTime next = addDays(date, 1);
    
    while (!isBusinessDay(next)) {
      next = addDays(next, 1);
    }
    
    return next;
  }

  /// Retourne le jour ouvrable précédent
  static DateTime previousBusinessDay(DateTime date) {
    DateTime previous = subtractDays(date, 1);
    
    while (!isBusinessDay(previous)) {
      previous = subtractDays(previous, 1);
    }
    
    return previous;
  }

  /// Compte le nombre de jours ouvrables entre deux dates
  static int businessDaysBetween(DateTime start, DateTime end) {
    int count = 0;
    DateTime current = startOfDay(start);
    final endDay = startOfDay(end);
    
    while (current.isBefore(endDay) || isSameDay(current, endDay)) {
      if (isBusinessDay(current)) {
        count++;
      }
      current = addDays(current, 1);
    }
    
    return count;
  }

  // ==================== TIME SLOTS ====================
  
  /// Génère les créneaux horaires disponibles pour une journée
  /// (8h-18h avec intervalles de 30 minutes, pause déjeuner 12h-14h)
  static List<DateTime> generateTimeSlots(DateTime date) {
    final slots = <DateTime>[];
    final baseDate = startOfDay(date);
    
    // Créneaux du matin: 8h00 - 12h00
    for (int hour = 8; hour < 12; hour++) {
      for (int minute = 0; minute < 60; minute += 30) {
        slots.add(baseDate.add(Duration(hours: hour, minutes: minute)));
      }
    }
    
    // Pause déjeuner: 12h00 - 14h00 (pas de créneaux)
    
    // Créneaux de l'après-midi: 14h00 - 18h00
    for (int hour = 14; hour < 18; hour++) {
      for (int minute = 0; minute < 60; minute += 30) {
        slots.add(baseDate.add(Duration(hours: hour, minutes: minute)));
      }
    }
    
    return slots;
  }

  /// Filtre les créneaux disponibles (retire ceux déjà passés)
  static List<DateTime> getAvailableTimeSlots(DateTime date) {
    final allSlots = generateTimeSlots(date);
    final now = DateTime.now();
    
    // Si la date est aujourd'hui, filtrer les créneaux passés
    if (isToday(date)) {
      return allSlots.where((slot) => slot.isAfter(now.add(const Duration(hours: 1)))).toList();
    }
    
    return allSlots;
  }

  // ==================== AGE CALCULATION ====================
  
  /// Calcule l'âge à partir d'une date de naissance
  static int calculateAge(DateTime birthDate) {
    final today = DateTime.now();
    int age = today.year - birthDate.year;
    
    if (today.month < birthDate.month ||
        (today.month == birthDate.month && today.day < birthDate.day)) {
      age--;
    }
    
    return age;
  }

  // ==================== HELPERS ====================
  
  /// Retourne le nombre de jours dans un mois
  static int daysInMonth(int year, int month) {
    return DateTime(year, month + 1, 0).day;
  }

  /// Retourne le nom du mois en français
  static String monthName(int month) {
    const months = [
      'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
      'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
    ];
    return months[month - 1];
  }

  /// Retourne le nom du jour en français
  static String dayName(int weekday) {
    const days = [
      'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'
    ];
    return days[weekday - 1];
  }

  /// Parse une date depuis une chaîne ISO 8601
  static DateTime? parseIso(String? dateString) {
    if (dateString == null || dateString.isEmpty) return null;
    
    try {
      return DateTime.parse(dateString);
    } catch (e) {
      return null;
    }
  }

  /// Convertit une date en chaîne ISO 8601
  static String toIso(DateTime date) {
    return date.toIso8601String();
  }

  /// Crée une date à partir de composants
  static DateTime createDate({
    required int year,
    required int month,
    required int day,
    int hour = 0,
    int minute = 0,
    int second = 0,
  }) {
    return DateTime(year, month, day, hour, minute, second);
  }
}

/// Extension sur DateTime pour des méthodes pratiques
extension DateTimeExtension on DateTime {
  /// Vérifie si la date est aujourd'hui
  bool get isToday => DateUtils.isToday(this);

  /// Vérifie si la date est hier
  bool get isYesterday => DateUtils.isYesterday(this);

  /// Vérifie si la date est demain
  bool get isTomorrow => DateUtils.isTomorrow(this);

  /// Vérifie si la date est dans le passé
  bool get isPast => DateUtils.isPast(this);

  /// Vérifie si la date est dans le futur
  bool get isFuture => DateUtils.isFuture(this);

  /// Vérifie si la date est un jour ouvrable
  bool get isBusinessDay => DateUtils.isBusinessDay(this);

  /// Vérifie si la date est un week-end
  bool get isWeekend => DateUtils.isWeekend(this);

  /// Retourne le début de la journée
  DateTime get startOfDay => DateUtils.startOfDay(this);

  /// Retourne la fin de la journée
  DateTime get endOfDay => DateUtils.endOfDay(this);

  /// Retourne le début de la semaine
  DateTime get startOfWeek => DateUtils.startOfWeek(this);

  /// Retourne la fin de la semaine
  DateTime get endOfWeek => DateUtils.endOfWeek(this);

  /// Retourne le début du mois
  DateTime get startOfMonth => DateUtils.startOfMonth(this);

  /// Retourne la fin du mois
  DateTime get endOfMonth => DateUtils.endOfMonth(this);

  /// Ajoute des jours
  DateTime addDays(int days) => DateUtils.addDays(this, days);

  /// Soustrait des jours
  DateTime subtractDays(int days) => DateUtils.subtractDays(this, days);

  /// Ajoute des mois
  DateTime addMonths(int months) => DateUtils.addMonths(this, months);

  /// Soustrait des mois
  DateTime subtractMonths(int months) => DateUtils.subtractMonths(this, months);
}