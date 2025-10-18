import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/constants/app_router.dart';
import '../../../data/providers/auth_provider.dart';
import '../../../data/providers/settings_provider.dart';

/// Écran des paramètres de l'application
class SettingsScreen extends ConsumerStatefulWidget {
  const SettingsScreen({super.key});

  @override
  ConsumerState<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends ConsumerState<SettingsScreen> {
  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authStateProvider);
    final settingsState = ref.watch(settingsProvider);
    final user = authState.user;

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Paramètres'),
        backgroundColor: Colors.white,
        foregroundColor: AppColors.textPrimary,
        elevation: 0,
      ),
      body: ListView(
        children: [
          // Profil utilisateur
          if (user != null) ...[
            Container(
              color: Colors.white,
              padding: const EdgeInsets.all(24),
              child: Row(
                children: [
                  CircleAvatar(
                    radius: 35,
                    backgroundColor: AppColors.primary.withOpacity(0.1),
                    child: Text(
                      user.firstName[0].toUpperCase(),
                      style: TextStyle(
                        fontSize: 28,
                        fontWeight: FontWeight.bold,
                        color: AppColors.primary,
                      ),
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          '${user.firstName} ${user.lastName}',
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                            color: AppColors.textPrimary,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          user.email,
                          style: TextStyle(
                            fontSize: 14,
                            color: AppColors.textSecondary,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 8,
                            vertical: 4,
                          ),
                          decoration: BoxDecoration(
                            color: AppColors.primary.withOpacity(0.1),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Text(
                            user.isClient ? 'Client' : 'Bailleur',
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w600,
                              color: AppColors.primary,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  IconButton(
                    icon: const Icon(Icons.edit),
                    onPressed: () {
                      // TODO: Éditer le profil
                    },
                  ),
                ],
              ),
            ),
            const SizedBox(height: 8),
          ],

          // Compte
          _buildSection(
            'Compte',
            [
              _buildSettingTile(
                icon: Icons.person_outline,
                title: 'Informations personnelles',
                onTap: () {
                  // TODO: Éditer les infos
                },
              ),
              _buildSettingTile(
                icon: Icons.lock_outline,
                title: 'Changer le mot de passe',
                onTap: () {
                  _showChangePasswordDialog();
                },
              ),
              _buildSettingTile(
                icon: Icons.phone_outlined,
                title: 'Numéro de téléphone',
                subtitle: user?.phone ?? '',
                onTap: () {
                  // TODO: Modifier le téléphone
                },
              ),
            ],
          ),

          const SizedBox(height: 8),

          // Notifications
          _buildSection(
            'Notifications',
            [
              settingsState.when(
                data: (settings) => _buildSwitchTile(
                  icon: Icons.notifications_outlined,
                  title: 'Notifications push',
                  subtitle: 'Recevoir des notifications',
                  value: settings['notifications_enabled'] ?? true,
                  onChanged: (value) {
                    ref.read(settingsProvider.notifier).updateSetting(
                          'notifications_enabled',
                          value,
                        );
                  },
                ),
                loading: () => const CircularProgressIndicator(),
                error: (_, __) => const SizedBox(),
              ),
              settingsState.when(
                data: (settings) => _buildSwitchTile(
                  icon: Icons.email_outlined,
                  title: 'Notifications par email',
                  subtitle: 'Recevoir des emails',
                  value: settings['email_notifications'] ?? true,
                  onChanged: (value) {
                    ref.read(settingsProvider.notifier).updateSetting(
                          'email_notifications',
                          value,
                        );
                  },
                ),
                loading: () => const SizedBox(),
                error: (_, __) => const SizedBox(),
              ),
            ],
          ),

          const SizedBox(height: 8),

          // Préférences
          _buildSection(
            'Préférences',
            [
              settingsState.when(
                data: (settings) => _buildSwitchTile(
                  icon: Icons.dark_mode_outlined,
                  title: 'Mode sombre',
                  subtitle: 'Activer le thème sombre',
                  value: settings['dark_mode'] ?? false,
                  onChanged: (value) {
                    ref.read(settingsProvider.notifier).updateSetting(
                          'dark_mode',
                          value,
                        );
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(
                        content: Text('Le mode sombre sera bientôt disponible'),
                      ),
                    );
                  },
                ),
                loading: () => const SizedBox(),
                error: (_, __) => const SizedBox(),
              ),
              _buildSettingTile(
                icon: Icons.language_outlined,
                title: 'Langue',
                subtitle: 'Français',
                onTap: () {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text('Les autres langues seront bientôt disponibles'),
                    ),
                  );
                },
              ),
            ],
          ),

          const SizedBox(height: 8),

          // À propos
          _buildSection(
            'À propos',
            [
              _buildSettingTile(
                icon: Icons.info_outline,
                title: 'À propos de HouseConnect',
                subtitle: 'Version 1.0.0',
                onTap: () {
                  _showAboutDialog();
                },
              ),
              _buildSettingTile(
                icon: Icons.privacy_tip_outlined,
                title: 'Politique de confidentialité',
                onTap: () {
                  // TODO: Afficher la politique
                },
              ),
              _buildSettingTile(
                icon: Icons.description_outlined,
                title: 'Conditions d\'utilisation',
                onTap: () {
                  // TODO: Afficher les CGU
                },
              ),
              _buildSettingTile(
                icon: Icons.help_outline,
                title: 'Aide et support',
                onTap: () {
                  // TODO: Support
                },
              ),
            ],
          ),

          const SizedBox(height: 8),

          // Déconnexion
          Container(
            color: Colors.white,
            child: _buildSettingTile(
              icon: Icons.logout,
              title: 'Se déconnecter',
              iconColor: Colors.red,
              titleColor: Colors.red,
              onTap: () {
                _showLogoutDialog();
              },
            ),
          ),

          const SizedBox(height: 24),
        ],
      ),
    );
  }

  Widget _buildSection(String title, List<Widget> children) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(24, 16, 24, 8),
          child: Text(
            title,
            style: TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w600,
              color: AppColors.textSecondary,
            ),
          ),
        ),
        Container(
          color: Colors.white,
          child: Column(children: children),
        ),
      ],
    );
  }

  Widget _buildSettingTile({
    required IconData icon,
    required String title,
    String? subtitle,
    Color? iconColor,
    Color? titleColor,
    required VoidCallback onTap,
  }) {
    return ListTile(
      leading: Icon(
        icon,
        color: iconColor ?? AppColors.primary,
      ),
      title: Text(
        title,
        style: TextStyle(
          fontSize: 16,
          fontWeight: FontWeight.w500,
          color: titleColor ?? AppColors.textPrimary,
        ),
      ),
      subtitle: subtitle != null
          ? Text(
              subtitle,
              style: TextStyle(
                fontSize: 13,
                color: AppColors.textSecondary,
              ),
            )
          : null,
      trailing: Icon(
        Icons.chevron_right,
        color: AppColors.textSecondary,
      ),
      onTap: onTap,
    );
  }

  Widget _buildSwitchTile({
    required IconData icon,
    required String title,
    String? subtitle,
    required bool value,
    required ValueChanged<bool> onChanged,
  }) {
    return SwitchListTile(
      secondary: Icon(
        icon,
        color: AppColors.primary,
      ),
      title: Text(
        title,
        style: const TextStyle(
          fontSize: 16,
          fontWeight: FontWeight.w500,
          color: AppColors.textPrimary,
        ),
      ),
      subtitle: subtitle != null
          ? Text(
              subtitle,
              style: TextStyle(
                fontSize: 13,
                color: AppColors.textSecondary,
              ),
            )
          : null,
      value: value,
      activeColor: AppColors.primary,
      onChanged: onChanged,
    );
  }

  void _showChangePasswordDialog() {
    final currentPasswordController = TextEditingController();
    final newPasswordController = TextEditingController();
    final confirmPasswordController = TextEditingController();

    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Changer le mot de passe'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: currentPasswordController,
              decoration: const InputDecoration(
                labelText: 'Mot de passe actuel',
                border: OutlineInputBorder(),
              ),
              obscureText: true,
            ),
            const SizedBox(height: 16),
            TextField(
              controller: newPasswordController,
              decoration: const InputDecoration(
                labelText: 'Nouveau mot de passe',
                border: OutlineInputBorder(),
              ),
              obscureText: true,
            ),
            const SizedBox(height: 16),
            TextField(
              controller: confirmPasswordController,
              decoration: const InputDecoration(
                labelText: 'Confirmer le nouveau mot de passe',
                border: OutlineInputBorder(),
              ),
              obscureText: true,
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Annuler'),
          ),
          ElevatedButton(
            onPressed: () {
              // TODO: Implémenter le changement de mot de passe
              Navigator.pop(context);
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text('Mot de passe changé avec succès'),
                  backgroundColor: Colors.green,
                ),
              );
            },
            child: const Text('Changer'),
          ),
        ],
      ),
    );
  }

  void _showAboutDialog() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('HouseConnect'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Version 1.0.0'),
            const SizedBox(height: 16),
            const Text(
              'HouseConnect est une plateforme de gestion immobilière qui facilite la recherche de logements et la gestion des biens au Gabon.',
              style: TextStyle(fontSize: 14),
            ),
            const SizedBox(height: 16),
            Text(
              '© 2025 HouseConnect. Tous droits réservés.',
              style: TextStyle(
                fontSize: 12,
                color: AppColors.textSecondary,
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Fermer'),
          ),
        ],
      ),
    );
  }

  void _showLogoutDialog() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Déconnexion'),
        content: const Text('Voulez-vous vraiment vous déconnecter ?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Annuler'),
          ),
          ElevatedButton(
            onPressed: () async {
              Navigator.pop(context);

              // Déconnexion via le provider
              await ref.read(authStateProvider.notifier).logout();

              if (!mounted) return;

              // Rediriger vers l'écran de login
              Navigator.of(context).pushNamedAndRemoveUntil(
                AppRouter.login,
                (route) => false,
              );
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.red,
            ),
            child: const Text('Se déconnecter'),
          ),
        ],
      ),
    );
  }
}