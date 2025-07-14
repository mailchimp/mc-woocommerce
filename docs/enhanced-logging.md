# Enhanced Mailchimp Connection Logging

## Overview

The enhanced logging system provides detailed diagnostic information for Mailchimp API connection failures, helping customers and support teams quickly identify and resolve connectivity issues.

## Features

### Comprehensive Error Tracking
- Captures all API connection attempts (success and failure)
- Records detailed error messages and HTTP status codes
- Tracks connection timing metrics (DNS lookup, connect time, total time)
- Identifies SSL certificate issues

### Diagnostic Information
- Detects hosting provider automatically
- Identifies CDN presence (Cloudflare, Akamai, etc.)
- Detects proxy configurations
- Provides possible causes for failures
- Offers specific recommendations for resolution

### Connection Details Captured
- HTTP method and full URL
- Response codes and error types
- Server IP addresses and ports
- SSL verification status
- Request/response timing
- Environment information (PHP, WordPress, WooCommerce versions)

## Accessing Connection Logs

1. Navigate to the Mailchimp for WooCommerce settings in your WordPress admin
2. Click on the "Logs" tab
3. Select "Connection Logs (Enhanced)" from the dropdown menu
4. View detailed connection information for the last 100 API calls

## Log Entry Information

Each log entry includes:

### Basic Information
- Timestamp (local and UTC)
- HTTP method (GET, POST, etc.)
- API endpoint URL
- HTTP status code
- Success/failure status
- Response time

### Diagnostic Details
- **Environment**: PHP version, WordPress version, WooCommerce version, hosting provider
- **Connection**: Primary IP, connection time, DNS lookup time, SSL status
- **Error Details**: Error type, message, and response data
- **Diagnostics**: Possible causes and recommended solutions

## Common Issues and Solutions

### HTTP 0 - Connection Failed
**Possible Causes:**
- Firewall blocking outbound HTTPS connections
- Network connectivity issues
- DNS resolution problems

**Recommendations:**
- Check server firewall settings
- Verify outbound HTTPS connections are allowed
- Test DNS resolution for api.mailchimp.com

### HTTP 403 - Forbidden
**Possible Causes:**
- IP address blocked by Mailchimp or CDN
- Rate limiting
- Akamai security blocking

**Recommendations:**
- Check if server IP is blacklisted
- Contact hosting provider about outbound restrictions
- Implement request throttling

### HTTP 429 - Rate Limited
**Possible Causes:**
- Too many API requests in short time
- Aggressive sync settings

**Recommendations:**
- Reduce API request frequency
- Implement exponential backoff
- Adjust sync batch sizes

### HTTP 500+ - Server Errors
**Possible Causes:**
- Mailchimp service issues
- Temporary server problems

**Recommendations:**
- Retry requests after delay
- Check Mailchimp status page
- Contact support if persistent

## Data Storage

- Connection logs are stored as WordPress transients
- Kept for 7 days automatically
- Maximum 100 entries retained
- Can be manually cleared from the logs interface

## Privacy and Security

- API keys and sensitive authentication data are automatically redacted
- Only connection metadata is logged, not request/response bodies
- Logs are only accessible to WordPress administrators

## Support Usage

When contacting support about connection issues:

1. Navigate to Connection Logs
2. Identify the failed requests
3. Note the error messages and diagnostic information
4. Share the timestamp and error details with support
5. If needed, export logs for detailed analysis

## Technical Implementation

The enhanced logger (`MailChimp_WooCommerce_Enhanced_Logger`) hooks into the existing API class to capture:
- All cURL operations
- Error conditions
- Response metadata
- Environmental context

Logs are integrated into the existing WooCommerce logging system while maintaining backward compatibility.