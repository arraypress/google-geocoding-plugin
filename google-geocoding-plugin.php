<?php
/**
 * ArrayPress - Google Geocoding Tester
 *
 * @package     ArrayPress\Google\Geocoding
 * @author      David Sherlock
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @link        https://arraypress.com/
 * @since       1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:         ArrayPress - Google Geocoding Tester
 * Plugin URI:          https://github.com/arraypress/google-geocoding-plugin
 * Description:         A plugin to test and demonstrate the Google Geocoding API integration.
 * Version:             1.0.0
 * Requires at least:   6.7.1
 * Requires PHP:        7.4
 * Author:              David Sherlock
 * Author URI:          https://arraypress.com/
 * License:             GPL v2 or later
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:         arraypress-google-geocoding
 * Domain Path:         /languages
 * Network:             false
 * Update URI:          false
 */

declare( strict_types=1 );

namespace ArrayPress\Google\Geocoding;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/vendor/autoload.php';

class Plugin {

	/**
	 * API Client instance
	 *
	 * @var Client|null
	 */
	private ?Client $client = null;

	/**
	 * Hook name for the admin page.
	 *
	 * @var string
	 */
	const MENU_HOOK = 'google_page_arraypress-google-geocoding';

	/**
	 * Plugin constructor
	 */
	public function __construct() {
		// Load text domain for translations
		add_action( 'init', [ $this, 'load_textdomain' ] );

		// Admin hooks
		add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

		// Initialize client if API key exists
		$api_key = get_option( 'google_geocoding_api_key' );
		if ( ! empty( $api_key ) ) {
			$this->client = new Client(
				$api_key,
				(bool) get_option( 'google_geocoding_enable_cache', true ),
				(int) get_option( 'google_geocoding_cache_duration', 86400 )
			);
		}
	}

	/**
	 * Load plugin textdomain
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'arraypress-google-geocoding',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}

	/**
	 * Enqueue admin assets
	 */
	public function enqueue_admin_assets( $hook ): void {
		if ( $hook !== self::MENU_HOOK ) {
			return;
		}

		wp_enqueue_style(
			'google-geocoding-test-admin',
			plugins_url( 'assets/css/admin.css', __FILE__ ),
			[],
			'1.0.0'
		);

		wp_enqueue_script(
			'google-geocoding-test-admin',
			plugins_url( 'assets/js/admin.js', __FILE__ ),
			[ 'jquery' ],
			'1.0.0',
			true
		);
	}

	/**
	 * Registers the Google menu and timezone detection submenu page in the WordPress admin.
	 *
	 * This method handles the creation of a shared Google menu across plugins (if it doesn't
	 * already exist) and adds the Timezone Detection tool as a submenu item. It also removes
	 * the default submenu item to prevent a blank landing page.
	 *
	 * @return void
	 */
	public function add_menu_page(): void {
		// Only add the main Google menu if it doesn't exist yet
		global $admin_page_hooks;

		if ( ! isset( $admin_page_hooks['arraypress-google'] ) ) {
			add_menu_page(
				__( 'Google', 'arraypress-google-address-validation' ),
				__( 'Google', 'arraypress-google-address-validation' ),
				'manage_options',
				'arraypress-google',
				null,
				'dashicons-google',
				30
			);
		}

		// Add the address validation submenu
		add_submenu_page(
			'arraypress-google',
			__( 'Geocoding', 'arraypress-google-address-validation' ),
			__( 'Geocoding', 'arraypress-google-address-validation' ),
			'manage_options',
			'arraypress-google-geocoding',
			[ $this, 'render_test_page' ]
		);
	}

	/**
	 * Register settings
	 */
	public function register_settings(): void {
		register_setting( 'geocoding_test_settings', 'google_geocoding_api_key' );
		register_setting( 'geocoding_test_settings', 'google_geocoding_enable_cache', 'bool' );
		register_setting( 'geocoding_test_settings', 'google_geocoding_cache_duration', 'int' );
	}

