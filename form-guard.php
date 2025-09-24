<?php

/**
 * Plugin Name: Form Guard (Disable on Non-Production)
 * Description: Disables all forms sitewide when the current domain is not the allowed production domain. Also blocks Contact Form 7 server-side. Supports PH-specific message via IP geolocation.
 * Version:     1.2.0
 * Author:      Roy De La Torre
 * Plugin URI:  https://github.com/roy-dela-torre/disable-form
 * Update URI:  https://github.com/roy-dela-torre/disable-form
 */

if (!defined('ABSPATH')) exit;

// Define plugin constants
define('FG_PLUGIN_VERSION', '1.2.0');
define('FG_PLUGIN_SLUG', 'form-guard');
define('FG_PLUGIN_FILE', __FILE__);

// Load update configuration
require_once plugin_dir_path(__FILE__) . 'update-config.php';

/** -----------------------------
 * Helpers
 * ------------------------------*/
function fg_normalize_host($host)
{
    $host = strtolower(trim((string) $host));
    if (strpos($host, 'www.') === 0) $host = substr($host, 4);
    return $host;
}
function fg_current_host()
{
    $home = home_url();
    $host = wp_parse_url($home, PHP_URL_HOST);
    return fg_normalize_host($host ?: '');
}
function fg_allowed_host()
{
    $saved = get_option('fg_allowed_domain', 'www.bankofmakati.com.ph');
    return fg_normalize_host($saved);
}
function fg_guard_enabled()
{
    return get_option('fg_guard_enabled', '0') === '1';
}
function fg_message_default()
{
    $default = '🚧 Forms are disabled on this non-production domain.';
    $msg = get_option('fg_guard_message', $default);
    $msg = is_string($msg) ? trim($msg) : $default;
    return $msg === '' ? $default : $msg;
}
function fg_message_ph()
{
    $default = '🚧 Ang website ay kasalukuyang nasa BETA. Pansamantalang naka-disable ang lahat ng forms.';
    $msg = get_option('fg_guard_message_ph', $default);
    $msg = is_string($msg) ? trim($msg) : $default;
    return $msg === '' ? $default : $msg;
}
function fg_use_geo_api()
{
    return get_option('fg_use_geo_api', '0') === '1';
}
function fg_disabled_cf7_forms()
{
    $disabled = get_option('fg_disabled_cf7_forms', []);
    return is_array($disabled) ? $disabled : [];
}
function fg_get_all_cf7_forms()
{
    if (!function_exists('wpcf7')) return [];
    
    $forms = get_posts([
        'post_type' => 'wpcf7_contact_form',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ]);
    
    $result = [];
    foreach ($forms as $form) {
        $result[$form->ID] = $form->post_title;
    }
    return $result;
}

/**
 * Geo: detect country code (PH, US, etc.)
 * Order: Cloudflare header -> common GEOIP headers -> optional ipapi.co -> 'XX' (unknown)
 * Result cached in a cookie and transient for ~24h.
 */
