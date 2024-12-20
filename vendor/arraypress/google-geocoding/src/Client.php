<?php
/**
 * Google Geocoding API Client Class
 *
 * @package     ArrayPress/Utils
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\Google\Geocoding;

use WP_Error;

/**
 * Class Client
 *
 * A comprehensive utility class for interacting with the Google Geocoding API.
 */
class Client {

	/**
	 * API key for Google Geocoding
	 *
	 * @var string
	 */
	private string $api_key;

	/**
	 * Base URL for the Geocoding API
	 *
	 * @var string
	 */
	private const API_ENDPOINT = 'https://maps.googleapis.com/maps/api/geocode/json';

	/**
	 * Whether to enable response caching
	 *
	 * @var bool
	 */
	private bool $enable_cache;

	/**
	 * Cache expiration time in seconds
	 *
	 * @var int
	 */
	private int $cache_expiration;

	/**
	 * Initialize the Geocoding client
	 *
	 * @param string $api_key          API key for Google Geocoding
	 * @param bool   $enable_cache     Whether to enable caching (default: true)
	 * @param int    $cache_expiration Cache expiration in seconds (default: 24 hours)
	 */
	public function __construct( string $api_key, bool $enable_cache = true, int $cache_expiration = 86400 ) {
		$this->api_key          = $api_key;
		$this->enable_cache     = $enable_cache;
		$this->cache_expiration = $cache_expiration;
	}

	/**
	 * Geocode an address to coordinates
	 *
	 * @param string $address Address to geocode
	 *
	 * @return Response|WP_Error Response object or WP_Error on failure
	 */
	public function geocode( string $address ) {
		$cache_key = $this->get_cache_key( 'geocode_' . $address );

		if ( $this->enable_cache ) {
			$cached_data = get_transient( $cache_key );
			if ( false !== $cached_data ) {
				return new Response( $cached_data );
			}
		}

		$response = $this->make_request( [ 'address' => $address ] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( $this->enable_cache ) {
			set_transient( $cache_key, $response, $this->cache_expiration );
		}

		return new Response( $response );
	}

	/**
	 * Reverse geocode coordinates to address
	 *
	 * @param float $lat Latitude
	 * @param float $lng Longitude
	 *
	 * @return Response|WP_Error Response object or WP_Error on failure
	 */
	public function reverse_geocode( float $lat, float $lng ) {
		$cache_key = $this->get_cache_key( "reverse_{$lat}_{$lng}" );

		if ( $this->enable_cache ) {
			$cached_data = get_transient( $cache_key );
			if ( false !== $cached_data ) {
				return new Response( $cached_data );
			}
		}

		$response = $this->make_request( [ 'latlng' => "{$lat},{$lng}" ] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( $this->enable_cache ) {
			set_transient( $cache_key, $response, $this->cache_expiration );
		}

		return new Response( $response );
	}

	/**
	 * Make a request to the Geocoding API
	 *
	 * @param array $params Request parameters
	 *
	 * @return array|WP_Error Response array or WP_Error on failure
	 */
	private function make_request( array $params ) {
		$params['key'] = $this->api_key;

		$url = add_query_arg( $params, self::API_ENDPOINT );

		$response = wp_remote_get( $url, [
			'timeout' => 15,
			'headers' => [ 'Accept' => 'application/json' ]
		] );

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'api_error',
				sprintf(
					__( 'Geocoding API request failed: %s', 'arraypress' ),
					$response->get_error_message()
				)
			);
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_Error(
				'json_error',
				__( 'Failed to parse Geocoding API response', 'arraypress' )
			);
		}

		if ( $data['status'] !== 'OK' && $data['status'] !== 'ZERO_RESULTS' ) {
			return new WP_Error(
				'api_error',
				sprintf(
					__( 'Geocoding API returned error: %s', 'arraypress' ),
					$data['status']
				)
			);
		}

		return $data;
	}

	/**
	 * Generate cache key
	 *
	 * @param string $identifier Cache identifier
	 *
	 * @return string Cache key
	 */
	private function get_cache_key( string $identifier ): string {
		return 'google_geocoding_' . md5( $identifier . $this->api_key );
	}

	/**
	 * Clear cached data
	 *
	 * @param string|null $identifier Optional specific cache to clear
	 *
	 * @return bool True on success, false on failure
	 */
	public function clear_cache( ?string $identifier = null ): bool {
		if ( $identifier !== null ) {
			return delete_transient( $this->get_cache_key( $identifier ) );
		}

		global $wpdb;
		$pattern = $wpdb->esc_like( '_transient_google_geocoding_' ) . '%';

		return $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
					$pattern
				)
			) !== false;
	}

}