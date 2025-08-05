# hCaptcha Newsletter Signup Handler

A PHP-based form handler that processes newsletter signups with hCaptcha verification and integrates with Sendy email marketing software.

## Overview

This system consists of two main components:

1. **`hcaptcha-handler.php`** - Universal form handler for static HTML pages
2. **`subscribe.php`** - Modified Sendy subscription endpoint with hCaptcha integration

## Features

- **hCaptcha Integration**: Protects against bots with hCaptcha verification
- **Honeypot Protection**: Additional anti-spam measures with honeypot fields
- **IP Detection**: Handles various proxy configurations (Cloudflare, Nginx, etc.)
- **Sendy Integration**: Direct API calls to Sendy for email list management
- **Error Handling**: Basic error handling

## Configuration Required

### hCaptcha Configuration File

You **must** create a configuration file at `/path/to/your/private/hcaptcha-config.php` containing:

```php
<?php
// hCaptcha site keys - Get these from https://hcaptcha.com/
define('HCAPTCHA_SECRET', 'your_hcaptcha_secret_key_here');

// Sendy API configuration
define('SENDY_API_KEY', 'your_sendy_api_key');
define('SENDY_LIST_ID', 'your_sendy_list_id');
?>
```

**Important**: 
- Keep this file outside your web root for security
- Never commit this file to version control
- Get your hCaptcha keys from [https://hcaptcha.com/](https://hcaptcha.com/)
- Find your Sendy API key and List ID in your Sendy admin panel

## How It Works

### Universal Handler (`hcaptcha-handler.php`)

1. **Form Validation**: Validates email format and required fields
2. **Bot Protection**: Checks honeypot fields and hCaptcha response
3. **IP Detection**: Extracts real visitor IP through various proxy configurations
4. **Sendy Integration**: Forwards validated data to Sendy subscription endpoint
5. **Response Handling**: Processes Sendy responses and redirects appropriately

### Modified Sendy Handler (`subscribe.php`)

The Sendy subscription endpoint has been modified to:
- Integrate hCaptcha verification for all POST requests
- Skip hCaptcha for valid API key requests
- Support IP address forwarding from external handlers
- Maintain all original Sendy functionality

## HTML Form Example

```html
<form action="/path/to/hcaptcha-handler.php" method="POST">
    <input type="email" name="email" placeholder="Enter your email" required>
    <input type="text" name="fname" placeholder="First name">
    <input type="text" name="lname" placeholder="Last name">
    
    <!-- Honeypot fields (hidden with CSS) -->
    <input type="text" name="website" style="display:none;">
    <input type="text" name="hp" style="display:none;">
    
    <!-- hCaptcha widget -->
    <div class="h-captcha" data-sitekey="your_hcaptcha_site_key"></div>
    
    <!-- Optional redirect destination -->
    <input type="hidden" name="redirect" value="/thanks/">
    
    <button type="submit">Subscribe</button>
</form>

<script src="https://hcaptcha.com/1/api.js" async defer></script>
```

## Security Features

- **Input Validation**: All inputs are sanitized and validated
- **hCaptcha Verification**: Server-side verification of hCaptcha responses
- **Honeypot Fields**: Hidden fields to catch automated submissions
- **IP Validation**: Validates IP addresses before processing
- **Safe Redirects**: Only allows predefined redirect destinations
- **Error Logging**: Comprehensive logging for debugging

## Allowed Redirect URLs

The handler only allows redirects to predefined safe URLs:
- `/are-you-a-bot/` (default)
- `/thanks/`

To add more allowed redirects, modify the `$allowedRedirects` array in `hcaptcha-handler.php`.

## Error Handling

The system provides user-friendly error messages for:
- Invalid email addresses
- Missing required fields
- hCaptcha verification failures
- Network errors
- Already subscribed users

## Installation

1. Upload both PHP files to your server
2. Create the hCaptcha configuration file with your API keys
3. Update the file paths in the handlers to match your setup
4. Test with a sample HTML form

## Dependencies

- PHP with cURL extension
- Valid hCaptcha account and site keys
- Sendy installation with API access
- Web server with PHP support

## Maintenance Notes

- Regularly update hCaptcha site keys if needed
- Monitor error logs for suspicious activity
- Keep Sendy and PHP updated for security
- Review and update allowed redirect URLs as needed

## Support

This handler is designed for use with static HTML pages that need newsletter signup functionality without WordPress or other CMS dependencies.

---

**Security Notice**: Always keep your configuration file with API keys outside the web root and never commit it to version control.