function fg_detect_country_code()
{
    // Cookie first (fast)
    if (!empty($_COOKIE['fg_cc'])) {
        $cc = strtoupper(sanitize_text_field($_COOKIE['fg_cc']));
        if (preg_match('/^[A-Z]{2}$/', $cc)) return $cc;
    }

    // Cloudflare
    if (!empty($_SERVER['HTTP_CF_IPCOUNTRY'])) {
        $cc = strtoupper(sanitize_text_field($_SERVER['HTTP_CF_IPCOUNTRY']));
        if ($cc && $cc !== 'XX') return fg_cache_country($cc);
    }

    // Common server-provided geo vars
    $server_vars = ['GEOIP_COUNTRY_CODE', 'HTTP_GEOIP_COUNTRY_CODE', 'GEO_COUNTRY', 'HTTP_X_APPENGINE_COUNTRY'];
    foreach ($server_vars as $v) {
        if (!empty($_SERVER[$v])) {
            $cc = strtoupper(sanitize_text_field($_SERVER[$v]));
            if (preg_match('/^[A-Z]{2}$/', $cc)) return fg_cache_country($cc);
        }
    }

    // Optional remote API (ipapi.co) with transient cache by IP
    if (fg_use_geo_api()) {
        $ip = fg_client_ip();
        if ($ip) {
            $key = 'fg_cc_' . md5($ip);
            $cached = get_transient($key);
            if (is_string($cached) && preg_match('/^[A-Z]{2}$/', $cached)) {
                return fg_cache_country($cached);
            }
            // Call ipapi.co (no key; rate-limited). Fail-safe if it times out.
            $resp = wp_remote_get('https://ipapi.co/' . $ip . '/country/', ['timeout' => 2]);
            if (!is_wp_error($resp) && 200 === wp_remote_retrieve_response_code($resp)) {
                $body = strtoupper(trim(wp_remote_retrieve_body($resp)));
                if (preg_match('/^[A-Z]{2}$/', $body)) {
                    set_transient($key, $body, 24 * HOUR_IN_SECONDS);
                    return fg_cache_country($body);
                }
            }
        }
    }

    return fg_cache_country('XX'); // unknown
}
function fg_client_ip()
{
    $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($keys as $k) {
        if (!empty($_SERVER[$k])) {
            $v = $_SERVER[$k];
            if ($k === 'HTTP_X_FORWARDED_FOR') { // take first public IP
                $parts = array_map('trim', explode(',', $v));
                $v = $parts[0] ?? $v;
            }
            return sanitize_text_field($v);
        }
    }
    return null;
}
function fg_cache_country($cc)
{
    // set cookie ~1 day
    setcookie('fg_cc', $cc, time() + DAY_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN ?: '', is_ssl(), true);
    $_COOKIE['fg_cc'] = $cc; // make available this request
    return $cc;
}

function fg_is_non_production()
{
    if (!fg_guard_enabled()) return false;
    $current = fg_current_host();
    $allowed = fg_allowed_host();
    return $current !== $allowed;
}
function fg_active_message_text()
{
    // If visitor is PH, use PH message; else default
    $cc = fg_detect_country_code();
    return ($cc === 'PH') ? fg_message_ph() : fg_message_default();
}

/** -----------------------------
 * Admin: Settings Page
 * ------------------------------*/
add_action('admin_menu', function () {
    add_options_page(
        'Form Guard',
        'Form Guard',
        'manage_options',
        'form-guard',
        'fg_render_settings_page'
    );
});

add_action('admin_init', function () {
    register_setting('fg_settings_group', 'fg_guard_enabled');
    register_setting('fg_settings_group', 'fg_allowed_domain', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'www.bankofmakati.com.ph',
    ]);
    register_setting('fg_settings_group', 'fg_guard_message', [
        'type' => 'string',
        'sanitize_callback' => 'wp_strip_all_tags',
        'default' => '🚧 Forms are disabled on this non-production domain.',
    ]);
    register_setting('fg_settings_group', 'fg_guard_message_ph', [
        'type' => 'string',
        'sanitize_callback' => 'wp_strip_all_tags',
        'default' => '🚧 Ang website ay kasalukuyang nasa BETA. Pansamantalang naka-disable ang lahat ng forms.',
    ]);
    register_setting('fg_settings_group', 'fg_use_geo_api'); // '1' or not set
    register_setting('fg_settings_group', 'fg_disabled_cf7_forms', [
        'type' => 'array',
        'sanitize_callback' => 'fg_sanitize_form_ids',
        'default' => []
    ]);
});

function fg_sanitize_form_ids($input) {
    if (!is_array($input)) return [];
    return array_map('intval', array_filter($input, 'is_numeric'));
}

