class ApiEndpoints {
  static const String baseUrl = 'https://your-api-domain.com/api/v1';
  
  // Auth
  static const String login = '/login';
  static const String register = '/register';
  static const String logout = '/logout';
  static const String me = '/me';
  static const String verifyOtp = '/verify-otp';
  
  // Properties
  static const String properties = '/properties';
  static String propertyDetail(int id) => '/properties/$id';
  static const String districts = '/districts';
  static const String visitSettings = '/settings/visit-price';
  
  // Appointments
  static const String appointments = '/appointments';
  static String appointmentDetail(int id) => '/appointments/$id';
  static String cancelAppointment(int id) => '/appointments/$id/cancel';
  
  // Leases
  static const String activeLease = '/leases/active';
  static String requestLease(int appointmentId) => 
      '/appointments/$appointmentId/request-lease';
  
  // Invoices
  static const String invoices = '/invoices';
  static String invoiceDetail(int id) => '/invoices/$id';
  static String payInvoice(int id) => '/invoices/$id/pay';
  
  // Expenses
  static const String expenses = '/expenses';
  static const String expenseSummary = '/expenses/statistics/summary';
  static const String expensesByCategory = '/expenses/statistics/by-category';
  
  // Dashboard
  static const String clientDashboard = '/dashboard/client';
  static const String landlordDashboard = '/dashboard/landlord';
  
  // Notifications
  static const String notifications = '/notifications';
  static const String unreadCount = '/notifications/unread-count';
  static String markAsRead(String id) => '/notifications/$id/read';
  
  // Device Tokens
  static const String registerDevice = '/device-tokens';
}