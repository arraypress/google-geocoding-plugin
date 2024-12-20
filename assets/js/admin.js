(function ($) {
    'use strict';

    // Store form references
    const $forwardForm = $('#geocoding-forward-form');
    const $reverseForm = $('#geocoding-reverse-form');
    const $settingsForm = $('#geocoding-settings-form');

    // Store input references
    const $address = $('#address');
    const $latitude = $('#latitude');
    const $longitude = $('#longitude');
    const $apiKey = $('#geocoding_api_key');
    const $enableCache = $('#geocoding_enable_cache');
    const $cacheDuration = $('#geocoding_cache_duration');

    /**
     * Initialize the admin interface
     */
    function init() {
        bindEvents();
        setupInputValidation();
        setupGeolocation();
        setupCacheDurationToggle();
    }

    /**
     * Bind event listeners
     */
    function bindEvents() {
        // Validate forms before submission
        $forwardForm.on('submit', validateForwardForm);
        $reverseForm.on('submit', validateReverseForm);
        $settingsForm.on('submit', validateSettingsForm);

        // Real-time input validation
        $latitude.on('input', validateCoordinate);
        $longitude.on('input', validateCoordinate);

        // Clear validation messages on input
        $address.on('input', clearValidation);
        $apiKey.on('input', clearValidation);
        $cacheDuration.on('input', clearValidation);
    }

    /**
     * Set up coordinate input validation
     */
    function setupInputValidation() {
        // Add custom validation styling
        $('input[type="text"], input[type="number"]').on('invalid', function () {
            $(this).addClass('validation-error');
        }).on('input', function () {
            $(this).removeClass('validation-error');
        });
    }

    /**
     * Set up geolocation features
     */
    function setupGeolocation() {
        // Add geolocation button if supported
        if ("geolocation" in navigator) {
            const $geoButton = $('<button>', {
                type: 'button',
                class: 'button button-secondary',
                text: 'Use Current Location'
            }).insertAfter($latitude);

            $geoButton.on('click', function (e) {
                e.preventDefault();
                $(this).prop('disabled', true).text('Getting Location...');

                navigator.geolocation.getCurrentPosition(
                    function (position) {
                        $latitude.val(position.coords.latitude.toFixed(6));
                        $longitude.val(position.coords.longitude.toFixed(6));
                        $geoButton.prop('disabled', false).text('Use Current Location');
                    },
                    function (error) {
                        showError('Geolocation failed: ' + error.message);
                        $geoButton.prop('disabled', false).text('Use Current Location');
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 5000,
                        maximumAge: 0
                    }
                );
            });
        }
    }

    /**
     * Set up cache duration toggle based on cache enable checkbox
     */
    function setupCacheDurationToggle() {
        function toggleCacheDuration() {
            $cacheDuration.prop('disabled', !$enableCache.prop('checked'));
        }

        $enableCache.on('change', toggleCacheDuration);
        toggleCacheDuration(); // Initial state
    }

    /**
     * Validate forward geocoding form
     */
    function validateForwardForm(e) {
        clearValidation();

        if (!$address.val().trim()) {
            e.preventDefault();
            showError('Please enter an address');
            $address.focus();
            return false;
        }
        return true;
    }

    /**
     * Validate reverse geocoding form
     */
    function validateReverseForm(e) {
        clearValidation();

        const lat = parseFloat($latitude.val());
        const lng = parseFloat($longitude.val());

        if (isNaN(lat) || lat < -90 || lat > 90) {
            e.preventDefault();
            showError('Latitude must be between -90 and 90');
            $latitude.focus();
            return false;
        }

        if (isNaN(lng) || lng < -180 || lng > 180) {
            e.preventDefault();
            showError('Longitude must be between -180 and 180');
            $longitude.focus();
            return false;
        }

        return true;
    }

    /**
     * Validate settings form
     */
    function validateSettingsForm(e) {
        clearValidation();

        if (!$apiKey.val().trim()) {
            e.preventDefault();
            showError('Please enter your API key');
            $apiKey.focus();
            return false;
        }

        if ($enableCache.prop('checked')) {
            const duration = parseInt($cacheDuration.val(), 10);
            if (isNaN(duration) || duration < 300) {
                e.preventDefault();
                showError('Cache duration must be at least 300 seconds');
                $cacheDuration.focus();
                return false;
            }
        }

        return true;
    }

    /**
     * Validate coordinate input in real-time
     */
    function validateCoordinate() {
        const $input = $(this);
        const value = parseFloat($input.val());
        const isLatitude = $input.attr('id') === 'latitude';
        const min = isLatitude ? -90 : -180;
        const max = isLatitude ? 90 : 180;

        if (!isNaN(value) && value >= min && value <= max) {
            $input.removeClass('validation-error');
        } else {
            $input.addClass('validation-error');
        }
    }

    /**
     * Show error message
     */
    function showError(message) {
        const $error = $('<div>', {
            class: 'notice notice-error is-dismissible',
            html: $('<p>', {text: message})
        });

        $('.geocoding-test .notice').remove(); // Remove any existing notices
        $('.geocoding-test h1').after($error);

        // Add dismiss button functionality
        const $button = $('<button>', {
            type: 'button',
            class: 'notice-dismiss',
            html: $('<span>', {class: 'screen-reader-text', text: 'Dismiss this notice.'})
        }).appendTo($error);

        $button.on('click', function () {
            $error.fadeOut(200, function () {
                $(this).remove();
            });
        });
    }

    /**
     * Clear validation messages
     */
    function clearValidation() {
        $('.geocoding-test .notice').remove();
        $(this).removeClass('validation-error');
    }

    /**
     * Format coordinates to 6 decimal places
     */
    function formatCoordinate() {
        const value = parseFloat($(this).val());
        if (!isNaN(value)) {
            $(this).val(value.toFixed(6));
        }
    }

    /**
     * Copy coordinate pairs to clipboard
     */
    function setupCoordinateCopy() {
        const $copyButton = $('<button>', {
            type: 'button',
            class: 'button button-secondary copy-coordinates',
            text: 'Copy Coordinates'
        }).insertAfter($longitude);

        $copyButton.on('click', function (e) {
            e.preventDefault();
            const lat = $latitude.val();
            const lng = $longitude.val();

            if (lat && lng) {
                const text = `${lat}, ${lng}`;
                navigator.clipboard.writeText(text).then(
                    function () {
                        const $button = $copyButton.text('Copied!');
                        setTimeout(() => $button.text('Copy Coordinates'), 2000);
                    },
                    function (err) {
                        showError('Failed to copy coordinates: ' + err);
                    }
                );
            }
        });
    }

    // Initialize on document ready
    $(document).ready(init);

})(jQuery);