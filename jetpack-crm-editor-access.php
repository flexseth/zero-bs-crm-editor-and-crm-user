<?php
/**
 * Plugin Name: Zero BS CRM Editor Access
 * Plugin URI: https://github.com/flexseth/zero-bs-crm-editor-and-crm-user
 * Description: Automatically grants Editor role capabilities to users with Zero BS CRM Admin (Full CRM Permissions) role, allowing them to manage both CRM data and WordPress posts.
 * Version: 1.0.1
 * Requires at least: 5.0
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * Author: Seth Miller
 * Author URI: https://flexperception.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: zero-bs-crm-editor-and-crm-user
 *
 * @package ZeroBSCRMEditorAccess
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'ZERO_BS_CRM_EDITOR_ACCESS_VERSION', '1.0.0' );
define( 'ZERO_BS_CRM_EDITOR_ACCESS_PLUGIN_FILE', __FILE__ );
define( 'ZERO_BS_CRM_EDITOR_ACCESS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ZERO_BS_CRM_EDITOR_ACCESS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main plugin class for Zero BS CRM Editor Access.
 *
 * @since 1.0.0
 */
class Zero_BS_CRM_Editor_Access {

	/**
	 * Plugin instance.
	 *
	 * @since 1.0.0
	 * @var Zero_BS_CRM_Editor_Access
	 */
	private static $instance = null;

	/**
	 * Known Zero BS CRM admin role names.
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
	 * @return Zero_BS_CRM_Editor_Access
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
		register_activation_hook( ZERO_BS_CRM_EDITOR_ACCESS_PLUGIN_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( ZERO_BS_CRM_EDITOR_ACCESS_PLUGIN_FILE, array( $this, 'deactivate' ) );
	}

	/**
	 * Initialize plugin.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		// WordPress automatically loads translations for plugins hosted on WordPress.org as of version 4.6.

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
		add_action( 'admin_post_zero_bs_crm_editor_settings', array( $this, 'handle_settings_form' ) );
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
		add_option( 'zero_bs_crm_editor_access_auto_grant', '1' );
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
		$remove_on_deactivate = get_option( 'zero_bs_crm_editor_access_remove_on_deactivate', '0' );
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
		do_action( 'zero_bs_crm_editor_access_granted', $user );

		return true;
	}

	/**
	 * Check if auto-grant is enabled.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	private function is_auto_grant_enabled() {
		return '1' === get_option( 'zero_bs_crm_editor_access_auto_grant', '1' );
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
			__( 'Zero BS CRM Editor Access', 'zero-bs-crm-editor-and-crm-user' ),
			__( 'CRM Editor Access', 'zero-bs-crm-editor-and-crm-user' ),
			'manage_options',
			'zero-bs-crm-editor-access',
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
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'zero-bs-crm-editor-and-crm-user' ) );
		}

		$auto_grant              = get_option( 'zero_bs_crm_editor_access_auto_grant', '1' );
		$remove_on_deactivate    = get_option( 'zero_bs_crm_editor_access_remove_on_deactivate', '0' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Zero BS CRM Editor Access Settings', 'zero-bs-crm-editor-and-crm-user' ); ?></h1>
			
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'zero_bs_crm_editor_settings', 'zero_bs_crm_editor_nonce' ); ?>
				<input type="hidden" name="action" value="zero_bs_crm_editor_settings" />
				
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="auto_grant"><?php esc_html_e( 'Auto-grant Editor Access', 'zero-bs-crm-editor-and-crm-user' ); ?></label>
						</th>
						<td>
							<input type="checkbox" id="auto_grant" name="auto_grant" value="1" <?php checked( '1', $auto_grant ); ?> />
							<label for="auto_grant"><?php esc_html_e( 'Automatically grant Editor role to users with CRM Admin permissions', 'zero-bs-crm-editor-and-crm-user' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="remove_on_deactivate"><?php esc_html_e( 'Remove on Deactivation', 'zero-bs-crm-editor-and-crm-user' ); ?></label>
						</th>
						<td>
							<input type="checkbox" id="remove_on_deactivate" name="remove_on_deactivate" value="1" <?php checked( '1', $remove_on_deactivate ); ?> />
							<label for="remove_on_deactivate"><?php esc_html_e( 'Remove Editor role from CRM users when plugin is deactivated', 'zero-bs-crm-editor-and-crm-user' ); ?></label>
						</td>
					</tr>
				</table>
				
				<?php submit_button(); ?>
			</form>
			
			<h2><?php esc_html_e( 'Plugin Information', 'zero-bs-crm-editor-and-crm-user' ); ?></h2>
			<p><?php esc_html_e( 'This plugin automatically grants Editor role capabilities to users who have Zero BS CRM Admin permissions. This allows CRM administrators to manage both customer data and WordPress content.', 'zero-bs-crm-editor-and-crm-user' ); ?></p>
			
			<h3><?php esc_html_e( 'Supported CRM Admin Roles', 'zero-bs-crm-editor-and-crm-user' ); ?></h3>
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
			wp_die( esc_html__( 'You do not have sufficient permissions to perform this action.', 'zero-bs-crm-editor-and-crm-user' ) );
		}

		if ( ! isset( $_POST['zero_bs_crm_editor_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['zero_bs_crm_editor_nonce'] ) ), 'zero_bs_crm_editor_settings' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'zero-bs-crm-editor-and-crm-user' ) );
		}

		$auto_grant           = isset( $_POST['auto_grant'] ) ? '1' : '0';
		$remove_on_deactivate = isset( $_POST['remove_on_deactivate'] ) ? '1' : '0';

		update_option( 'zero_bs_crm_editor_access_auto_grant', $auto_grant );
		update_option( 'zero_bs_crm_editor_access_remove_on_deactivate', $remove_on_deactivate );

		wp_safe_redirect( add_query_arg( 'updated', '1', admin_url( 'options-general.php?page=zero-bs-crm-editor-access' ) ) );
		exit;
	}
}

// Initialize the plugin.
Zero_BS_CRM_Editor_Access::get_instance();