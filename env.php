<?php
global $wp_version;

return (object) array(
    'repo' => 'develop',
    'environment' => 'production',
    'version' => '2.0.1',
    'wp_version' => (empty($wp_version) ? 'Unknown' : $wp_version),
    'wc_version' => class_exists('WC') ? WC()->version : null,
);