function fg_render_settings_page()
{
    if (!current_user_can('manage_options')) return;
    $enabled = get_option('fg_guard_enabled', '0');
    $allowed = get_option('fg_allowed_domain', 'www.bankofmakati.com.ph');
    $msg     = fg_message_default();
    $msgPH   = fg_message_ph();
    $useAPI  = get_option('fg_use_geo_api', '0');
    $disabledCF7Forms = fg_disabled_cf7_forms();
    $allCF7Forms = fg_get_all_cf7_forms();
?>
    <div class="wrap">
        <h1>Form Guard (Disable on Non-Production)</h1>
        <form method="post" action="options.php">
            <?php settings_fields('fg_settings_group'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">Enable guard</th>
                    <td>
                        <label>
                            <input type="checkbox" name="fg_guard_enabled" value="1" <?php checked($enabled, '1'); ?>>
                            Disable forms on any domain that does not match the allowed domain.
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Allowed domain</th>
                    <td>
                        <input type="text" class="regular-text" name="fg_allowed_domain"
                            value="<?php echo esc_attr($allowed); ?>"
                            placeholder="www.bankofmakati.com.ph" />
                        <p class="description">Use your production host (with or without <code>www.</code>).</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Default message</th>
                    <td>
                        <input type="text" class="regular-text" name="fg_guard_message"
                            value="<?php echo esc_attr($msg); ?>"
                            placeholder="Message for non-PH visitors" />
                        <p class="description">Shown to visitors whose IP is not detected as PH (or country unknown).</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Disable Specific Contact Form 7 Forms</th>
                    <td>
                        <?php if (empty($allCF7Forms)): ?>
                            <p><em>No Contact Form 7 forms found. Make sure Contact Form 7 plugin is installed and you have created some forms.</em></p>
                        <?php else: ?>
                            <fieldset style="border: 1px solid #ddd; padding: 15px; background: #f9f9f9; border-radius: 4px;">
                                <legend class="screen-reader-text">Select forms to disable</legend>
                                <div style="max-height: 200px; overflow-y: auto; padding: 5px;">
                                    <?php foreach ($allCF7Forms as $formId => $formTitle): ?>
                                        <label style="display: block; margin-bottom: 10px; padding: 8px; background: white; border: 1px solid #e1e1e1; border-radius: 3px;">
                                            <input type="checkbox" 
                                                   name="fg_disabled_cf7_forms[]" 
                                                   value="<?php echo esc_attr($formId); ?>"
                                                   <?php checked(in_array($formId, $disabledCF7Forms)); ?>
                                                   style="margin-right: 8px;">
                                            <strong><?php echo esc_html($formTitle); ?></strong> 
                                            <code style="color: #666; font-size: 11px;">(ID: <?php echo esc_html($formId); ?>)</code>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <div style="margin-top: 10px;">
                                    <button type="button" onclick="fg_select_all_forms()" class="button button-small">Select All</button>
                                    <button type="button" onclick="fg_deselect_all_forms()" class="button button-small">Deselect All</button>
                                </div>
                            </fieldset>
                            <p class="description">
                                <strong>Instructions:</strong><br>
                                • Check the forms you want to disable on non-production domains<br>
                                • When specific forms are selected, only those forms will be disabled<br>
                                • If no forms are selected, ALL forms will be disabled (backward compatibility)<br>
                                • Search forms are never disabled<br>
                                <span id="fg-selection-count" style="color: #d63638; font-weight: bold;">
                                    Currently selected: <?php echo count($disabledCF7Forms); ?> form(s)
                                </span>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>
                <!-- <tr>
                    <th scope="row">PH message</th>
                    <td>
                        <input type="text" class="regular-text" name="fg_guard_message_ph"
                               value="<?php echo esc_attr($msgPH); ?>"
                               placeholder="Message for visitors from the Philippines" />
                        <p class="description">Example: <code>Ang website ay kasalukuyang nasa beta pa lamang. Paunawa na ang lahat ng mga forms ay pansamantalang naka-disable sa panahong ito.</code></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Enable IP lookup API (optional)</th>
                    <td>
                        <label>
                            <input type="checkbox" name="fg_use_geo_api" value="1" <?php checked($useAPI, '1'); ?>>
                            Use ipapi.co when headers are unavailable (cached ~24h). May be rate-limited on high traffic.
                        </label>
                    </td>
                </tr> -->
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    
    <script>
    function fg_select_all_forms() {
        var checkboxes = document.querySelectorAll('input[name="fg_disabled_cf7_forms[]"]');
        checkboxes.forEach(function(checkbox) {
            checkbox.checked = true;
        });
        fg_update_selection_count();
    }
    
    function fg_deselect_all_forms() {
        var checkboxes = document.querySelectorAll('input[name="fg_disabled_cf7_forms[]"]');
        checkboxes.forEach(function(checkbox) {
            checkbox.checked = false;
        });
        fg_update_selection_count();
    }
    
    function fg_update_selection_count() {
        var checkedBoxes = document.querySelectorAll('input[name="fg_disabled_cf7_forms[]"]:checked');
        var countElement = document.getElementById('fg-selection-count');
        if (countElement) {
            countElement.innerHTML = 'Currently selected: ' + checkedBoxes.length + ' form(s)';
        }
    }
    
    // Add event listeners to update count when checkboxes change
    document.addEventListener('DOMContentLoaded', function() {
        var checkboxes = document.querySelectorAll('input[name="fg_disabled_cf7_forms[]"]');
        checkboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', fg_update_selection_count);
        });
    });
    </script>
