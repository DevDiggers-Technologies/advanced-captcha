<?php
/**
 * Self-check for the Free/Pro boundary.
 *
 * Every Pro feature is meant to be physically absent from this plugin, not switched off.
 * The easy ways to break that are a rebuild from a stale source tree or a file copied
 * across from the Pro plugin. This asserts that neither has happened.
 *
 *   php bin/test-free-boundary.php
 *
 * @package Advanced CAPTCHA
 */

$root   = dirname( __DIR__ ) . '/';
$errors = [];

/**
 * Read a file, or return '' when it is absent.
 *
 * @param string $path Absolute path.
 * @return string
 */
function ddwcac_read( $path ) {
	return file_exists( $path ) ? (string) file_get_contents( $path ) : '';
}

/**
 * Every shipped file of a type, excluding the bundled framework and tooling.
 *
 * @param string   $root       Plugin root.
 * @param string[] $extensions Extensions.
 * @return string[] Relative path => contents.
 */
function ddwcac_plugin_files( $root, $extensions ) {
	$files    = [];
	$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );

	foreach ( $iterator as $file ) {
		$path = $file->getPathname();

		if ( ! in_array( strtolower( $file->getExtension() ), $extensions, true ) ) {
			continue;
		}

		foreach ( [ '/node_modules/', '/devdiggers-framework/', '/bin/', '/tests/', '/build/' ] as $skip ) {
			if ( false !== strpos( $path, $skip ) ) {
				continue 2;
			}
		}

		$files[ str_replace( $root, '', $path ) ] = ddwcac_read( $path );
	}

	return $files;
}

$php = ddwcac_plugin_files( $root, [ 'php' ] );
$js  = ddwcac_plugin_files( $root, [ 'js' ] );

// 1. Pro-only files are gone, not merely unhooked.
foreach ( [ 'includes/data/disposable-domains.php' ] as $file ) {
	if ( file_exists( $root . $file ) ) {
		$errors[] = "Pro file {$file} is shipped.";
	}
}

// 2. No licensing, updater or remote notifications.
foreach ( $php as $relative => $contents ) {
	foreach ( [ 'ddfw_is_license_activated', 'ddfw_register_plugin_for_updates', '_ddwcac_purchase_code', '_ddwcac_license_activated', 'DevDiggers_Notifications', 'DDFW_Plugin_Updater', 'get_license_layout' ] as $needle ) {
		if ( false !== strpos( $contents, $needle ) ) {
			$errors[] = "Licensing reference '{$needle}' is present in {$relative}.";
		}
	}
}

foreach ( [ 'includes/class-ddfw-plugin-updater.php', 'includes/class-devdiggers-notifications.php', 'templates/layout/license.php' ] as $file ) {
	if ( file_exists( $root . 'devdiggers-framework/' . $file ) ) {
		$errors[] = "Bundled framework ships {$file}.";
	}
}

// 3. No handler, cron or hook for a Pro feature. Locked UI describes these; nothing may run them.
$banned = [
	// Lockout, block list and country rules.
	'ddwcac_lock_ip',
	'ddwcac_is_locked',
	'ddwcac_unlock',
	'ddwcac_register_failure',
	'ddwcac_get_lockouts',
	'ddwcac_ip_locked_out',
	"'_ddwcac_blocked_ips'",
	"['blocked_ips']",
	"['lockout_",
	'ddwcac_get_country',
	'ddwcac_is_country_blocked',
	"['country_mode']",
	// Smart mode and the reCAPTCHA v3 fallback.
	'ddwcac_is_smart_mode',
	'ddwcac_is_suspect',
	'ddwcac_mark_suspect',
	'wp_login_failed',
	'google_fallback_active',
	"['google_v3_fallback']",
	"'ddwcac_v2_'",
	// Fraud shield and email guard.
	'woocommerce_order_status_failed',
	'ddwcac_record_failed_payment',
	'ddwcac_is_email_blocked',
	"['card_testing_",
	"['checkout_rate_limit']",
	"['disposable_",
	// Notifications, import/export, retention setting.
	'wp_mail',
	'ddwcac_weekly_report',
	'admin_post_ddwcac_export_settings',
	'admin_post_ddwcac_import_settings',
	"['log_retention']",
	// Pro-only integrations.
	'gform_',
	'elementor_pro/forms',
	'forminator_',
	'frm_',
	'bp_signup_validate',
	'bbp_new_',
	'um_submit_form_errors_hook',
	'mc4wp_form',
	'ddwcaf_',
	'ddwcpos_',
	// Built at load time only in Pro.
	'wp_unschedule_hook',
	'register_rest_route',
];

