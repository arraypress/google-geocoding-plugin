# Google Geocoding Tester Plugin for WordPress

A WordPress plugin that provides a user interface for testing and demonstrating the Google Geocoding API integration. This plugin allows you to easily test forward and reverse geocoding operations and manage API settings through the WordPress admin interface.

## Features

- Visual interface for geocoding testing
- Forward geocoding (address to coordinates)
- Reverse geocoding (coordinates to address)
- Configurable caching system
- Comprehensive location details including:
    - Formatted address
    - Precise coordinates
    - Place ID and types
    - Plus codes
    - Viewport boundaries
    - Detailed address components
    - Business location indicators
    - Partial match status

## Requirements

- PHP 7.4 or later
- WordPress 5.0 or later
- Google Maps API key with Geocoding API enabled

## Installation

1. Download or clone this repository
2. Place in your WordPress plugins directory
3. Run `composer install` in the plugin directory
4. Activate the plugin in WordPress
5. Add your Google Geocoding API key in Google > Geocoding

## Usage

1. Navigate to Google > Geocoding in your WordPress admin panel
2. Enter your Google Geocoding API key in the settings section
3. Configure caching preferences (optional)
4. Use the forward geocoding form to convert addresses to coordinates
5. Use the reverse geocoding form to convert coordinates to addresses
6. View comprehensive location information in the results table

## Features in Detail

### Forward Geocoding
- Convert any address or place name into precise coordinates
- Get detailed address components and location type
- Identify business locations and points of interest

### Reverse Geocoding
- Convert latitude/longitude pairs into human-readable addresses
- Get nearest address and place information
- Access administrative area details

### Caching System
- Configurable cache duration
- Cache clearing functionality
- Reduced API usage and improved performance

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the GPL-2.0-or-later License.

## Support

For support and bug reports, please use the GitHub issue tracker.