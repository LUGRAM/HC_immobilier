class ApiEndpoints {
  // Base URL - À remplacer par votre URL backend
  static const String baseUrl = 'https://your-backend-url.com/api';

  // Auth endpoints
  static const String login = '/auth/login';
  static const String register = '/auth/register';
  static const String logout = '/auth/logout';
  static const String me = '/auth/me';
  static const String updateProfile = '/auth/profile';
  static const String changePassword = '/auth/change-password';
  static const String forgotPassword = '/auth/forgot-password';
  static const String resetPassword = '/auth/reset-password';
  static const String verifyOtp = '/auth/verify-otp';
  static const String resendOtp = '/auth/resend-otp';

  // Properties endpoints
  static const String properties = '/properties';
  static const String propertyDetail = '/properties'; // + /{id}
  static const String searchProperties = '/properties/search';
  static const String popularProperties = '/properties/popular';
  static const String nearbyProperties = '/properties/nearby';
  static const String myProperties = '/properties/my-properties';

  // Appointments endpoints
  static const String appointments = '/appointments';
  static const String appointmentDetail = '/appointments'; // + /{id}
  static const String createAppointment = '/appointments';
  static const String cancelAppointment = '/appointments'; // + /{id}/cancel
  static const String validateAppointment = '/appointments'; // + /{id}/validate
  static const String availableTimeSlots = '/appointments/available-slots';
  static const String myAppointments = '/appointments/my-appointments';

  // Payments endpoints
  static const String initiatePayment = '/payments/initiate';
  static const String initiateInvoicePayment = '/payments/initiate-invoice';
  static const String checkPaymentStatus = '/payments/status';
  static const String paymentHistory = '/payments/history';
  static const String webhookCinetPay = '/webhooks/cinetpay';

  // Invoices endpoints
  static const String invoices = '/invoices';
  static const String invoiceDetail = '/invoices'; // + /{id}
  static const String myInvoices = '/invoices/my-invoices';
  static const String pendingInvoices = '/invoices/pending';
  static const String payInvoice = '/invoices'; // + /{id}/pay

  // Leases endpoints
  static const String leases = '/leases';
  static const String leaseDetail = '/leases'; // + /{id}
  static const String myLeases = '/leases/my-leases';
  static const String activeLeases = '/leases/active';
  static const String createLease = '/leases';
  static const String terminateLease = '/leases'; // + /{id}/terminate

  // Expenses endpoints
  static const String expenses = '/expenses';
  static const String expenseDetail = '/expenses'; // + /{id}
  static const String createExpense = '/expenses';
  static const String updateExpense = '/expenses'; // + /{id}
  static const String deleteExpense = '/expenses'; // + /{id}
  static const String expensesByCategory = '/expenses/by-category';
  static const String monthlyExpenses = '/expenses/monthly';

  // Maintenance endpoints
  static const String maintenanceRequests = '/maintenance-requests';
  static const String maintenanceDetail = '/maintenance-requests'; // + /{id}
  static const String createMaintenance = '/maintenance-requests';
  static const String updateMaintenanceStatus = '/maintenance-requests'; // + /{id}/status
  static const String myMaintenanceRequests = '/maintenance-requests/my-requests';

  // Dashboard endpoints
  static const String clientDashboard = '/dashboard/client';
  static const String landlordDashboard = '/dashboard/landlord';
  static const String dashboardStats = '/dashboard/stats';

  // Notifications endpoints
  static const String notifications = '/notifications';
  static const String markAsRead = '/notifications'; // + /{id}/read
  static const String markAllAsRead = '/notifications/read-all';
  static const String unreadCount = '/notifications/unread-count';
  static const String registerDeviceToken = '/notifications/register-device';
  static const String sendTestNotification = '/notifications/test';

  // Settings endpoints
  static const String settings = '/settings';
  static const String visitPrice = '/settings/visit-price';
  static const String currency = '/settings/currency';

  // Favorites endpoints
  static const String favorites = '/favorites';
  static const String addFavorite = '/favorites'; // POST with property_id
  static const String removeFavorite = '/favorites'; // DELETE + /{property_id}
  static const String myFavorites = '/favorites/my-favorites';

  // Districts & Cities endpoints
  static const String districts = '/districts';
  static const String cities = '/cities';

  // Upload endpoints
  static const String uploadImage = '/upload/image';
  static const String uploadDocument = '/upload/document';

  // Helpers pour construire des URLs dynamiques
  static String getPropertyDetailUrl(int id) => '$propertyDetail/$id';
  static String getAppointmentDetailUrl(int id) => '$appointmentDetail/$id';
  static String getInvoiceDetailUrl(int id) => '$invoiceDetail/$id';
  static String getLeaseDetailUrl(int id) => '$leaseDetail/$id';
  static String getMaintenanceDetailUrl(int id) => '$maintenanceDetail/$id';
  static String getExpenseDetailUrl(int id) => '$expenseDetail/$id';
  static String getPaymentStatusUrl(String transactionId) => '$checkPaymentStatus/$transactionId';
  static String getCancelAppointmentUrl(int id) => '$appointments/$id/cancel';
  static String getValidateAppointmentUrl(int id) => '$appointments/$id/validate';
  static String getPayInvoiceUrl(int id) => '$invoices/$id/pay';
  static String getTerminateLeaseUrl(int id) => '$leases/$id/terminate';
  static String getUpdateMaintenanceStatusUrl(int id) => '$maintenanceRequests/$id/status';
  static String getMarkNotificationAsReadUrl(int id) => '$notifications/$id/read';
  static String getRemoveFavoriteUrl(int propertyId) => '$favorites/$propertyId';
}