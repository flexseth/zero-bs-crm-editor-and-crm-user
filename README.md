# Jetpack CRM Editor Access

A WordPress plugin that automatically grants Editor role capabilities to users with Jetpack CRM Admin (Full CRM Permissions) role, allowing them to manage both CRM data and WordPress content seamlessly.

## Description

This plugin bridges the gap between Jetpack CRM administration and WordPress content management by automatically granting Editor role capabilities to users who have Jetpack CRM Admin permissions. This eliminates the need to manually assign multiple roles and ensures CRM administrators can manage both customer relationships and website content.

## Features

- **Automatic Role Assignment**: Automatically grants Editor role to users with CRM Admin permissions
- **Multiple CRM Role Support**: Supports various Jetpack CRM admin role names
- **Security Focused**: Follows WordPress security best practices with proper nonce validation and capability checks
- **Accessibility Compliant**: Built with accessibility standards in mind
- **PHPCS Compliant**: Follows WordPress PHP Coding Standards
- **Admin Settings**: Configurable options through WordPress admin interface
- **Hook Integration**: Triggers on user login and role changes
- **Translation Ready**: Prepared for internationalization

## Supported CRM Admin Roles

The plugin recognizes the following Jetpack CRM admin role names:

- `zerobs_admin`
- `jetpack_crm_admin`
- `crm_admin`
- `zerobscrm_admin`

## Installation

### Manual Installation

1. Download the plugin files
2. Upload the `jetpack-crm-editor-access` folder to your `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Configure settings under Settings > CRM Editor Access

### Via WordPress Admin

1. Go to Plugins > Add New
2. Upload the plugin zip file
3. Activate the plugin
4. Configure settings under Settings > CRM Editor Access

## How It Works

### Automatic Detection and Assignment

The plugin works by:

1. **Monitoring User Events**: Hooks into WordPress user login and role change events
2. **Role Detection**: Checks if users have any of the supported CRM admin roles
3. **Capability Granting**: Automatically adds the Editor role to qualifying users
4. **Real-time Updates**: Processes changes immediately when roles are modified

### Technical Implementation

```php
// Example: When a user logs in
add_action( 'wp_login', array( $this, 'handle_user_login' ), 10, 2 );

// Example: When user roles change
add_action( 'set_user_role', array( $this, 'handle_role_change' ), 10, 3 );
add_action( 'add_user_role', array( $this, 'handle_role_addition' ), 10, 2 );
```

### Security Measures

- **Capability Checks**: All admin functions require `manage_options` capability
- **Nonce Validation**: All form submissions include WordPress nonce verification
- **Input Sanitization**: All user inputs are properly sanitized
- **Direct Access Prevention**: Plugin files cannot be accessed directly

## Configuration

### Settings Page

Access the settings page via **Settings > CRM Editor Access** in your WordPress admin.

#### Available Options

1. **Auto-grant Editor Access** (Default: Enabled)
   - Automatically grants Editor role to users with CRM Admin permissions
   - Can be disabled to prevent automatic role assignment

2. **Remove on Deactivation** (Default: Disabled)
   - When enabled, removes Editor role from CRM users when plugin is deactivated
   - Helps clean up role assignments if plugin is no longer needed

### Programmatic Configuration

You can also configure the plugin via code:

```php
// Disable auto-granting
update_option( 'jetpack_crm_editor_access_auto_grant', '0' );

// Enable removal on deactivation
update_option( 'jetpack_crm_editor_access_remove_on_deactivate', '1' );
```

## Hooks and Filters

### Actions

#### `jetpack_crm_editor_access_granted`
Fired after editor capabilities are granted to a CRM admin.

```php
/**
 * Do something after editor access is granted
 */
function my_custom_action( $user ) {
    // Log the event, send notification, etc.
    error_log( "Editor access granted to user: " . $user->user_login );
}
add_action( 'jetpack_crm_editor_access_granted', 'my_custom_action' );
```

## Frequently Asked Questions

### Q: What happens if I deactivate the plugin?

**A:** By default, users retain their Editor role when the plugin is deactivated. However, you can enable the "Remove on Deactivation" option in settings to automatically remove Editor roles from CRM users when the plugin is deactivated.

### Q: Will this work with custom CRM admin roles?

**A:** The plugin supports the most common Jetpack CRM admin role names. If you have custom role names, you may need to modify the `$crm_admin_roles` array in the plugin code.

### Q: Does this plugin modify the CRM admin role capabilities?

**A:** No, the plugin only adds the Editor role to users who already have CRM admin roles. It doesn't modify existing CRM capabilities.

### Q: Is this plugin translation-ready?

**A:** Yes, the plugin is prepared for internationalization with proper text domains and translation functions.

### Q: What if a user already has Editor role?

**A:** The plugin checks for existing Editor role before adding it, preventing duplicate role assignments.

## Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **Jetpack CRM**: Any version with admin roles

## Changelog

### 1.0.0
- Initial release
- Automatic Editor role assignment for CRM admins
- Admin settings interface
- Security and accessibility compliance
- Translation ready

## Support

For support, feature requests, or bug reports, please visit the plugin's GitHub repository or contact the plugin author.

## License

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## Contributing

Contributions are welcome! Please ensure all code follows WordPress coding standards and includes appropriate tests.

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## Security

If you discover any security vulnerabilities, please report them responsibly by contacting the plugin author directly rather than posting them publicly.