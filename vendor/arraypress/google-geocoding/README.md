# Google Geocoding API for WordPress

A PHP library for integrating with the Google Geocoding API in WordPress, providing address to coordinate conversion and structured address information. Features WordPress transient caching and WP_Error support.

## Features

- 🗺️ **Address Geocoding**: Convert addresses to coordinates
- 📍 **Reverse Geocoding**: Convert coordinates to addresses
- 🏠 **Address Components**: Access structured address information
- ⚡ **WordPress Integration**: Native transient caching and WP_Error support
- 🛡️ **Type Safety**: Full type hinting and strict types
- 🔄 **Response Parsing**: Clean response object for easy data access
- 🌍 **Global Support**: Works with addresses worldwide
- 📐 **Viewport Information**: Access location viewport bounds

## Requirements

- PHP 7.4 or later
- WordPress 5.0 or later
- Google Geocoding API key

## Installation

Install via Composer:

```bash
composer require arraypress/google-geocoding
```

## Basic Usage

```php
use ArrayPress\Google\Geocoding\Client;

// Initialize client with your API key
$client = new Client( 'your-google-api-key' );

// Forward geocoding (Address to Coordinates)
$result = $client->geocode( '1600 Amphitheatre Parkway, Mountain View, CA' );
if ( ! is_wp_error( $result ) ) {
	$coordinates = $result->get_coordinates();
	echo "Latitude: {$coordinates['latitude']}\n";
	echo "Longitude: {$coordinates['longitude']}\n";
}

// Reverse geocoding (Coordinates to Address)
$result = $client->reverse_geocode( 37.4220, - 122.0841 );
if ( ! is_wp_error( $result ) ) {
	echo $result->get_formatted_address();
}
```

## Extended Examples
### Getting Structured Address Components

```php
$result = $client->geocode( '1600 Amphitheatre Parkway, Mountain View, CA' );
if ( ! is_wp_error( $result ) ) {
	$address      = $result->get_structured_address();
	$street       = $result->get_street_number() . ' ' . $result->get_street_name();
	$city         = $result->get_city();
	$state        = $result->get_state();
	$state_code   = $result->get_state_short();  // Returns "CA"
	$postal       = $result->get_postal_code();
	$country      = $result->get_country();
	$country_code = $result->get_country_short(); // Returns "US"
}
```

### Handling Responses with Caching

```php
// Initialize with custom cache duration (1 hour = 3600 seconds)
$client = new Client( 'your-api-key', true, 3600 );

// Results will be cached
$result = $client->geocode( '1600 Amphitheatre Parkway, Mountain View, CA' );

// Clear specific cache
$client->clear_cache( 'geocode_1600 Amphitheatre Parkway, Mountain View, CA' );

// Clear all geocoding caches
$client->clear_cache();
```

## API Methods
### Client Methods

* `geocode( $address )`: Convert address to coordinates
* `reverse_geocode( $lat, $lng )`: Convert coordinates to address
* `clear_cache( $identifier = null )`: Clear cached responses

### Response Methods

* `get_formatted_address()`: Get full formatted address
* `get_coordinates()`: Get latitude/longitude array
* `get_latitude()`: Get latitude
* `get_longitude()`: Get longitude
* `get_place_id()`: Get Google Place ID
* `get_plus_code()`: Get plus code information
* `get_plus_code_compound()`: Get compound plus code
* `get_plus_code_global()`: Get global plus code
* `get_location_type()`: Get location type
* `get_street_number()`: Get street number
* `get_street_name()`: Get street name
* `get_city()`: Get city/locality
* `get_county()`: Get county
* `get_state()`: Get state/province
* `get_state_short()`: Get state/province code
* `get_postal_code()`: Get postal code
* `get_country()`: Get country
* `get_country_short()`: Get country code
* `get_viewport()`: Get viewport bounds
* `get_structured_address()`: Get all components

## Use Cases

* **Address Validation**: Verify and standardize addresses
* **Coordinate Lookup**: Get coordinates for addresses
* **Location Services**: Support location-based features
* **Address Parsing**: Extract address components
* **Geographic Analysis**: Analyze location data
* **Map Integration**: Support for mapping features
* **Address Autocomplete**: Base for address lookup systems

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the GPL-2.0-or-later License.

## Support

- [Documentation](https://github.com/arraypress/google-geocoding)
- [Issue Tracker](https://github.com/arraypress/google-geocoding/issues)