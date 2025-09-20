<?php
/**
 * Plugin Name: Jetpack CRM Editor Access
 * Plugin URI: https://github.com/yourusername/jetpack-crm-editor-access
 * Description: Automatically grants Editor role capabilities to users with Jetpack CRM Admin (Full CRM Permissions) role, allowing them to manage both CRM data and WordPress posts.
 * Version: 1.0.0
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: jetpack-crm-editor-access
 * Domain Path: /languages
 * Network: false
 *
 * @package JetpackCRMEditorAccess
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'JETPACK_CRM_EDITOR_ACCESS_VERSION', '1.0.0' );
define( 'JETPACK_CRM_EDITOR_ACCESS_PLUGIN_FILE', __FILE__ );
define( 'JETPACK_CRM_EDITOR_ACCESS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'JETPACK_CRM_EDITOR_ACCESS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main plugin class for Jetpack CRM Editor Access.
 *
 * @since 1.0.0
 */
class Jetpack_CRM_Editor_Access {

	/**
	 * Plugin instance.
	 *
	 * @since 1.0.0
	 * @var Jetpack_CRM_Editor_Access
	 */
	private static $instance = null;

	/**
	 * Known Jetpack CRM admin role names.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $crm_admin_roles = array(
		'zerobs_admin',
		'jetpack_crm_admin',
		'crm_admin',
		'zerobscrm_admin',
	);

	/**
	 * Get plugin instance.
	 *
	 * @since 1.0.0
	 * @return Jetpack_CRM_Editor_Access
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		register_activation_hook( JETPACK_CRM_EDITOR_ACCESS_PLUGIN_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( JETPACK_CRM_EDITOR_ACCESS_PLUGIN_FILE, array( $this, 'deactivate' ) );
	}

	/**
	 * Initialize plugin.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		// Load text domain for translations.
		load_plugin_textdomain( 'jetpack-crm-editor-access', false, dirname( plugin_basename( JETPACK_CRM_EDITOR_ACCESS_PLUGIN_FILE ) ) . '/languages' );

		// Hook into user login.
		add_action( 'wp_login', array( $this, 'handle_user_login' ), 10, 2 );

		// Hook into role changes.
		add_action( 'set_user_role', array( $this, 'handle_role_change' ), 10, 3 );
		add_action( 'add_user_role', array( $this, 'handle_role_addition' ), 10, 2 );

		// Check current user on admin init.
		add_action( 'admin_init', array( $this, 'check_current_user_access' ) );

		// Add admin menu for settings.
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

		// Handle admin form submission.
		add_action( 'admin_post_jetpack_crm_editor_settings', array( $this, 'handle_settings_form' ) );
	}

	/**
	 * Plugin activation.
	 *
	 * @since 1.0.0
	 */
	public function activate() {
		// Check if current user can activate plugins.
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		// Grant editor access to existing CRM admins.
		$this->grant_editor_access_to_existing_crm_admins();

		// Set default options.
		add_option( 'jetpack_crm_editor_access_auto_grant', '1' );
	}

	/**
	 * Plugin deactivation.
	 *
	 * @since 1.0.0
	 */
	public function deactivate() {
		// Check if current user can deactivate plugins.
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		// Optionally remove editor roles from CRM users on deactivation.
		$remove_on_deactivate = get_option( 'jetpack_crm_editor_access_remove_on_deactivate', '0' );
		if ( '1' === $remove_on_deactivate ) {
			$this->remove_editor_access_from_crm_users();
		}
	}

	/**
	 * Handle user login.
	 *
	 * @since 1.0.0
	 * @param string  $user_login Username.
	 * @param WP_User $user       User object.
	 */
	public function handle_user_login( $user_login, $user ) {
		if ( ! $this->is_auto_grant_enabled() ) {
			return;
		}

		if ( $this->user_has_crm_admin_role( $user ) ) {
			$this->grant_editor_capabilities( $user );
		}
	}

	/**
	 * Handle role change.
	 *
	 * @since 1.0.0
	 * @param int    $user_id   User ID.
	 * @param string $role      New role.
	 * @param array  $old_roles Previous roles.
	 */
	public function handle_role_change( $user_id, $role, $old_roles ) {
		if ( ! $this->is_auto_grant_enabled() ) {
			return;
		}

		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return;
		}

