=== Zero BS CRM Editor Access ===
Contributors: flexseth
Tags: crm, editor, permissions, zero-bs-crm, user-roles
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.0.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Grants Editor access to Zero BS CRM Admins for managing posts and pages alongside CRM data.

== Description ==

Zero BS CRM Editor Access automatically grants Editor role capabilities to users with Zero BS CRM Admin (Full CRM Permissions) role, allowing them to manage both CRM data and WordPress posts seamlessly.

This plugin bridges the gap between Zero BS CRM administration and WordPress content management by automatically granting Editor role capabilities to users who have Zero BS CRM Admin permissions. This eliminates the need to manually assign multiple roles and ensures CRM administrators can manage both customer relationships and website content.

= Features =

* **Automatic Role Assignment**: Automatically grants Editor role to users with CRM Admin permissions
* **Multiple CRM Role Support**: Supports various Zero BS CRM admin role names
* **Security Focused**: Follows WordPress security best practices with proper nonce validation and capability checks
* **Accessibility Compliant**: Built with accessibility standards in mind
* **PHPCS Compliant**: Follows WordPress PHP Coding Standards
* **Admin Settings**: Configurable options through WordPress admin interface
* **Hook Integration**: Triggers on user login and role changes
* **Translation Ready**: Prepared for internationalization

= Supported CRM Admin Roles =

The plugin recognizes the following Zero BS CRM admin role names:

* `zerobs_admin`
* `jetpack_crm_admin`
* `crm_admin`
* `zerobscrm_admin`

= How It Works =

The plugin works by:

1. **Monitoring User Events**: Hooks into WordPress user login and role change events
2. **Role Detection**: Checks if users have any of the supported CRM admin roles
3. **Capability Granting**: Automatically adds the Editor role to qualifying users
4. **Real-time Updates**: Processes changes immediately when roles are modified

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/zero-bs-crm-editor-and-crm-user/` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Settings > CRM Editor Access screen to configure the plugin

== Frequently Asked Questions ==

= What happens if I deactivate the plugin? =

By default, users retain their Editor role when the plugin is deactivated. However, you can enable the "Remove on Deactivation" option in settings to automatically remove Editor roles from CRM users when the plugin is deactivated.

= Will this work with custom CRM admin roles? =

The plugin supports the most common Zero BS CRM admin role names. If you have custom role names, you may need to modify the plugin code.

= Does this plugin modify the CRM admin role capabilities? =

No, the plugin only adds the Editor role to users who already have CRM admin roles. It doesn't modify existing CRM capabilities.

= What if a user already has Editor role? =

The plugin checks for existing Editor role before adding it, preventing duplicate role assignments.

== Screenshots ==

1. Settings page showing configuration options
2. Plugin automatically grants Editor access to CRM admins

== Changelog ==

= 1.0.0 =
* Initial release
* Automatic Editor role assignment for CRM admins
* Admin settings interface
* Security and accessibility compliance
* Translation ready

== Upgrade Notice ==

= 1.0.0 =
Initial release of Zero BS CRM Editor Access plugin.
