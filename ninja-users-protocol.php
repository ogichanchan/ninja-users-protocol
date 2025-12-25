<?php
/**
 * Plugin Name: Ninja Users Protocol
 * Plugin URI: https://github.com/ogichanchan/ninja-users-protocol
 * Description: A unique PHP-only WordPress utility. A ninja style users plugin acting as a protocol. Focused on simplicity and efficiency.
 * Version: 1.0.0
 * Author: ogichanchan
 * Author URI: https://github.com/ogichanchan
 * License: GPLv2 or later
 * Text Domain: ninja-users-protocol
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class for Ninja Users Protocol.
 * Manages the admin interface and user meta for "Ninja Status".
 */
class Ninja_Users_Protocol {

	/**
	 * Constructor.
	 * Registers necessary hooks for plugin functionality.
	 */
	public function __construct() {
		// Add admin menu page under the 'Users' section.
		add_action( 'admin_menu', array( $this, 'add_admin_menu_page' ) );

		// Hook into admin_init to process form submissions before header is sent.
		add_action( 'admin_init', array( $this, 'process_form_submission' ) );

		// Display admin notices for success or error messages.
		add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
	}

	/**
	 * Adds the "Ninja Protocol" submenu page to the WordPress admin 'Users' menu.
	 */
	public function add_admin_menu_page() {
		add_users_page(
			esc_html__( 'Ninja Users Protocol', 'ninja-users-protocol' ), // Page title.
			esc_html__( 'Ninja Protocol', 'ninja-users-protocol' ),       // Menu title.
			'manage_options',                                             // Capability required to access.
			'ninja-users-protocol',                                       // Menu slug.
			array( $this, 'render_admin_page' )                           // Callback function to render the page content.
		);
	}

	/**
	 * Renders the content of the Ninja Users Protocol admin page.
	 * Displays a list of users and allows managing their "Ninja Status".
	 */
	public function render_admin_page() {
		// Verify user capability before rendering the page.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'ninja-users-protocol' ) );
		}

		// Inline CSS for basic styling of the admin page.
		echo '<style type="text/css">';
		echo '
		.nu-protocol-container {
			margin-right: 20px;
			max-width: 960px; /* Constrain width for better readability */
		}
		.nu-protocol-table {
			width: 100%;
			border-collapse: collapse;
			margin-top: 20px;
		}
		.nu-protocol-table th,
		.nu-protocol-table td {
			border: 1px solid #ddd;
			padding: 8px;
			text-align: left;
			vertical-align: middle;
		}
		.nu-protocol-table th {
			background-color: #f2f2f2;
			font-weight: bold;
		}
		.nu-protocol-status-select {
			min-width: 120px;
			padding: 5px;
			border-radius: 3px;
			border: 1px solid #c3c4c7;
		}
		.nu-protocol-submit-button {
			margin-top: 15px;
		}
		.nu-protocol-actions-col {
			width: 160px; /* Adjust width for the select dropdown */
		}
		';
		echo '</style>';

		// Retrieve all users to display in the table.
		$users = get_users();
		?>
		<div class="wrap nu-protocol-container">
			<h1><?php esc_html_e( 'Ninja Users Protocol', 'ninja-users-protocol' ); ?></h1>
			<p><?php esc_html_e( 'Manage the "Ninja Status" for users on your site. This status is a custom attribute controlled by the Ninja Users Protocol.', 'ninja-users-protocol' ); ?></p>