<?php
}

/** -----------------------------
 * Frontend: Styles + Banner
 * ------------------------------*/
add_action('wp_head', function () {
    if (!fg_is_non_production()) return;
?>
    <style>
        .fg-banner {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #f59e0b;
            color: #111;
            z-index: 99999;
            text-align: center;
            padding: 10px 30px;
            font-weight: 600;
            box-shadow: 0 2px 6px rgba(0, 0, 0, .15);
            color: black;
            font-family: 'Montserrat', sans-serif;
            font-size: 16px;
            font-style: normal;
            font-weight: 400;
            line-height: 25px;
            /* 156.25% */
            word-break: break-all;
            @media(width < 767px){
                position: sticky;
            }
        }

        form.fg-disabled *:is(input, select, textarea, button) {
            pointer-events: none !important;
            opacity: .55 !important;
        }

        form.fg-disabled::after {
            content: 'Form disabled on non-production domain';
            display: block;
            font-size: 12px;
            color: #6b7280;
            margin-top: 6px;
        }
    </style>
    <div class="fg-banner">
        <?php echo esc_html(fg_active_message_text()); ?>
        <button class="close" style="position: absolute; top: 5px; right: 10px; background: none; border: none; font-size: 20px; cursor: pointer; color: inherit;" onclick="this.parentElement.style.display='none'; document.body.style.paddingTop=0; document.documentElement.style.scrollPaddingTop=0;">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 14 14" fill="none">
                <path d="M1.4 14L0 12.6L5.6 7L0 1.4L1.4 0L7 5.6L12.6 0L14 1.4L8.4 7L14 12.6L12.6 14L7 8.4L1.4 14Z" fill="#4A4A4A" />
            </svg>
        </button>
    </div>
<?php
});

/** -----------------------------
 * Frontend: Disable all forms + block submit
 * ------------------------------*/
