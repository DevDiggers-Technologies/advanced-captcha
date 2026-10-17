<?php
/** Run with php -d zend.assertions=1 tests/ip-rules-check.php. */

define( 'ABSPATH', __DIR__ . '/' );
require dirname( __DIR__ ) . '/includes/global-functions.php';

$rules = "203.0.113.5\n198.51.100.*\n10.0.0.0/8\n2001:db8::/32\nnot-an-ip\n192.168.1.0/33";

assert( ddwcac_ip_in_list( '203.0.113.5', $rules ) );
assert( ! ddwcac_ip_in_list( '203.0.113.6', $rules ) );
assert( ddwcac_ip_in_list( '198.51.100.77', $rules ) );
assert( ! ddwcac_ip_in_list( '198.51.101.77', $rules ) );
assert( ddwcac_ip_in_list( '10.255.1.2', $rules ) );
assert( ! ddwcac_ip_in_list( '11.0.0.1', $rules ) );
assert( ddwcac_ip_in_list( '2001:db8:abcd::1', $rules ) );
assert( ! ddwcac_ip_in_list( '2001:db9::1', $rules ) );
assert( ! ddwcac_ip_in_list( '192.168.1.5', $rules ) ); // Invalid /33 rule is ignored.
assert( ddwcac_ip_in_list( '172.16.5.4', '172.16.4.0/23' ) );
assert( ! ddwcac_ip_in_list( '172.16.6.4', '172.16.4.0/23' ) );
assert( ! ddwcac_ip_in_list( '', $rules ) );
echo "IP rule checks passed.\n";
