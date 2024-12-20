<?php return array(
    'root' => array(
        'name' => 'arraypress/google-geocoding-plugin',
        'pretty_version' => '1.0.0+no-version-set',
        'version' => '1.0.0.0',
        'reference' => null,
        'type' => 'wordpress-plugin',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'dev' => true,
    ),
    'versions' => array(
        'arraypress/google-geocoding' => array(
            'pretty_version' => 'dev-main',
            'version' => 'dev-main',
            'reference' => '9d67fe0bb4e016f1dddd6e988f9b7e2caf70e225',
            'type' => 'library',
            'install_path' => __DIR__ . '/../arraypress/google-geocoding',
            'aliases' => array(
                0 => '9999999-dev',
            ),
            'dev_requirement' => false,
        ),
        'arraypress/google-geocoding-plugin' => array(
            'pretty_version' => '1.0.0+no-version-set',
            'version' => '1.0.0.0',
            'reference' => null,
            'type' => 'wordpress-plugin',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
    ),
);