foreach ( $php as $relative => $contents ) {
	foreach ( $banned as $needle ) {
		if ( false !== strpos( $contents, $needle ) ) {
			$errors[] = "Pro code '{$needle}' is present in {$relative}.";
		}
	}
}

// 4. Only the Free settings are registered, so a crafted options.php post cannot save a Pro option.
$admin = ddwcac_read( $root . 'includes/admin/admin-functions.php' );
foreach ( [ "'smart_mode'", "'country_mode'", "'countries'", "'card_testing_enabled'", "'disposable_enabled'", "'lockout_enabled'", "'blocked_ips'", "'alert_lockouts'", "'weekly_report'", "'log_retention'", "'google_v3_fallback'", "'google_v2_site_key'", "'woocommerce_affiliates_forms'", "'multipos_forms'" ] as $key ) {
	if ( false !== strpos( $admin, $key ) ) {
		$errors[] = "Pro setting {$key} is registered or sanitized in admin-functions.php.";
	}
}

// 5. Free integrations are exactly the free set.
$globals = ddwcac_read( $root . 'includes/global-functions.php' );
if ( ! preg_match( '/function ddwcac_get_integrations\(\) \{\s*return \[(.*?)\];/s', $globals, $match ) || 2 !== preg_match_all( "/^\s*'[a-z]+'\s*=>/m", $match[1] ) ) {
	$errors[] = 'ddwcac_get_integrations() must list exactly the two Free form plugins.';
}

// 6. Every built asset maps to a webpack entry, and none carries Pro logic or writes storage.
$webpack = ddwcac_read( $root . 'webpack.config.js' );
foreach ( glob( $root . 'assets/{js,css}/*.{js,css}', GLOB_BRACE ) as $asset ) {
	$name = pathinfo( $asset, PATHINFO_FILENAME );
	if ( false === strpos( $webpack, '"' . $name . '"' ) ) {
		$errors[] = "assets/{$name} has no webpack entry.";
	}
}

foreach ( $js as $relative => $contents ) {
	foreach ( [ 'document.cookie', 'localStorage', 'console.log', 'smart_mode', 'lockout_', 'country-mode', 'card_testing', 'disposable_', 'data-fallback' ] as $needle ) {
		if ( false !== strpos( $contents, $needle ) ) {
			$errors[] = "'{$needle}' is present in {$relative}.";
		}
	}
}

// 7. Free first release: no version constant or upgrade routine; the Pro bootstrap guard is in place.
$main = ddwcac_read( $root . 'functions.php' );
foreach ( [ 'DDWCAC_VERSION', 'ddwcac_maybe_upgrade', 'ddwcac_db_version', 'DDWCAC_PRO_URL' ] as $needle ) {
	foreach ( $php as $relative => $contents ) {
		if ( false !== strpos( $contents, $needle ) ) {
			$errors[] = "'{$needle}' is present in {$relative}.";
		}
	}
}

if ( false === strpos( $main, "class_exists( 'DDWCAC_Init' )" ) || false === strpos( $main, 'class DDWCAC_Free_Init' ) ) {
	$errors[] = 'functions.php must define DDWCAC_Free_Init and stand down when DDWCAC_Init (Pro) exists.';
}

if ( false === strpos( $main, "'ddwcac' !== \$prefix" ) ) {
	$errors[] = 'The framework loader must always load on the plugin\'s own pages.';
}

// 8. Release hygiene.
$readme = ddwcac_read( $root . 'readme.txt' );
preg_match( '/^\s*\*\s*Version:\s*(\S+)/m', $main, $version );
preg_match( '/^Stable tag:\s*(\S+)/m', $readme, $stable );
preg_match( '/^=== (.+) ===/m', $readme, $title );
preg_match( '/Plugin Name:\s*(.+)/', $main, $name );

if ( ( $version[1] ?? '' ) !== ( $stable[1] ?? '-' ) ) {
	$errors[] = 'readme.txt Stable tag does not match the plugin Version.';
}
if ( trim( $title[1] ?? '' ) !== trim( $name[1] ?? '-' ) ) {
	$errors[] = 'readme.txt title does not match the Plugin Name.';
}

foreach ( glob( $root . '{,*/,*/*/,*/*/*/}index.php', GLOB_BRACE ) as $stub ) {
	if ( 0 !== strpos( ddwcac_read( $stub ), '<?php' ) ) {
		$errors[] = str_replace( $root, '', $stub ) . ' does not start with <?php.';
	}
}

if ( $errors ) {
	echo "Free/Pro boundary check FAILED:\n - " . implode( "\n - ", array_unique( $errors ) ) . "\n";
	exit( 1 );
}

echo "Free/Pro boundary check passed.\n";