	/**
	 * Process form submissions
	 */
	private function process_form_submissions(): array {
		$results = [
			'geocoding' => null,
			'reverse'   => null,
			'place_id'  => null
		];

		if ( isset( $_POST['submit_api_key'] ) ) {
			check_admin_referer( 'geocoding_test_api_key' );
			$api_key        = sanitize_text_field( $_POST['google_geocoding_api_key'] );
			$enable_cache   = isset( $_POST['google_geocoding_enable_cache'] );
			$cache_duration = (int) sanitize_text_field( $_POST['google_geocoding_cache_duration'] );

			update_option( 'google_geocoding_api_key', $api_key );
			update_option( 'google_geocoding_enable_cache', $enable_cache );
			update_option( 'google_geocoding_cache_duration', $cache_duration );

			$this->client = new Client( $api_key, $enable_cache, $cache_duration );
		}

		if ( ! $this->client ) {
			return $results;
		}

		// Process geocoding test
		if ( isset( $_POST['submit_google_geocoding'] ) && isset( $_POST['address'] ) ) {
			check_admin_referer( 'geocoding_test' );
			$results['geocoding'] = $this->client->geocode(
				sanitize_text_field( $_POST['address'] )
			);
		}

		// Process reverse geocoding test
		if ( isset( $_POST['submit_reverse'] ) && isset( $_POST['latitude'] ) && isset( $_POST['longitude'] ) ) {
			check_admin_referer( 'geocoding_test' );
			$results['reverse'] = $this->client->reverse_geocode(
				(float) sanitize_text_field( $_POST['latitude'] ),
				(float) sanitize_text_field( $_POST['longitude'] )
			);
		}

		// Clear cache if requested
		if ( isset( $_POST['clear_cache'] ) ) {
			check_admin_referer( 'geocoding_test' );
			$this->client->clear_cache();
			add_settings_error(
				'geocoding_test',
				'cache_cleared',
				__( 'Cache cleared successfully', 'arraypress-google-geocoding' ),
				'success'
			);
		}

		return $results;
	}

	/**
	 * Render location details
	 */
	private function render_location_details( $result ): void {
		if ( is_wp_error( $result ) ) {
			?>
            <div class="notice notice-error">
                <p><?php echo esc_html( $result->get_error_message() ); ?></p>
            </div>
			<?php
			return;
		}
		?>
        <table class="widefat striped">
            <tbody>
            <tr>
                <th><?php _e( 'Formatted Address', 'arraypress-google-geocoding' ); ?></th>
                <td><?php echo esc_html( $result->get_formatted_address() ); ?></td>
            </tr>
            <tr>
                <th><?php _e( 'Coordinates', 'arraypress-google-geocoding' ); ?></th>
                <td>
					<?php
					$coords = $result->get_coordinates();
					if ( $coords ) {
						printf(
						/* translators: 1: Latitude 2: Longitude */
							__( 'Lat: %1$s, Lng: %2$s', 'arraypress-google-geocoding' ),
							esc_html( $coords['latitude'] ),
							esc_html( $coords['longitude'] )
						);
					} else {
						_e( 'N/A', 'arraypress-google-geocoding' );
					}
					?>
                </td>
            </tr>
            <tr>
                <th><?php _e( 'Place ID', 'arraypress-google-geocoding' ); ?></th>
                <td><?php echo esc_html( $result->get_place_id() ); ?></td>
            </tr>
            <tr>
                <th><?php _e( 'Location Type', 'arraypress-google-geocoding' ); ?></th>
                <td><?php echo esc_html( $result->get_location_type() ); ?></td>
            </tr>
            <tr>
                <th><?php _e( 'Place Types', 'arraypress-google-geocoding' ); ?></th>
                <td><?php echo esc_html( implode( ', ', $result->get_types() ) ); ?></td>
            </tr>
            <tr>
                <th><?php _e( 'Plus Code', 'arraypress-google-geocoding' ); ?></th>
                <td>
					<?php
					$plus_code = $result->get_plus_code();
					if ( $plus_code ) {
						echo sprintf(
						/* translators: 1: Compound code 2: Global code */
							__( 'Compound: %1$s<br>Global: %2$s', 'arraypress-google-geocoding' ),
							esc_html( $plus_code['compound_code'] ),
							esc_html( $plus_code['global_code'] )
						);
					} else {
						_e( 'N/A', 'arraypress-google-geocoding' );
					}
					?>
                </td>
            </tr>
            <tr>
                <th><?php _e( 'Viewport', 'arraypress-google-geocoding' ); ?></th>
                <td>
					<?php
					$viewport = $result->get_viewport();
					if ( $viewport ) {
						echo sprintf(
						/* translators: 1: Northeast lat 2: Northeast lng 3: Southwest lat 4: Southwest lng */
							__( 'NE: %1$s, %2$s<br>SW: %3$s, %4$s', 'arraypress-google-geocoding' ),
							esc_html( $viewport['northeast']['lat'] ),
							esc_html( $viewport['northeast']['lng'] ),
							esc_html( $viewport['southwest']['lat'] ),
							esc_html( $viewport['southwest']['lng'] )
						);
					} else {
						_e( 'N/A', 'arraypress-google-geocoding' );
					}
					?>
                </td>
            </tr>
            <tr>
                <th><?php _e( 'Address Components', 'arraypress-google-geocoding' ); ?></th>
                <td>
					<?php
					$address = $result->get_structured_address();
					echo '<dl class="address-components">';
					foreach ( $address as $key => $value ) {
						if ( $value ) {
							printf(
								'<dt>%s:</dt><dd>%s</dd>',
								esc_html( ucwords( str_replace( '_', ' ', $key ) ) ),
								esc_html( $value )
							);
						}
					}
					echo '</dl>';
					?>
                </td>
            </tr>
            <tr>
                <th><?php _e( 'Is Business Location', 'arraypress-google-geocoding' ); ?></th>
                <td>
					<?php echo $result->is_business_location()
						? __( 'Yes', 'arraypress-google-geocoding' )
						: __( 'No', 'arraypress-google-geocoding' ); ?>
                </td>
            </tr>
            <tr>
                <th><?php _e( 'Is Partial Match', 'arraypress-google-geocoding' ); ?></th>
                <td>
					<?php echo $result->is_partial_match()
						? __( 'Yes', 'arraypress-google-geocoding' )
						: __( 'No', 'arraypress-google-geocoding' ); ?>
                </td>
            </tr>
            </tbody>
        </table>
		<?php
	}

