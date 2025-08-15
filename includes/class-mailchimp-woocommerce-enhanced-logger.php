<?php

/**
 * Enhanced logging class for Mailchimp WooCommerce
 * Provides detailed diagnostic information for connection failures
 */
class MailChimp_WooCommerce_Enhanced_Logger {
    
    const CONNECTION_LOG_KEY = 'mailchimp_connection_logs';
    const MAX_LOG_ENTRIES = 100;
    
    /**
     * Log connection attempt with detailed diagnostic information
     * 
     * @param string $method HTTP method (GET, POST, etc)
     * @param string $url Full URL being called
     * @param array $curl_info CURL info array from curl_getinfo()
     * @param mixed $response Raw response data
     * @param array $error_info Error details if any
     * @param array $headers Request headers
     * @param mixed $request_data Request body data
     */
    public static function log_connection_attempt($method, $url, $curl_info = array(), $response = null, $error_info = array(), $headers = array(), $request_data = null) {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'timestamp_utc' => current_time('mysql', true),
            'method' => $method,
            'url' => $url,
            'environment' => self::get_environment_info(),
            'connection' => self::get_connection_info($curl_info),
            'request' => self::get_request_info($headers, $request_data),
            'response' => self::get_response_info($response, $curl_info),
            'error' => $error_info,
            'diagnostics' => self::get_diagnostics_info($url, $curl_info, $error_info)
        );
        
        // Log to WooCommerce logs
        if (!empty($error_info)) {
            mailchimp_debug('enhanced_logger', 'ERROR', $log_entry);
        } else {
            mailchimp_debug('enhanced_logger', 'INFO', $log_entry);
        }
        
