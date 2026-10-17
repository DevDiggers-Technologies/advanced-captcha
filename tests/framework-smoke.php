<?php
/** Run with php -d zend.assertions=1 tests/framework-smoke.php. */

define( 'ABSPATH', __DIR__ . '/' );
$options = [];
function trailingslashit( $value ) {
	return rtrim( $value, '/' ) . '/';
}
function get_editable_roles() {
	return [];
}
function get_option( $name, $default = false ) {
	global $options;
	return $options[ $name ] ?? $default;
}
function update_option( $name, $value ) {
	global $options;
	$options[ $name ] = $value;
}
function sanitize_text_field( $value ) {
	return trim( (string) $value );
}
function __( $message ) {
	return $message;
}
function esc_html__( $message ) {
	return $message;
}
function apply_filters( $hook, $value ) {
	return $value;
}
define( 'DDWCAC_PLUGIN_FILE', dirname( __DIR__ ) . '/' );
class WP_Error {
	public function __construct( public $code, public $message ) {}
}

require dirname( __DIR__ ) . '/autoload/autoload.php';
require dirname( __DIR__ ) . '/includes/global-functions.php';

$wizard = ( new ReflectionClass( 'DDWCAdvancedCAPTCHA\\Includes\\Admin\\DDWCAC_Setup_Wizard' ) )->newInstanceWithoutConstructor();
$form   = [
	[
		'name'  => '_ddwcac_enabled',
		'value' => 'yes',
	],
	[
		'name'  => '_ddwcac_captcha_type',
		'value' => 'cloudflare',
	],
];
assert( $wizard->save_general( $form ) instanceof WP_Error );
assert( get_option( '_ddwcac_enabled' ) === false );
$form[] = [
	'name'  => '_ddwcac_cloudflare_site_key',
	'value' => 'public',
];
$form[] = [
	'name'  => '_ddwcac_cloudflare_site_secret',
	'value' => 'private',
];
assert( $wizard->save_general( $form ) === true );
assert( get_option( '_ddwcac_enabled' ) === 'yes' );
// The built-in CAPTCHA needs no keys.
assert( $wizard->save_general( [ [ 'name' => '_ddwcac_captcha_type', 'value' => 'builtin' ] ] ) === true );
assert( get_option( '_ddwcac_captcha_type' ) === 'builtin' );
echo "Advanced CAPTCHA framework smoke check passed.\n";
