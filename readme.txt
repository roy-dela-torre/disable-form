=== Form Guard (Disable on Non-Production) ===
Contributors: Roy De La Torre
Tags: form, security, production, development, staging, contact-form-7
Requires at least: 5.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Disables forms sitewide on non-production domains with IP geolocation support and selective Contact Form 7 blocking.

== Description ==

Form Guard is a WordPress plugin designed to protect your development and staging environments from accidental form submissions. It automatically detects when your site is running on a non-production domain and disables forms sitewide, preventing test data from being submitted or processed.

= Key Features =

* **Selective Form Disabling**: Choose which Contact Form 7 forms to disable, or disable all forms globally
* **Smart Geolocation**: Displays different messages based on visitor's location (Philippines vs. others) using multiple detection methods
* **Multiple IP Detection**: Supports Cloudflare, server headers, and optional API-based geolocation with 24-hour caching
* **Visual Indicators**: Shows a dismissible banner message and visual form indicators
* **Dual Protection**: Both client-side (JavaScript) and server-side (PHP) form blocking
* **Contact Form 7 Integration**: Advanced CF7 integration with selective form disabling and server-side blocking
* **Search Form Exception**: Automatically preserves search functionality while disabling other forms
* **Dynamic Form Detection**: Handles dynamically loaded forms using MutationObserver
* **Performance Optimized**: Cached geolocation results and minimal resource usage

= Use Cases =

* **Development Sites**: Prevent form submissions on local development environments
* **Staging Sites**: Block form processing on staging/testing domains  
* **Beta Testing**: Show appropriate messages during beta testing phases
* **Domain Migration**: Temporarily disable forms during site migrations

= How It Works =

1. The plugin compares your current domain with the configured production domain
2. If they don't match, forms are disabled based on your configuration:
   - **Selective Mode**: Only specified Contact Form 7 forms are disabled
   - **Global Mode**: All forms are disabled (when no specific CF7 forms are selected)
3. A dismissible banner appears at the top of the page with location-specific messaging
4. Geolocation detection uses multiple methods: Cloudflare headers → Server GeoIP → Optional API
5. Form submissions are blocked both client-side (JavaScript) and server-side (PHP hooks)
6. Dynamic forms are automatically detected and disabled using DOM monitoring

== Installation ==

= Automatic Installation =

1. Go to your WordPress admin dashboard
2. Navigate to Plugins > Add New
3. Search for "Form Guard"
4. Click "Install Now" and then "Activate"

= Manual Installation =