	/**
	 * Render test page
	 */
	public function render_test_page(): void {
		$results = $this->process_form_submissions();
		?>
        <div class="wrap geocoding-test">
            <h1><?php _e( 'Google Geocoding API Test', 'arraypress-google-geocoding' ); ?></h1>

			<?php settings_errors( 'geocoding_test' ); ?>

			<?php if ( empty( get_option( 'google_geocoding_api_key' ) ) ): ?>
                <!-- API Key Form -->
                <div class="notice notice-warning">
                    <p><?php _e( 'Please enter your Google Geocoding API key to begin testing.', 'arraypress-google-geocoding' ); ?></p>
                </div>
				<?php $this->render_settings_form(); ?>
			<?php else: ?>
                <!-- Test Forms -->
                <div class="geocoding-test-container">
                    <!-- Forward Geocoding -->
                    <div class="geocoding-test-section">
                        <h2><?php _e( 'Forward Geocoding', 'arraypress-google-geocoding' ); ?></h2>
                        <form method="post" class="geocoding-form">
							<?php wp_nonce_field( 'geocoding_test' ); ?>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="address"><?php _e( 'Address', 'arraypress-google-geocoding' ); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" name="address" id="address" class="regular-text"
                                               value="1600 Amphitheatre Parkway, Mountain View, CA"
                                               placeholder="<?php esc_attr_e( 'Enter address...', 'arraypress-google-geocoding' ); ?>">
                                    </td>
                                </tr>
                            </table>
							<?php submit_button( __( 'Test Geocoding', 'arraypress-google-geocoding' ), 'secondary', 'submit_google_geocoding' ); ?>
                        </form>

						<?php if ( $results['geocoding'] ): ?>
                            <h3><?php _e( 'Results', 'arraypress-google-geocoding' ); ?></h3>
							<?php $this->render_location_details( $results['geocoding'] ); ?>
						<?php endif; ?>
                    </div>

                    <!-- Reverse Geocoding -->
                    <div class="geocoding-test-section">
                        <h2><?php _e( 'Reverse Geocoding', 'arraypress-google-geocoding' ); ?></h2>
                        <form method="post" class="geocoding-form">
							<?php wp_nonce_field( 'geocoding_test' ); ?>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="latitude"><?php _e( 'Latitude', 'arraypress-google-geocoding' ); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" name="latitude" id="latitude" class="regular-text"
                                               value="37.4220"
                                               placeholder="<?php esc_attr_e( 'Enter latitude...', 'arraypress-google-geocoding' ); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="longitude"><?php _e( 'Longitude', 'arraypress-google-geocoding' ); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" name="longitude" id="longitude" class="regular-text"
                                               value="-122.0841"
                                               placeholder="<?php esc_attr_e( 'Enter longitude...', 'arraypress-google-geocoding' ); ?>">
                                    </td>
                                </tr>
                            </table>
							<?php submit_button( __( 'Test Reverse Geocoding', 'arraypress-google-geocoding' ), 'secondary', 'submit_reverse' ); ?>
                        </form>

						<?php if ( $results['reverse'] ): ?>
                            <h3><?php _e( 'Results', 'arraypress-google-geocoding' ); ?></h3>
							<?php $this->render_location_details( $results['reverse'] ); ?>
						<?php endif; ?>
                    </div>
                </div>

                <!-- Cache Management -->
                <div class="geocoding-test-section">
                    <h2><?php _e( 'Cache Management', 'arraypress-google-geocoding' ); ?></h2>
                    <form method="post" class="geocoding-form">
						<?php wp_nonce_field( 'geocoding_test' ); ?>
                        <p class="description">
							<?php _e( 'Clear the cached geocoding results. This will force new API requests for subsequent lookups.', 'arraypress-google-geocoding' ); ?>
                        </p>
						<?php submit_button( __( 'Clear Cache', 'arraypress-google-geocoding' ), 'delete', 'clear_cache' ); ?>
                    </form>
                </div>

                <!-- Settings -->
                <div class="geocoding-test-section">
					<?php $this->render_settings_form(); ?>
                </div>
			<?php endif; ?>
        </div>
		<?php
	}