			<form method="post" action="">
				<?php
				// Output a nonce field for security against CSRF attacks.
				wp_nonce_field( 'nu_protocol_update_ninja_status', 'nu_protocol_nonce' );
				?>
				<table class="wp-list-table widefat fixed striped nu-protocol-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'ID', 'ninja-users-protocol' ); ?></th>
							<th><?php esc_html_e( 'Username', 'ninja-users-protocol' ); ?></th>
							<th><?php esc_html_e( 'Email', 'ninja-users-protocol' ); ?></th>
							<th><?php esc_html_e( 'Roles', 'ninja-users-protocol' ); ?></th>
							<th class="nu-protocol-actions-col"><?php esc_html_e( 'Ninja Status', 'ninja-users-protocol' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! empty( $users ) ) : ?>
							<?php foreach ( $users as $user ) : ?>
								<?php
								// Get current ninja status for the user, default to 'pending_ninja' if not set.
								$ninja_status = get_user_meta( $user->ID, 'nu_protocol_ninja_status', true );
								if ( empty( $ninja_status ) ) {
									$ninja_status = 'pending_ninja'; // Default status.
								}
								?>
								<tr>
									<td><?php echo absint( $user->ID ); ?></td>
									<td><?php echo esc_html( $user->user_login ); ?></td>
									<td><?php echo esc_html( $user->user_email ); ?></td>
									<td><?php echo esc_html( implode( ', ', $user->roles ) ); ?></td>
									<td>
										<select name="nu_protocol_ninja_status[<?php echo absint( $user->ID ); ?>]" class="nu-protocol-status-select">
											<option value="active_ninja" <?php selected( $ninja_status, 'active_ninja' ); ?>>
												<?php esc_html_e( 'Active Ninja', 'ninja-users-protocol' ); ?>
											</option>
											<option value="inactive_ninja" <?php selected( $ninja_status, 'inactive_ninja' ); ?>>
												<?php esc_html_e( 'Inactive Ninja', 'ninja-users-protocol' ); ?>
											</option>
											<option value="pending_ninja" <?php selected( $ninja_status, 'pending_ninja' ); ?>>
												<?php esc_html_e( 'Pending Ninja', 'ninja-users-protocol' ); ?>
											</option>
										</select>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="5"><?php esc_html_e( 'No users found.', 'ninja-users-protocol' ); ?></td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
				<?php
				// Submit button for the form.
				submit_button( esc_html__( 'Update Ninja Status', 'ninja-users-protocol' ), 'primary nu-protocol-submit-button' );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Processes the form submission for updating user ninja status.
	 * This function is hooked into `admin_init`.
	 */
	public function process_form_submission() {
		// Only proceed if it's an admin request, a POST request, and our nonce field is present.
		if ( ! is_admin() || ! isset( $_POST['nu_protocol_nonce'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Verify the nonce to ensure the request originated from our form.
		if ( ! wp_verify_nonce( sanitize_key( $_POST['nu_protocol_nonce'] ), 'nu_protocol_update_ninja_status' ) ) {
			add_settings_error( 'nu_protocol_messages', 'nu_protocol_error', esc_html__( 'Nonce verification failed. Please try again.', 'ninja-users-protocol' ), 'error' );
			return;
		}

		// Check if the ninja status data is submitted and is an array.
		if ( isset( $_POST['nu_protocol_ninja_status'] ) && is_array( $_POST['nu_protocol_ninja_status'] ) ) {
			$updated_count = 0;
			$allowed_statuses = array( 'active_ninja', 'inactive_ninja', 'pending_ninja' );

			foreach ( $_POST['nu_protocol_ninja_status'] as $user_id => $status ) {
				$user_id = absint( $user_id ); // Sanitize user ID.
				$status  = sanitize_text_field( wp_unslash( $status ) ); // Sanitize status input.

				// Validate status against allowed values.
				if ( ! in_array( $status, $allowed_statuses, true ) ) {
					continue; // Skip invalid status values.
				}

				// Update user meta data.
				// update_user_meta returns true if the meta was added or updated, false if it was unchanged.
				if ( update_user_meta( $user_id, 'nu_protocol_ninja_status', $status ) ) {
					$updated_count++;
				}
			}

			// Add admin notices based on the outcome of the update.
			if ( $updated_count > 0 ) {
				add_settings_error( 'nu_protocol_messages', 'nu_protocol_success', sprintf( esc_html__( '%d user(s) ninja status updated successfully.', 'ninja-users-protocol' ), $updated_count ), 'success' );
			} else {
				add_settings_error( 'nu_protocol_messages', 'nu_protocol_no_change', esc_html__( 'No ninja status changes were made or saved, or an error occurred.', 'ninja-users-protocol' ), 'info' );
			}
		}
	}

	/**
	 * Displays admin notices (success/error/info messages).
	 * This function is hooked into `admin_notices`.
	 */
	public function display_admin_notices() {
		settings_errors( 'nu_protocol_messages' );
	}
}

/**
 * Instantiates the main plugin class.
 * This function is hooked into `plugins_loaded` to ensure all WordPress functions are available.
 */
function ninja_users_protocol_run() {
	new Ninja_Users_Protocol();
}
add_action( 'plugins_loaded', 'ninja_users_protocol_run' );

/**
 * Activation hook callback.
 * This function runs when the plugin is activated.
 * It ensures that all existing users have a default 'pending_ninja' status if it's not already set.
 */
function ninja_users_protocol_activate() {
	$users = get_users( array( 'fields' => 'ID' ) ); // Get only user IDs for efficiency.
	foreach ( $users as $user_id ) {
		// Check if the 'nu_protocol_ninja_status' meta field exists for the user.
		// If it's an empty string, it means the meta key does not exist or has no value.
		if ( '' === get_user_meta( $user_id, 'nu_protocol_ninja_status', true ) ) {
			// Set a default 'pending_ninja' status for users who don't have one.
			update_user_meta( $user_id, 'nu_protocol_ninja_status', 'pending_ninja' );
		}
	}
}
register_activation_hook( __FILE__, 'ninja_users_protocol_activate' );

/*
 * Deactivation Hook (Optional - for this plugin, the 'protocol' leaves its mark)
 *
 * This section is commented out to comply with the "ninja style" plugin description
 * which suggests the "protocol" leaves its mark. If desired, uncomment to remove
 * the custom user meta upon deactivation.
 *
 * function ninja_users_protocol_deactivate() {
 *     $users = get_users( array( 'fields' => 'ID' ) );
 *     foreach ( $users as $user_id ) {
 *         // Delete the custom 'nu_protocol_ninja_status' user meta.
 *         delete_user_meta( $user_id, 'nu_protocol_ninja_status' );
 *     }
 * }
 * register_deactivation_hook( __FILE__, 'ninja_users_protocol_deactivate' );
 */
?>