1. Download the plugin files
2. Upload the `disable-form` folder to `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to Settings > Form Guard to configure the plugin

= Quick Setup =

1. After activation, go to **Settings > Form Guard** in your WordPress admin
2. Check "Enable guard" to activate form protection
3. Set your production domain (e.g., `www.yoursite.com`)
4. Customize the warning message if needed
5. Save settings

== Configuration ==

= Basic Settings =

**Enable Guard**: Check this box to activate form protection on non-production domains.

**Allowed Domain**: Enter your production domain name (e.g., `www.bankofmakati.com.ph`). The plugin normalizes domains, so `www.` prefix is optional.

**Default Message**: Customize the message shown to non-Philippines visitors. Default: "🚧 Forms are disabled on this non-production domain."

**Disable Specific Contact Form 7 Forms**: Select individual CF7 forms to disable. Features include:
- Visual form selection with Select All/Deselect All buttons
- Real-time selection counter
- Scrollable interface for many forms
- Backward compatibility: if no forms are selected, ALL forms are disabled

= Advanced Options =

The plugin includes advanced geolocation features:

* **Philippines Message**: Automatic localized message for PH visitors: "🚧 Ang website ay kasalukuyang nasa BETA. Pansamantalang naka-disable ang lahat ng forms."
* **Multi-tier Geolocation**: Uses Cloudflare headers → Server GeoIP headers → Optional API fallback
* **Caching System**: 24-hour cookie and transient caching for performance
* **API Integration**: Optional ipapi.co integration with 2-second timeout and rate limiting protection

= Domain Configuration Examples =

* `yoursite.com` - matches both www.yoursite.com and yoursite.com
* `www.yoursite.com` - matches both www.yoursite.com and yoursite.com  
* `subdomain.yoursite.com` - matches only this specific subdomain

== Frequently Asked Questions ==

= Will this affect my production site? =

No, the plugin only activates when your current domain doesn't match the configured production domain. On your live site, forms will work normally.

= Can I customize the disabled form message? =

Yes, you can customize the message in Settings > Form Guard. The message appears both in the top banner and in alert dialogs when users try to submit forms.

= Does this work with all form plugins? =

The plugin works with:
- **Standard HTML forms**: Disabled via JavaScript and CSS
- **Contact Form 7**: Advanced integration with selective disabling and server-side blocking
- **Most form plugins**: Compatible with plugins that use standard HTML form elements
- **Dynamic forms**: Automatically detects and disables forms loaded via AJAX

Complex form systems with custom JavaScript may need additional configuration.

= Can I disable only specific Contact Form 7 forms? =

Yes! The plugin now supports selective CF7 form disabling:
- Select specific forms from a visual interface in Settings → Form Guard
- Use Select All/Deselect All buttons for bulk operations
- View real-time selection counter
- **Backward compatibility**: If no forms are selected, ALL forms are disabled (original behavior)

= Will search forms still work? =

Yes, search forms are automatically excluded from being disabled. The plugin detects search forms by checking for:
- `role="search"` attribute
- `input[type="search"]` elements  
- `input[name="s"]` elements (WordPress default)

= How does the geolocation feature work? =

The plugin uses a multi-tier detection system:
1. **Cloudflare headers** (`HTTP_CF_IPCOUNTRY`) - fastest, most reliable
2. **Server GeoIP headers** (`GEOIP_COUNTRY_CODE`, `HTTP_GEOIP_COUNTRY_CODE`, etc.)
3. **Optional API lookup** (ipapi.co with 2-second timeout)
4. **Caching**: Results cached in cookies and transients for 24 hours
5. **Fallback**: Returns 'XX' (unknown) if all methods fail

Philippines visitors see localized messages automatically.

= Can I temporarily disable the plugin without deactivating it? =

Yes, simply uncheck "Enable guard" in the plugin settings. This will disable form protection without deactivating the plugin.

= What happens if someone tries to submit a form? =

Form submissions are blocked at multiple levels:
- **Client-side**: JavaScript prevents form submission and shows an alert with your custom message
- **Visual cues**: Forms get the `fg-disabled` class with visual indicators (opacity, disabled state)
- **Server-side**: Contact Form 7 forms are blocked via `wpcf7_before_send_mail` hook
- **No data processing**: Absolutely no form data is processed or sent when disabled

== Technical Details ==

= System Requirements =

* WordPress 5.0 or higher
* PHP 7.4 or higher
* JavaScript enabled in browser for client-side protection

= Server Compatibility =

The plugin works with:
* Standard WordPress hosting
* Cloudflare (enhanced geolocation)
* Staging environments
* Local development (XAMPP, WAMP, Local, etc.)

= Performance =

* **Minimal performance impact**: Only active on non-production domains
* **Smart caching**: Geolocation results cached for 24 hours via cookies and transients
* **Conditional loading**: CSS and JavaScript only loaded when forms need to be disabled  
* **Zero production impact**: No database queries or processing on production sites
* **Optimized detection**: Fast domain comparison using normalized host checking
* **API protection**: Optional API calls have 2-second timeout and are cached per IP

== Screenshots ==

1. Plugin settings page showing configuration options
2. Form disabled banner displayed at top of page
3. Example of disabled form with visual indicators
4. Admin interface for customizing messages

== Changelog ==

= 1.2.0 =
* **NEW**: Selective Contact Form 7 form disabling with visual interface
* **NEW**: Advanced geolocation with multi-tier detection (Cloudflare → Server → API)
* **NEW**: Philippines-specific messaging with automatic localization
* **NEW**: Dynamic form detection using MutationObserver for AJAX-loaded forms
* **NEW**: Dismissible banner with close button and responsive design
* **IMPROVED**: 24-hour caching system for geolocation (cookies + transients)
* **IMPROVED**: Enhanced admin interface with Select All/Deselect All buttons
* **IMPROVED**: Better performance with conditional resource loading
* **IMPROVED**: Robust error handling and timeout protection for API calls
* **FIXED**: Search forms properly excluded from disabling
* **FIXED**: Backward compatibility maintained for existing installations

= 1.1.0 =
* Added Contact Form 7 server-side blocking
* Improved admin interface
* Enhanced form detection
* Added visual indicators for disabled forms

= 1.0.0 =
* Initial release
* Basic form disabling functionality
* Admin configuration interface
* Domain-based activation

== Upgrade Notice ==

= 1.2.0 =
This version adds geolocation support and improved form handling. Update recommended for better user experience.

== Support ==

For support, feature requests, or bug reports, please contact the plugin author or create an issue in the plugin repository.

== License ==

This plugin is licensed under the GPL v2 or later.

```