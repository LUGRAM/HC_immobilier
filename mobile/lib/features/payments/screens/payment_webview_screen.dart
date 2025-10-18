import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';
import '../../../core/constants/app_colors.dart';

/// Écran WebView pour gérer le paiement via CinetPay
/// Affiche l'interface de paiement Mobile Money et gère les callbacks
class PaymentWebViewScreen extends StatefulWidget {
  final String paymentUrl;
  final String transactionId;
  final Function(bool success, String message) onPaymentComplete;

  const PaymentWebViewScreen({
    super.key,
    required this.paymentUrl,
    required this.transactionId,
    required this.onPaymentComplete,
  });

  @override
  State<PaymentWebViewScreen> createState() => _PaymentWebViewScreenState();
}

class _PaymentWebViewScreenState extends State<PaymentWebViewScreen> {
  late final WebViewController _controller;
  bool _isLoading = true;
  double _progress = 0;
  String? _error;

  @override
  void initState() {
    super.initState();
    _initializeWebView();
  }

  void _initializeWebView() {
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setBackgroundColor(Colors.white)
      ..setNavigationDelegate(
        NavigationDelegate(
          onProgress: (int progress) {
            setState(() {
              _progress = progress / 100;
              _isLoading = progress < 100;
            });
          },
          onPageStarted: (String url) {
            setState(() {
              _isLoading = true;
              _error = null;
            });
          },
          onPageFinished: (String url) {
            setState(() {
              _isLoading = false;
            });
          },
          onWebResourceError: (WebResourceError error) {
            setState(() {
              _error = error.description;
              _isLoading = false;
            });
          },
          onNavigationRequest: (NavigationRequest request) {
            final uri = Uri.parse(request.url);
            
            // Détecter les URLs de callback CinetPay
            if (_isSuccessUrl(uri)) {
              _handlePaymentSuccess();
              return NavigationDecision.prevent;
            } else if (_isCancelUrl(uri)) {
              _handlePaymentCancel();
              return NavigationDecision.prevent;
            } else if (_isErrorUrl(uri)) {
              _handlePaymentError();
              return NavigationDecision.prevent;
            }
            
            return NavigationDecision.navigate;
          },
        ),
      )
      ..loadRequest(Uri.parse(widget.paymentUrl));
  }

  bool _isSuccessUrl(Uri uri) {
    // URLs de succès possibles de CinetPay
    return uri.path.contains('/success') ||
        uri.path.contains('/payment/success') ||
        uri.queryParameters.containsKey('status') &&
            uri.queryParameters['status'] == 'success' ||
        uri.queryParameters.containsKey('payment_status') &&
            uri.queryParameters['payment_status'] == 'paid';
  }

  bool _isCancelUrl(Uri uri) {
    // URLs d'annulation possibles
    return uri.path.contains('/cancel') ||
        uri.path.contains('/payment/cancel') ||
        uri.queryParameters.containsKey('status') &&
            uri.queryParameters['status'] == 'cancelled';
  }

  bool _isErrorUrl(Uri uri) {
    // URLs d'erreur possibles
    return uri.path.contains('/error') ||
        uri.path.contains('/payment/error') ||
        uri.path.contains('/failed') ||
        uri.queryParameters.containsKey('status') &&
            (uri.queryParameters['status'] == 'failed' ||
                uri.queryParameters['status'] == 'error');
  }

  void _handlePaymentSuccess() {
    widget.onPaymentComplete(true, 'Paiement effectué avec succès');
    Navigator.of(context).pop(true);
  }

  void _handlePaymentCancel() {
    widget.onPaymentComplete(false, 'Paiement annulé');
    Navigator.of(context).pop(false);
  }

  void _handlePaymentError() {
    widget.onPaymentComplete(false, 'Erreur lors du paiement');
    Navigator.of(context).pop(false);
  }

  void _showExitConfirmation() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Annuler le paiement ?'),
        content: const Text(
          'Êtes-vous sûr de vouloir quitter ? Votre paiement ne sera pas effectué.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Continuer le paiement'),
          ),
          TextButton(
            onPressed: () {
              Navigator.pop(context); // Fermer le dialog
              Navigator.pop(context, false); // Fermer la WebView
            },
            style: TextButton.styleFrom(
              foregroundColor: Colors.red,
            ),
            child: const Text('Quitter'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return WillPopScope(
      onWillPop: () async {
        _showExitConfirmation();
        return false; // Empêcher la sortie directe
      },
      child: Scaffold(
        backgroundColor: Colors.white,
        appBar: AppBar(
          title: const Text('Paiement sécurisé'),
          backgroundColor: Colors.white,
          foregroundColor: AppColors.textPrimary,
          elevation: 0,
          leading: IconButton(
            icon: const Icon(Icons.close),
            onPressed: _showExitConfirmation,
          ),
          bottom: _isLoading
              ? PreferredSize(
                  preferredSize: const Size.fromHeight(4),
                  child: LinearProgressIndicator(
                    value: _progress,
                    backgroundColor: Colors.grey.shade200,
                    valueColor: AlwaysStoppedAnimation<Color>(
                      AppColors.primary,
                    ),
                  ),
                )
              : null,
        ),
        body: _buildBody(),
      ),
    );
  }

  Widget _buildBody() {
    if (_error != null) {
      return _buildErrorState();
    }

    return Stack(
      children: [
        // WebView
        WebViewWidget(controller: _controller),

        // Loading overlay (au début uniquement)
        if (_isLoading && _progress < 0.3)
          Container(
            color: Colors.white,
            child: Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  CircularProgressIndicator(
                    valueColor: AlwaysStoppedAnimation<Color>(
                      AppColors.primary,
                    ),
                  ),
                  const SizedBox(height: 24),
                  const Text(
                    'Chargement sécurisé...',
                    style: TextStyle(
                      fontSize: 16,
                      color: AppColors.textSecondary,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(
                        Icons.lock,
                        size: 16,
                        color: AppColors.textSecondary,
                      ),
                      const SizedBox(width: 4),
                      Text(
                        'Connexion sécurisée',
                        style: TextStyle(
                          fontSize: 12,
                          color: AppColors.textSecondary,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
      ],
    );
  }

  Widget _buildErrorState() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.error_outline,
              size: 64,
              color: Colors.red.shade300,
            ),
            const SizedBox(height: 24),
            const Text(
              'Erreur de chargement',
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 12),
            Text(
              _error ?? 'Une erreur est survenue',
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 14,
                color: AppColors.textSecondary,
              ),
            ),
            const SizedBox(height: 32),
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                OutlinedButton(
                  onPressed: () => Navigator.pop(context, false),
                  child: const Text('Annuler'),
                ),
                const SizedBox(width: 16),
                ElevatedButton(
                  onPressed: () {
                    setState(() {
                      _error = null;
                      _isLoading = true;
                    });
                    _controller.loadRequest(Uri.parse(widget.paymentUrl));
                  },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.primary,
                  ),
                  child: const Text('Réessayer'),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  @override
  void dispose() {
    super.dispose();
  }
}