add_action('wp_footer', function () {
    if (!fg_is_non_production()) return;
    $msg = fg_active_message_text();
    $disabledCF7Forms = fg_disabled_cf7_forms();
?>
    <script>
        (function() {
            try {
                var messageText = <?php echo json_encode($msg, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
                var disabledCF7Forms = <?php echo json_encode($disabledCF7Forms); ?>;

                // Make this function globally accessible
                window.formGuardDisableAllForms = function disableAllForms() {
                    var forms = document.querySelectorAll('form');
                    forms.forEach(function(f) {
                        // Skip search forms
                        if (f.getAttribute('role') === 'search' || 
                            f.querySelector('input[type="search"]') ||
                            f.querySelector('input[name="s"]')) {
                            return; // Don't disable search forms
                        }
                        
                        // Skip if already disabled
                        if (f.classList.contains('fg-disabled')) {
                            return;
                        }
                        
                        // Check if this is a Contact Form 7 form
                        var isCF7Form = f.classList.contains('wpcf7-form') || f.querySelector('.wpcf7-form');
                        var shouldDisable = false;
                        
                        if (isCF7Form) {
                            // If specific CF7 forms are selected for disabling
                            if (disabledCF7Forms && disabledCF7Forms.length > 0) {
                                // Check if this specific form should be disabled
                                var formIdInput = f.querySelector('input[name="_wpcf7"]');
                                if (formIdInput) {
                                    var formId = parseInt(formIdInput.value);
                                    shouldDisable = disabledCF7Forms.indexOf(formId) !== -1;
                                }
                            } else {
                                // No specific forms selected, disable all CF7 forms (backward compatibility)
                                shouldDisable = true;
                            }
                        } else {
                            // For non-CF7 forms, only disable if no specific CF7 forms are selected
                            // This maintains backward compatibility - if user hasn't selected specific forms,
                            // disable all forms as before
                            shouldDisable = !disabledCF7Forms || disabledCF7Forms.length === 0;
                        }
                        
                        if (shouldDisable) {
                            f.classList.add('fg-disabled');
                            f.querySelectorAll('input, select, textarea, button').forEach(function(el) {
                                try {
                                    el.setAttribute('disabled', 'disabled');
                                } catch (e) {}
                            });
                            f.addEventListener('submit', function(ev) {
                                ev.preventDefault();
                                ev.stopImmediatePropagation();
                                alert(messageText);
                                return false;
                            }, true);
                        }
                    });
                };

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', window.formGuardDisableAllForms);
                } else {
                    window.formGuardDisableAllForms();
                }

                // Watch for dynamically added forms
                if (typeof MutationObserver !== 'undefined') {
                    var observer = new MutationObserver(function(mutations) {
                        var shouldCheck = false;
                        mutations.forEach(function(mutation) {
                            if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                                for (var i = 0; i < mutation.addedNodes.length; i++) {
                                    var node = mutation.addedNodes[i];
                                    if (node.nodeType === 1) { // Element node
                                        if (node.tagName === 'FORM' || node.querySelector && node.querySelector('form')) {
                                            shouldCheck = true;
                                            break;
                                        }
                                    }
                                }
                            }
                        });
                        if (shouldCheck) {
                            window.formGuardDisableAllForms();
                        }
                    });
                    
                    observer.observe(document.body, {
                        childList: true,
                        subtree: true
                    });
                }
            } catch (e) {}
        })();
    </script>
<?php
});

/** -----------------------------
 * Contact Form 7: server-side stop + message
 * ------------------------------*/
add_action('plugins_loaded', function () {
    if (!function_exists('wpcf7')) return;
    add_action('wpcf7_before_send_mail', function ($contact_form) {
        if (fg_is_non_production() && fg_should_disable_cf7_form($contact_form->id())) {
            $contact_form->skip_mail = true;
        }
    }, 1, 1);
    add_filter('wpcf7_display_message', function ($message, $status) {
        if (fg_is_non_production()) {
            // Get the current form being processed
            $current_cf7 = wpcf7_get_current_contact_form();
            if ($current_cf7 && fg_should_disable_cf7_form($current_cf7->id())) {
                return fg_active_message_text();
            }
        }
        return $message;
    }, 10, 2);
});

function fg_should_disable_cf7_form($form_id) {
    $disabledForms = fg_disabled_cf7_forms();
    
    // If no specific forms are selected, disable all CF7 forms (backward compatibility)
    if (empty($disabledForms)) {
        return true;
    }
    
    // Only disable if this specific form is in the disabled list
    return in_array((int)$form_id, $disabledForms);
}