		if ( $this->user_has_crm_admin_role( $user ) ) {
			$this->grant_editor_capabilities( $user );
		}
	}

	/**
	 * Handle role addition.
	 *
	 * @since 1.0.0
	 * @param int    $user_id User ID.
	 * @param string $role    Added role.
	 */
	public function handle_role_addition( $user_id, $role ) {
		if ( ! $this->is_auto_grant_enabled() ) {
			return;
		}

		if ( in_array( $role, $this->crm_admin_roles, true ) ) {
			$user = get_user_by( 'id', $user_id );
			if ( $user ) {
				$this->grant_editor_capabilities( $user );
			}
		}
	}

	/**
	 * Check current user access on admin init.
	 *
	 * @since 1.0.0
	 */
	public function check_current_user_access() {
		if ( ! $this->is_auto_grant_enabled() ) {
			return;
		}

		$current_user = wp_get_current_user();
		if ( $this->user_has_crm_admin_role( $current_user ) ) {
			$this->grant_editor_capabilities( $current_user );
		}
	}

	/**
	 * Check if user has CRM admin role.
	 *
	 * @since 1.0.0
	 * @param WP_User $user User object.
	 * @return bool
	 */
	private function user_has_crm_admin_role( $user ) {
		if ( ! $user || ! ( $user instanceof WP_User ) ) {
			return false;
		}

		return ! empty( array_intersect( $this->crm_admin_roles, $user->roles ) );
	}

	/**
	 * Grant editor capabilities to user.
	 *
	 * @since 1.0.0
	 * @param WP_User $user User object.
	 * @return bool
	 */
	private function grant_editor_capabilities( $user ) {
		if ( ! $user || ! ( $user instanceof WP_User ) ) {
			return false;
		}

		// Check if user already has editor role.
		if ( in_array( 'editor', $user->roles, true ) ) {
			return true;
		}

		// Add editor role.
		$user->add_role( 'editor' );

		/**
		 * Fires after editor capabilities are granted to a CRM admin.
		 *
		 * @since 1.0.0
		 * @param WP_User $user User object.
		 */
		do_action( 'jetpack_crm_editor_access_granted', $user );

		return true;
	}

	/**
	 * Check if auto-grant is enabled.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	private function is_auto_grant_enabled() {
		return '1' === get_option( 'jetpack_crm_editor_access_auto_grant', '1' );
	}

	/**
	 * Grant editor access to existing CRM admins.
	 *
	 * @since 1.0.0
	 */
	private function grant_editor_access_to_existing_crm_admins() {
		$users = get_users(
			array(
				'role__in' => $this->crm_admin_roles,
				'fields'   => 'all',
			)
		);

		foreach ( $users as $user ) {
			$this->grant_editor_capabilities( $user );
		}
	}

	/**
	 * Remove editor access from CRM users.
	 *
	 * @since 1.0.0
	 */
	private function remove_editor_access_from_crm_users() {
		$users = get_users(
			array(
				'role__in' => $this->crm_admin_roles,
				'fields'   => 'all',
			)
		);

		foreach ( $users as $user ) {
			if ( in_array( 'editor', $user->roles, true ) ) {
				$user->remove_role( 'editor' );
			}
		}
	}

	/**
	 * Add admin menu.
	 *
	 * @since 1.0.0
	 */
	public function add_admin_menu() {
		add_options_page(
			__( 'Jetpack CRM Editor Access', 'jetpack-crm-editor-access' ),
			__( 'CRM Editor Access', 'jetpack-crm-editor-access' ),
			'manage_options',
			'jetpack-crm-editor-access',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Render settings page.
	 *
	 * @since 1.0.0
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'jetpack-crm-editor-access' ) );
		}

		$auto_grant              = get_option( 'jetpack_crm_editor_access_auto_grant', '1' );
		$remove_on_deactivate    = get_option( 'jetpack_crm_editor_access_remove_on_deactivate', '0' );
		$nonce                   = wp_create_nonce( 'jetpack_crm_editor_settings' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Jetpack CRM Editor Access Settings', 'jetpack-crm-editor-access' ); ?></h1>
			
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'jetpack_crm_editor_settings', 'jetpack_crm_editor_nonce' ); ?>
				<input type="hidden" name="action" value="jetpack_crm_editor_settings" />
				
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="auto_grant"><?php esc_html_e( 'Auto-grant Editor Access', 'jetpack-crm-editor-access' ); ?></label>
						</th>
						<td>
							<input type="checkbox" id="auto_grant" name="auto_grant" value="1" <?php checked( '1', $auto_grant ); ?> />
							<label for="auto_grant"><?php esc_html_e( 'Automatically grant Editor role to users with CRM Admin permissions', 'jetpack-crm-editor-access' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="remove_on_deactivate"><?php esc_html_e( 'Remove on Deactivation', 'jetpack-crm-editor-access' ); ?></label>
						</th>
						<td>
							<input type="checkbox" id="remove_on_deactivate" name="remove_on_deactivate" value="1" <?php checked( '1', $remove_on_deactivate ); ?> />
							<label for="remove_on_deactivate"><?php esc_html_e( 'Remove Editor role from CRM users when plugin is deactivated', 'jetpack-crm-editor-access' ); ?></label>
						</td>
					</tr>
				</table>
				
				<?php submit_button(); ?>
			</form>
			
			<h2><?php esc_html_e( 'Plugin Information', 'jetpack-crm-editor-access' ); ?></h2>
			<p><?php esc_html_e( 'This plugin automatically grants Editor role capabilities to users who have Jetpack CRM Admin permissions. This allows CRM administrators to manage both customer data and WordPress content.', 'jetpack-crm-editor-access' ); ?></p>
			
			<h3><?php esc_html_e( 'Supported CRM Admin Roles', 'jetpack-crm-editor-access' ); ?></h3>
			<ul>
				<?php foreach ( $this->crm_admin_roles as $role ) : ?>
					<li><code><?php echo esc_html( $role ); ?></code></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}

	/**
	 * Handle settings form submission.
	 *
	 * @since 1.0.0
	 */
	public function handle_settings_form() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to perform this action.', 'jetpack-crm-editor-access' ) );
		}

		if ( ! wp_verify_nonce( $_POST['jetpack_crm_editor_nonce'], 'jetpack_crm_editor_settings' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'jetpack-crm-editor-access' ) );
		}

		$auto_grant           = isset( $_POST['auto_grant'] ) ? '1' : '0';
		$remove_on_deactivate = isset( $_POST['remove_on_deactivate'] ) ? '1' : '0';

		update_option( 'jetpack_crm_editor_access_auto_grant', $auto_grant );
		update_option( 'jetpack_crm_editor_access_remove_on_deactivate', $remove_on_deactivate );

		wp_safe_redirect( add_query_arg( 'updated', '1', admin_url( 'options-general.php?page=jetpack-crm-editor-access' ) ) );
		exit;
	}
}

// Initialize the plugin.
Jetpack_CRM_Editor_Access::get_instance();