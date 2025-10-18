import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../data/providers/auth_provider.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/routes/app_router.dart';

class ProfileScreen extends ConsumerWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authState = ref.watch(authProvider);
    final user = authState.user;

    if (user == null) {
      return Scaffold(
        body: Center(
          child: Text('Utilisateur non connecté'),
        ),
      );
    }

    return Scaffold(
      backgroundColor: Colors.grey[50],
      appBar: AppBar(
        title: Text('Mon Profil'),
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
      ),
      body: SingleChildScrollView(
        child: Column(
          children: [
            // ============ HEADER PROFIL ============
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [
                    AppColors.primary,
                    AppColors.primaryDark,
                  ],
                ),
              ),
              child: Column(
                children: [
                  // Avatar
                  Stack(
                    children: [
                      CircleAvatar(
                        radius: 50,
                        backgroundColor: Colors.white,
                        child: user.profileImage != null
                            ? ClipOval(
                                child: Image.network(
                                  user.profileImage!,
                                  width: 100,
                                  height: 100,
                                  fit: BoxFit.cover,
                                  errorBuilder: (_, __, ___) => _buildDefaultAvatar(user.name),
                                ),
                              )
                            : _buildDefaultAvatar(user.name),
                      ),
                      Positioned(
                        bottom: 0,
                        right: 0,
                        child: Container(
                          padding: EdgeInsets.all(8),
                          decoration: BoxDecoration(
                            color: AppColors.secondary,
                            shape: BoxShape.circle,
                            border: Border.all(color: Colors.white, width: 2),
                          ),
                          child: Icon(
                            Icons.edit,
                            size: 16,
                            color: Colors.white,
                          ),
                        ),
                      ),
                    ],
                  ),

                  SizedBox(height: 16),

                  // Nom
                  Text(
                    user.name,
                    style: TextStyle(
                      fontSize: 24,
                      fontWeight: FontWeight.bold,
                      color: Colors.white,
                    ),
                  ),

                  SizedBox(height: 4),

                  // Email
                  Text(
                    user.email,
                    style: TextStyle(
                      fontSize: 14,
                      color: Colors.white.withOpacity(0.9),
                    ),
                  ),

                  SizedBox(height: 8),

                  // Badge rôle
                  Container(
                    padding: EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                    decoration: BoxDecoration(
                      color: Colors.white.withOpacity(0.2),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      user.isClient
                          ? 'Client'
                          : user.isLandlord
                              ? 'Bailleur'
                              : 'Admin',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),

                  // Badge validation (si client)
                  if (user.isClient) ...[
                    SizedBox(height: 8),
                    Container(
                      padding: EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                      decoration: BoxDecoration(
                        color: user.hasValidatedProperty
                            ? Colors.green.withOpacity(0.3)
                            : Colors.orange.withOpacity(0.3),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(
                            user.hasValidatedProperty
                                ? Icons.verified
                                : Icons.info_outline,
                            size: 16,
                            color: Colors.white,
                          ),
                          SizedBox(width: 6),
                          Text(
                            user.hasValidatedProperty
                                ? 'Compte vérifié'
                                : 'En attente de visite',
                            style: TextStyle(
                              color: Colors.white,
                              fontSize: 12,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ],
              ),
            ),

            SizedBox(height: 16),

            // ============ INFORMATIONS PERSONNELLES ============
            _buildSection(
              context,
              title: 'Informations personnelles',
              children: [
                _buildInfoTile(
                  icon: Icons.person_outline,
                  title: 'Nom complet',
                  value: user.name,
                  onTap: () => _editProfile(context, ref, 'name'),
                ),
                Divider(height: 1),
                _buildInfoTile(
                  icon: Icons.email_outlined,
                  title: 'Email',
                  value: user.email,
                  onTap: () => _editProfile(context, ref, 'email'),
                ),
                Divider(height: 1),
                _buildInfoTile(
                  icon: Icons.phone_outlined,
                  title: 'Téléphone',
                  value: user.phone ?? 'Non renseigné',
                  onTap: () => _editProfile(context, ref, 'phone'),
                ),
              ],
            ),

            SizedBox(height: 16),

            // ============ PARAMÈTRES ============
            _buildSection(
              context,
              title: 'Paramètres',
              children: [
                _buildMenuTile(
                  icon: Icons.lock_outline,
                  title: 'Changer le mot de passe',
                  onTap: () => _changePassword(context, ref),
                ),
                Divider(height: 1),
                _buildMenuTile(
                  icon: Icons.notifications_outlined,
                  title: 'Notifications',
                  onTap: () {
                    Navigator.pushNamed(context, AppRouter.settings);
                  },
                ),
                Divider(height: 1),
                _buildMenuTile(
                  icon: Icons.language_outlined,
                  title: 'Langue',
                  trailing: Text(
                    'Français',
                    style: TextStyle(color: Colors.grey[600]),
                  ),
                  onTap: () {
                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(content: Text('Bientôt disponible')),
                    );
                  },
                ),
              ],
            ),

            SizedBox(height: 16),

            // ============ SUPPORT ============
            _buildSection(
              context,
              title: 'Support',
              children: [
                _buildMenuTile(
                  icon: Icons.help_outline,
                  title: 'Centre d\'aide',
                  onTap: () {
                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(content: Text('Bientôt disponible')),
                    );
                  },
                ),
                Divider(height: 1),
                _buildMenuTile(
                  icon: Icons.privacy_tip_outlined,
                  title: 'Politique de confidentialité',
                  onTap: () {
                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(content: Text('Bientôt disponible')),
                    );
                  },
                ),
                Divider(height: 1),
                _buildMenuTile(
                  icon: Icons.description_outlined,
                  title: 'Conditions d\'utilisation',
                  onTap: () {
                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(content: Text('Bientôt disponible')),
                    );
                  },
                ),
              ],
            ),

            SizedBox(height: 16),

            // ============ DÉCONNEXION ============
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: ElevatedButton.icon(
                onPressed: () => _logout(context, ref),
                icon: Icon(Icons.logout),
                label: Text('Se déconnecter'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.red,
                  foregroundColor: Colors.white,
                  padding: EdgeInsets.symmetric(vertical: 16),
                  minimumSize: Size(double.infinity, 56),
                ),
              ),
            ),

            SizedBox(height: 16),

            // Version
            Text(
              'HouseConnect v1.0.0',
              style: TextStyle(
                fontSize: 12,
                color: Colors.grey[600],
              ),
            ),

            SizedBox(height: 32),
          ],
        ),
      ),
    );
  }

  Widget _buildDefaultAvatar(String name) {
    return Text(
      name.isNotEmpty ? name[0].toUpperCase() : '?',
      style: TextStyle(
        fontSize: 40,
        fontWeight: FontWeight.bold,
        color: AppColors.primary,
      ),
    );
  }

  Widget _buildSection(
    BuildContext context, {
    required String title,
    required List<Widget> children,
  }) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: AppColors.shadowSm,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.all(16),
            child: Text(
              title,
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.bold,
                color: Colors.grey[900],
              ),
            ),
          ),
          ...children,
        ],
      ),
    );
  }

  Widget _buildInfoTile({
    required IconData icon,
    required String title,
    required String value,
    VoidCallback? onTap,
  }) {
    return ListTile(
      leading: Icon(icon, color: AppColors.primary),
      title: Text(
        title,
        style: TextStyle(
          fontSize: 13,
          color: Colors.grey[600],
        ),
      ),
      subtitle: Text(
        value,
        style: TextStyle(
          fontSize: 16,
          fontWeight: FontWeight.w500,
          color: Colors.grey[900],
        ),
      ),
      trailing: Icon(Icons.edit, size: 20, color: Colors.grey[400]),
      onTap: onTap,
    );
  }

  Widget _buildMenuTile({
    required IconData icon,
    required String title,
    Widget? trailing,
    VoidCallback? onTap,
  }) {
    return ListTile(
      leading: Icon(icon, color: AppColors.primary),
      title: Text(
        title,
        style: TextStyle(
          fontSize: 16,
          fontWeight: FontWeight.w500,
        ),
      ),
      trailing: trailing ?? Icon(Icons.arrow_forward_ios, size: 16),
      onTap: onTap,
    );
  }

  void _editProfile(BuildContext context, WidgetRef ref, String field) {
    final currentValue = field == 'name'
        ? ref.read(authProvider).user?.name
        : field == 'email'
            ? ref.read(authProvider).user?.email
            : ref.read(authProvider).user?.phone;

    showDialog(
      context: context,
      builder: (context) {
        final controller = TextEditingController(text: currentValue ?? '');

        return AlertDialog(
          title: Text('Modifier ${field == 'name' ? 'le nom' : field == 'email' ? 'l\'email' : 'le téléphone'}'),
          content: TextField(
            controller: controller,
            autofocus: true,
            keyboardType: field == 'email'
                ? TextInputType.emailAddress
                : field == 'phone'
                    ? TextInputType.phone
                    : TextInputType.text,
            decoration: InputDecoration(
              labelText: field == 'name'
                  ? 'Nouveau nom'
                  : field == 'email'
                      ? 'Nouvel email'
                      : 'Nouveau téléphone',
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: Text('Annuler'),
            ),
            ElevatedButton(
              onPressed: () async {
                final newValue = controller.text.trim();
                if (newValue.isEmpty) return;

                final success = await ref.read(authProvider.notifier).updateProfile(
                      name: field == 'name' ? newValue : null,
                      email: field == 'email' ? newValue : null,
                      phone: field == 'phone' ? newValue : null,
                    );

                if (context.mounted) {
                  Navigator.pop(context);
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text(
                        success
                            ? 'Profil mis à jour'
                            : 'Erreur de mise à jour',
                      ),
                      backgroundColor: success ? Colors.green : Colors.red,
                    ),
                  );
                }
              },
              child: Text('Enregistrer'),
            ),
          ],
        );
      },
    );
  }

  void _changePassword(BuildContext context, WidgetRef ref) {
    showDialog(
      context: context,
      builder: (context) {
        final oldPasswordController = TextEditingController();
        final newPasswordController = TextEditingController();
        final confirmPasswordController = TextEditingController();

        return AlertDialog(
          title: Text('Changer le mot de passe'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextField(
                controller: oldPasswordController,
                obscureText: true,
                decoration: InputDecoration(
                  labelText: 'Ancien mot de passe',
                ),
              ),
              SizedBox(height: 16),
              TextField(
                controller: newPasswordController,
                obscureText: true,
                decoration: InputDecoration(
                  labelText: 'Nouveau mot de passe',
                ),
              ),
              SizedBox(height: 16),
              TextField(
                controller: confirmPasswordController,
                obscureText: true,
                decoration: InputDecoration(
                  labelText: 'Confirmer le mot de passe',
                ),
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: Text('Annuler'),
            ),
            ElevatedButton(
              onPressed: () {
                // TODO: Implémenter le changement de mot de passe
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(content: Text('Fonctionnalité bientôt disponible')),
                );
                Navigator.pop(context);
              },
              child: Text('Changer'),
            ),
          ],
        );
      },
    );
  }

  void _logout(BuildContext context, WidgetRef ref) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text('Se déconnecter'),
        content: Text('Êtes-vous sûr de vouloir vous déconnecter ?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: Text('Annuler'),
          ),
          ElevatedButton(
            onPressed: () async {
              await ref.read(authProvider.notifier).logout();
              if (context.mounted) {
                Navigator.pushNamedAndRemoveUntil(
                  context,
                  AppRouter.login,
                  (route) => false,
                );
              }
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.red,
            ),
            child: Text('Se déconnecter'),
          ),
        ],
      ),
    );
  }
}