        // Store in transient for admin view
        self::store_connection_log($log_entry);
    }
    
    /**
     * Get environment information
     */
    private static function get_environment_info() {
        return array(
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version'),
            'wc_version' => defined('WC_VERSION') ? WC_VERSION : 'unknown',
            'mc_version' => defined('MC_WC_VERSION') ? MC_WC_VERSION : 'unknown',
            'server_software' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'unknown',
            'server_ip' => self::get_server_ip(),
            'site_url' => get_site_url(),
            'home_url' => get_home_url(),
            'is_ssl' => is_ssl(),
            'is_multisite' => is_multisite(),
            'active_plugins' => self::get_active_plugins_info(),
            'hosting_provider' => self::detect_hosting_provider(),
            'proxy_detected' => self::detect_proxy(),
            'cdn_detected' => self::detect_cdn()
        );
    }
    
    /**
     * Get connection information from CURL
     */
    private static function get_connection_info($curl_info) {
        if (empty($curl_info)) {
            return array('error' => 'No CURL info available');
        }
        
        return array(
            'http_code' => isset($curl_info['http_code']) ? $curl_info['http_code'] : null,
            'total_time' => isset($curl_info['total_time']) ? $curl_info['total_time'] : null,
            'namelookup_time' => isset($curl_info['namelookup_time']) ? $curl_info['namelookup_time'] : null,
            'connect_time' => isset($curl_info['connect_time']) ? $curl_info['connect_time'] : null,
            'pretransfer_time' => isset($curl_info['pretransfer_time']) ? $curl_info['pretransfer_time'] : null,
            'starttransfer_time' => isset($curl_info['starttransfer_time']) ? $curl_info['starttransfer_time'] : null,
            'redirect_count' => isset($curl_info['redirect_count']) ? $curl_info['redirect_count'] : null,
            'redirect_url' => isset($curl_info['redirect_url']) ? $curl_info['redirect_url'] : null,
            'primary_ip' => isset($curl_info['primary_ip']) ? $curl_info['primary_ip'] : null,
            'primary_port' => isset($curl_info['primary_port']) ? $curl_info['primary_port'] : null,
            'local_ip' => isset($curl_info['local_ip']) ? $curl_info['local_ip'] : null,
            'local_port' => isset($curl_info['local_port']) ? $curl_info['local_port'] : null,
            'ssl_verify_result' => isset($curl_info['ssl_verify_result']) ? $curl_info['ssl_verify_result'] : null,
            'content_type' => isset($curl_info['content_type']) ? $curl_info['content_type'] : null,
            'scheme' => isset($curl_info['scheme']) ? $curl_info['scheme'] : null,
            'protocol' => isset($curl_info['protocol']) ? $curl_info['protocol'] : null,
            'ssl_engine' => isset($curl_info['ssl_engine']) ? $curl_info['ssl_engine'] : null,
            'request_size' => isset($curl_info['request_size']) ? $curl_info['request_size'] : null,
            'download_size' => isset($curl_info['size_download']) ? $curl_info['size_download'] : null,
            'upload_size' => isset($curl_info['size_upload']) ? $curl_info['size_upload'] : null,
            'header_size' => isset($curl_info['header_size']) ? $curl_info['header_size'] : null
        );
    }
    
    /**
     * Get request information
     */
    private static function get_request_info($headers, $request_data) {
        $info = array(
            'headers' => self::sanitize_headers($headers),
            'data_size' => is_string($request_data) ? strlen($request_data) : null,
            'data_type' => gettype($request_data)
        );
        
        // Include non-sensitive request data
        if (is_array($request_data) || is_object($request_data)) {
            $info['data_keys'] = array_keys((array)$request_data);
        }
        
        return $info;
    }
    
    /**
     * Get response information
     */
    private static function get_response_info($response, $curl_info) {
        $info = array(
            'size' => is_string($response) ? strlen($response) : null,
            'type' => gettype($response),
            'is_json' => false,
            'headers' => array()
        );
        
        if (!empty($curl_info['header_size']) && is_string($response)) {
            $header_text = substr($response, 0, $curl_info['header_size']);
            $info['headers'] = self::parse_response_headers($header_text);
        }
        
        if (is_string($response)) {
            $decoded = json_decode($response, true);
            $info['is_json'] = (json_last_error() === JSON_ERROR_NONE);
            
            if ($info['is_json'] && is_array($decoded)) {
                // Include error information if present
                if (isset($decoded['status'])) $info['status'] = $decoded['status'];
                if (isset($decoded['title'])) $info['title'] = $decoded['title'];
                if (isset($decoded['detail'])) $info['detail'] = $decoded['detail'];
                if (isset($decoded['type'])) $info['type'] = $decoded['type'];
                if (isset($decoded['errors'])) $info['errors'] = $decoded['errors'];
            }
        }
        
        return $info;
    }
    
    /**
     * Get diagnostic information
     */
    private static function get_diagnostics_info($url, $curl_info, $error_info) {
        $diagnostics = array(
            'possible_causes' => array(),
            'recommendations' => array(),
            'checks' => array()
        );
        
        // Parse URL for additional info
        $parsed_url = parse_url($url);
        $diagnostics['target_host'] = isset($parsed_url['host']) ? $parsed_url['host'] : 'unknown';
        $diagnostics['target_datacenter'] = self::extract_datacenter($parsed_url['host']);
        
        // DNS resolution check
        if (!empty($parsed_url['host'])) {
            $dns_records = @dns_get_record($parsed_url['host'], DNS_A + DNS_AAAA);
            $diagnostics['checks']['dns_resolution'] = !empty($dns_records);
            if ($dns_records) {
                $diagnostics['dns_records'] = array_map(function($record) {
                    return array(
                        'type' => $record['type'],
                        'ip' => isset($record['ip']) ? $record['ip'] : (isset($record['ipv6']) ? $record['ipv6'] : null)
                    );
                }, $dns_records);
            }
        }
        
        // Check for common issues
        if (!empty($curl_info['http_code'])) {
            switch ($curl_info['http_code']) {
                case 0:
                    $diagnostics['possible_causes'][] = 'Connection failed - possible network issue or firewall blocking';
                    $diagnostics['recommendations'][] = 'Check server firewall settings';
                    $diagnostics['recommendations'][] = 'Verify outbound HTTPS connections are allowed';
                    break;
                case 403:
                    $diagnostics['possible_causes'][] = 'Access forbidden - possible IP blocking or rate limiting';
                    $diagnostics['possible_causes'][] = 'Akamai or CDN blocking the request';
                    $diagnostics['recommendations'][] = 'Check if your server IP is blacklisted';
                    $diagnostics['recommendations'][] = 'Contact hosting provider about outbound restrictions';
                    break;
                case 429:
                    $diagnostics['possible_causes'][] = 'Rate limit exceeded';
                    $diagnostics['recommendations'][] = 'Reduce API request frequency';
                    $diagnostics['recommendations'][] = 'Implement exponential backoff';
                    break;
                case 500:
                case 502:
                case 503:
                case 504:
                    $diagnostics['possible_causes'][] = 'Mailchimp server error';
                    $diagnostics['recommendations'][] = 'Retry the request after a delay';
                    $diagnostics['recommendations'][] = 'Check Mailchimp status page';
                    break;
            }
        }
        
        // SSL issues
        if (!empty($curl_info['ssl_verify_result']) && $curl_info['ssl_verify_result'] != 0) {
            $diagnostics['possible_causes'][] = 'SSL certificate verification failed';
            $diagnostics['recommendations'][] = 'Update server CA certificates';
            $diagnostics['checks']['ssl_verification'] = false;
        }
        
        // Timeout issues
        if (!empty($curl_info['total_time']) && $curl_info['total_time'] > 30) {
            $diagnostics['possible_causes'][] = 'Request timeout - slow connection';
            $diagnostics['recommendations'][] = 'Check network latency to Mailchimp servers';
        }
        
        // Connection time issues
        if (!empty($curl_info['connect_time']) && $curl_info['connect_time'] > 5) {
            $diagnostics['possible_causes'][] = 'Slow connection establishment';
            $diagnostics['recommendations'][] = 'Check DNS resolution speed';
            $diagnostics['recommendations'][] = 'Verify network routing';
        }
        
        return $diagnostics;
    }
    
    /**
     * Detect hosting provider
     */
    private static function detect_hosting_provider() {
        $indicators = array(
            'wpengine' => 'WP Engine',
            'pantheon' => 'Pantheon',
            'kinsta' => 'Kinsta',
            'siteground' => 'SiteGround',
            'bluehost' => 'Bluehost',
            'godaddy' => 'GoDaddy',
            'dreamhost' => 'DreamHost',
            'hostgator' => 'HostGator',
            'cloudways' => 'Cloudways'
        );
        
        foreach ($indicators as $key => $provider) {
            if (defined('WPE_APIKEY') && $key === 'wpengine') return $provider;
            if (defined('PANTHEON_ENVIRONMENT') && $key === 'pantheon') return $provider;
            if (isset($_SERVER['KINSTA_CACHE_ZONE']) && $key === 'kinsta') return $provider;
            
            // Check server variables
            $server_vars = array($_SERVER['SERVER_SOFTWARE'], $_SERVER['SERVER_NAME'], php_uname('n'));
            foreach ($server_vars as $var) {
                if (stripos($var, $key) !== false) {
                    return $provider;
                }
            }
        }
        
        return 'Unknown';
    }
    
    /**
     * Detect proxy
     */
    private static function detect_proxy() {
        $proxy_headers = array(
            'HTTP_VIA',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED',
            'HTTP_CLIENT_IP',
            'HTTP_FORWARDED_FOR_IP',
            'VIA',
            'X_FORWARDED_FOR',
            'FORWARDED_FOR',
            'X_FORWARDED',
            'FORWARDED',
            'CLIENT_IP',
            'FORWARDED_FOR_IP',
            'HTTP_PROXY_CONNECTION'
        );
        
        foreach ($proxy_headers as $header) {
            if (!empty($_SERVER[$header])) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Detect CDN
     */
    private static function detect_cdn() {
        $cdn_headers = array(
            'HTTP_CF_RAY' => 'Cloudflare',
            'HTTP_CF_VISITOR' => 'Cloudflare',
            'HTTP_CF_CONNECTING_IP' => 'Cloudflare',
            'HTTP_X_SUCURI_CLIENTIP' => 'Sucuri',
            'HTTP_X_AKAMAI_EDGESCAPE' => 'Akamai',
            'HTTP_X_FASTLY_REQUEST_ID' => 'Fastly',
            'HTTP_X_STACKPATH_REQUEST_ID' => 'StackPath'
        );
        
        foreach ($cdn_headers as $header => $cdn) {
            if (!empty($_SERVER[$header])) {
                return $cdn;
            }
        }
        
        return 'None detected';
    }
    
    /**
     * Get server IP
     */
    private static function get_server_ip() {
        // Try to get actual outbound IP
        $services = array(
            'https://api.ipify.org',
            'https://icanhazip.com',
            'https://ipecho.net/plain'
        );
        
        foreach ($services as $service) {
            $response = wp_remote_get($service, array(
                'timeout' => 2,
                'sslverify' => false
            ));
            
            if (!is_wp_error($response)) {
                $ip = trim(wp_remote_retrieve_body($response));
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : 'unknown';
    }
    
    /**
     * Get active plugins info
     */
    private static function get_active_plugins_info() {
        $active_plugins = get_option('active_plugins', array());
        $plugin_info = array();
        
        foreach ($active_plugins as $plugin) {
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin, false, false);
            if (!empty($plugin_data['Name'])) {
                $plugin_info[] = $plugin_data['Name'] . ' v' . $plugin_data['Version'];
            }
        }
        
        return $plugin_info;
    }
    
    /**
     * Extract datacenter from Mailchimp URL
     */
    private static function extract_datacenter($host) {
        if (preg_match('/^([a-z]+)(\d+)\.api\.mailchimp\.com$/', $host, $matches)) {
            return $matches[1] . $matches[2];
        }
        return 'unknown';
    }
    
    /**
     * Sanitize headers to remove sensitive information
     */
    private static function sanitize_headers($headers) {
        if (!is_array($headers)) return array();
        
        $sanitized = array();
        $sensitive_headers = array('authorization', 'x-api-key', 'cookie', 'set-cookie', 'bearer');
        
        foreach ($headers as $key => $value) {
            $lower_key = strtolower($key);
            $sensitive = false;
            foreach ($sensitive_headers as $sensitive_header) {
                if (str_contains($lower_key, $sensitive_header)) {
                    $sensitive = true;
                }
            }
            if ($sensitive || in_array($lower_key, $sensitive_headers)) {
                $sanitized[$key] = '[REDACTED]';
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Parse response headers
     */
    private static function parse_response_headers($header_text) {
        $headers = array();
        $lines = explode("\r\n", $header_text);
        
        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $headers[trim($key)] = trim($value);
            }
        }
        
        return self::sanitize_headers($headers);
    }
    
    /**
     * Format log message for output
     */
    private static function format_log_message($log_entry) {
        $message = sprintf(
            "[%s] %s %s - HTTP %s",
            $log_entry['timestamp'],
            $log_entry['method'],
            $log_entry['url'],
            isset($log_entry['connection']['http_code']) ? $log_entry['connection']['http_code'] : 'N/A'
        );
        
        if (!empty($log_entry['error'])) {
            $message .= sprintf(" - ERROR: %s", wc_print_r($log_entry['error'], true));
        }
        
        if (!empty($log_entry['diagnostics']['possible_causes'])) {
            $message .= sprintf(" - Possible causes: %s", implode('; ', $log_entry['diagnostics']['possible_causes']));
        }
        
        $message .= sprintf(" - Full details: %s", wc_print_r($log_entry, true));
        
        return $message;
    }
    
    /**
     * Store connection log in transient
     */
    private static function store_connection_log($log_entry) {
        $logs = get_transient(self::CONNECTION_LOG_KEY);
        if (!is_array($logs)) {
            $logs = array();
        }
        
        // Add new entry at beginning
        array_unshift($logs, $log_entry);
        
        // Keep only recent entries
        if (count($logs) > self::MAX_LOG_ENTRIES) {
            $logs = array_slice($logs, 0, self::MAX_LOG_ENTRIES);
        }
        
        // Store for 7 days
        set_transient(self::CONNECTION_LOG_KEY, $logs, 7 * DAY_IN_SECONDS);
    }
    
    /**
     * Get stored connection logs
     */
    public static function get_connection_logs($limit = 50) {
        $logs = get_transient(self::CONNECTION_LOG_KEY);
        if (!is_array($logs)) {
            return array();
        }
        
        return array_slice($logs, 0, $limit);
    }
    
    /**
     * Clear stored connection logs
     */
    public static function clear_connection_logs() {
        delete_transient(self::CONNECTION_LOG_KEY);
    }
}