	/**
	 * Render settings form
	 */
	private function render_settings_form(): void {
		?>
        <h2><?php _e( 'Settings', 'arraypress-google-geocoding' ); ?></h2>
        <form method="post" class="geocoding-form">
			<?php wp_nonce_field( 'geocoding_test_api_key' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="google_geocoding_api_key"><?php _e( 'API Key', 'arraypress-google-geocoding' ); ?></label>
                    </th>
                    <td>
                        <input type="text" name="google_geocoding_api_key" id="google_geocoding_api_key"
                               class="regular-text"
                               value="<?php echo esc_attr( get_option( 'google_geocoding_api_key' ) ); ?>"
                               placeholder="<?php esc_attr_e( 'Enter your Google Geocoding API key...', 'arraypress-google-geocoding' ); ?>">
                        <p class="description">
							<?php _e( 'Your Google Geocoding API key. Required for making API requests.', 'arraypress-google-geocoding' ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="google_geocoding_enable_cache"><?php _e( 'Enable Cache', 'arraypress-google-geocoding' ); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="google_geocoding_enable_cache"
                                   id="google_geocoding_enable_cache"
                                   value="1" <?php checked( get_option( 'google_geocoding_enable_cache', true ) ); ?>>
							<?php _e( 'Cache geocoding results', 'arraypress-google-geocoding' ); ?>
                        </label>
                        <p class="description">
							<?php _e( 'Caching results can help reduce API usage and improve performance.', 'arraypress-google-geocoding' ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="google_geocoding_cache_duration"><?php _e( 'Cache Duration', 'arraypress-google-geocoding' ); ?></label>
                    </th>
                    <td>
                        <input type="number" name="google_geocoding_cache_duration" id="google_geocoding_cache_duration"
                               class="regular-text"
                               value="<?php echo esc_attr( get_option( 'google_geocoding_cache_duration', 86400 ) ); ?>"
                               min="300" step="300">
                        <p class="description">
							<?php _e( 'How long to cache results in seconds. Default is 86400 (24 hours).', 'arraypress-google-geocoding' ); ?>
                        </p>
                    </td>
                </tr>
            </table>
			<?php submit_button(
				empty( get_option( 'google_geocoding_api_key' ) )
					? __( 'Save Settings', 'arraypress-google-geocoding' )
					: __( 'Update Settings', 'arraypress-google-geocoding' ),
				'primary',
				'submit_api_key'
			); ?>
        </form>
		<?php
	}

}

new Plugin();