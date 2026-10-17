<?php
/**
 * Verification: honeypot, provider checks and logging.
 *
 * @package Advanced CAPTCHA
 * @version 1.0.0
 */

namespace DDWCAdvancedCAPTCHA\Helper\Verification;

use DDWCAdvancedCAPTCHA\Helper\Logs\DDWCAC_Logs_Helper;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCAC_Verification_Helper' ) ) {
	/**
	 * Verification helper class
	 */
	class DDWCAC_Verification_Helper {
		/**
		 * Provider request field and verify endpoint.
		 */
		const PROVIDERS = [
			'builtin'    => [
				'field' => 'ddwcac_builtin_token',
				'url'   => '',
			],
			'google'     => [
				'field' => 'g-recaptcha-response',
				'url'   => 'https://www.%s/recaptcha/api/siteverify',
			],
			'cloudflare' => [
				'field' => 'cf-turnstile-response',
				'url'   => 'https://challenges.cloudflare.com/turnstile/v0/siteverify', // phpcs:ignore PluginCheck.CodeAnalysis.Offloading.OffloadedContent -- Server-side token verification endpoint, disclosed in readme.txt.
			],
			'h'          => [
				'field' => 'h-captcha-response',
				'url'   => 'https://api.hcaptcha.com/siteverify',
			],
		];

		/**
		 * How long a built-in challenge stays valid.
		 */
		const BUILTIN_TTL = 1800;

		/**
		 * Results already computed in this request, keyed by token. Login and lost password forms
		 * can run the check twice (WordPress and WooCommerce share hooks), and providers reject a
		 * token the second time, so the first answer is reused.
		 *
		 * @var array
		 */
		protected static $results = [];

		/**
		 * Configuration
		 *
		 * @var array
		 */
		protected $configuration;

		/**
		 * Construct
		 *
		 * @param array $configuration Plugin configuration.
		 */
		public function __construct( $configuration ) {
			$this->configuration = $configuration;
		}

		/**
		 * Verify the submission. Cheap local checks run first so obvious bots never cost a provider call.
		 *
		 * @param string     $form Form key, used for logging.
		 * @param array|null $data Submitted fields; defaults to $_POST.
		 * @return array{success:bool,reason:string,message:string}
		 */
		public function ddwcac_verify( $form = 'other', $data = null ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- The CAPTCHA is the request's proof; the host form verifies its own nonce.
			$data = null === $data ? wp_unslash( $_POST ) : (array) $data;
			$type = $this->configuration['captcha_type'];
			$ip   = ddwcac_get_ip_address();

			if ( empty( self::PROVIDERS[ $type ] ) || ! ddwcac_is_provider_ready( $type, $this->configuration ) ) {
				// Nothing to verify against; the front end never renders a widget in this state either.
				return $this->ddwcac_result( true, 'not-configured' );
			}

			$honeypot = $this->ddwcac_check_honeypot( $data );
			if ( $honeypot ) {
				return $this->ddwcac_finish( $form, 'failed', $honeypot, $ip );
			}

			$field = self::PROVIDERS[ $type ]['field'];
			$token = isset( $data[ $field ] ) && is_scalar( $data[ $field ] ) ? sanitize_text_field( (string) $data[ $field ] ) : '';

			if ( '' === $token ) {
				return $this->ddwcac_finish( $form, 'failed', 'missing-input-response', $ip );
			}

			if ( isset( self::$results[ $token ] ) ) {
				return self::$results[ $token ];
			}

			$result = 'builtin' === $type
				? $this->ddwcac_builtin_verify( $token, isset( $data['ddwcac_builtin_answer'] ) && is_scalar( $data['ddwcac_builtin_answer'] ) ? sanitize_text_field( (string) $data['ddwcac_builtin_answer'] ) : '' )
				: $this->ddwcac_remote_verify( $type, $token, $ip );

			self::$results[ $token ] = $this->ddwcac_finish( $form, $result['status'], $result['reason'], $ip );

			return self::$results[ $token ];
		}

		/**
		 * Test a provider's keys from the settings screen, without logging.
		 *
		 * @param string $type  Provider.
		 * @param array  $data  Posted preview fields.
		 * @return array{status:string,reason:string}
		 */
		public function ddwcac_test( $type, $data ) {
			$field = self::PROVIDERS[ $type ]['field'] ?? '';
			$token = isset( $data[ $field ] ) ? sanitize_text_field( (string) $data[ $field ] ) : '';

			if ( '' === $token ) {
				return [
					'status' => 'failed',
					'reason' => 'missing-input-response',
				];
			}

			return 'builtin' === $type
				? $this->ddwcac_builtin_verify( $token, sanitize_text_field( (string) ( $data['ddwcac_builtin_answer'] ?? '' ) ) )
				: $this->ddwcac_remote_verify( $type, $token, ddwcac_get_ip_address() );
		}

		/**
		 * Honeypot and minimum fill time. Returns a reason code on failure, '' when fine.
		 * Forms that cannot carry the fields (some API clients) fall through to the CAPTCHA.
		 *
		 * @param array $data Submitted fields.
		 * @return string
		 */
		protected function ddwcac_check_honeypot( $data ) {
			if ( 'yes' !== $this->configuration['honeypot_enabled'] ) {
				return '';
			}

			if ( ! empty( $data['ddwcac_hp'] ) ) {
				return 'honeypot';
			}

			$stamp = isset( $data['ddwcac_ts'] ) && is_scalar( $data['ddwcac_ts'] ) ? (string) $data['ddwcac_ts'] : '';

			if ( '' === $stamp ) {
				return '';
			}

			list( $time, $signature ) = array_pad( explode( '.', $stamp, 2 ), 2, '' );

			if ( ! ctype_digit( $time ) || ! hash_equals( ddwcac_sign( $time ), $signature ) ) {
				return 'honeypot';
			}

			return time() - (int) $time < (int) $this->configuration['honeypot_min_time'] ? 'too-fast' : '';
		}

		/**
		 * Built-in challenge check: signature, age and single use.
		 *
		 * @param string $token  Signed token.
		 * @param string $answer Visitor's answer.
		 * @return array{status:string,reason:string}
		 */
		protected function ddwcac_builtin_verify( $token, $answer ) {
			list( $issued, $nonce, $signature ) = array_pad( explode( '.', $token, 3 ), 3, '' );

			if ( ! ctype_digit( $issued ) || ! $nonce || time() - (int) $issued > self::BUILTIN_TTL ) {
				return [
					'status' => 'failed',
					'reason' => 'builtin-expired',
				];
			}

			if ( '' === $answer || ! hash_equals( ddwcac_sign( $issued . '|' . $nonce . '|' . strtolower( trim( $answer ) ) ), $signature ) ) {
				return [
					'status' => 'failed',
					'reason' => '' === $answer ? 'missing-input-response' : 'builtin-wrong',
				];
			}

			// A solved challenge is good once; remember it until it would have expired anyway.
			$used = 'ddwcac_used_' . md5( $nonce );
			if ( false !== get_transient( $used ) ) {
				return [
					'status' => 'failed',
					'reason' => 'builtin-reused',
				];
			}
			set_transient( $used, 1, self::BUILTIN_TTL );

			return [
				'status' => 'passed',
				'reason' => '',
			];
		}

		/**
		 * Ask the provider about a token.
		 *
		 * @param string $type  Provider key.
		 * @param string $token Response token.
		 * @param string $ip    Visitor IP.
		 * @return array{status:string,reason:string}
		 */
		protected function ddwcac_remote_verify( $type, $token, $ip ) {
			$response = wp_remote_post(
				sprintf( self::PROVIDERS[ $type ]['url'], $this->configuration['google_domain'] ),
				[
					'timeout' => 10,
					'body'    => [
						'secret'   => $this->configuration[ "{$type}_site_secret" ],
						'response' => $token,
						'remoteip' => $ip,
					],
				]
			);

			$body = is_wp_error( $response ) ? null : json_decode( wp_remote_retrieve_body( $response ), true );

			if ( ! is_array( $body ) || ! isset( $body['success'] ) ) {
				// The provider is down or unreachable: let the store owner choose availability over strictness.
				return [
					'status' => 'yes' === $this->configuration['fail_open'] ? 'error' : 'failed',
					'reason' => 'provider-unreachable',
				];
			}

			if ( ! $body['success'] ) {
				return [
					'status' => 'failed',
					'reason' => ! empty( $body['error-codes'] ) ? (string) reset( $body['error-codes'] ) : 'invalid-input-response',
				];
			}

			if ( 'google' === $type && 'v3' === $this->configuration['google_captcha_version'] && isset( $body['score'] ) && (float) $body['score'] < (float) $this->configuration['google_v3_score'] ) {
				return [
					'status' => 'failed',
					'reason' => 'low-score',
				];
			}

			return [
				'status' => 'passed',
				'reason' => '',
			];
		}

		/**
		 * Log the attempt and shape the result.
		 *
		 * @param string $form   Form key.
		 * @param string $status Log status.
		 * @param string $reason Reason code.
		 * @param string $ip     Visitor IP.
		 * @return array
		 */
		public function ddwcac_finish( $form, $status, $reason, $ip ) {
			$this->ddwcac_log( $form, $status, $reason, $ip );

			return $this->ddwcac_result( in_array( $status, [ 'passed', 'error' ], true ), $reason );
		}

		/**
		 * Result array with the message the visitor should see.
		 *
		 * @param bool   $success Passed or not.
		 * @param string $reason  Reason code.
		 * @return array
		 */
		protected function ddwcac_result( $success, $reason ) {
			$message = $this->configuration['error_message'];

			if ( 'builtin-wrong' === $reason ) {
				$message = esc_html__( 'The CAPTCHA answer is not correct. Please try the new challenge.', 'advanced-captcha' );
			} elseif ( 'builtin-expired' === $reason ) {
				$message = esc_html__( 'The CAPTCHA expired. Please answer the new challenge.', 'advanced-captcha' );
			}

			return [
				'success' => $success,
				'reason'  => $reason,
				'message' => $message,
			];
		}

		/**
		 * Write a log row when logging is on.
		 *
		 * @param string $form   Form key.
		 * @param string $status Status.
		 * @param string $reason Reason.
		 * @param string $ip     IP.
		 * @return void
		 */
		public function ddwcac_log( $form, $status, $reason, $ip ) {
			if ( 'yes' !== $this->configuration['logging_enabled'] ) {
				return;
			}

			( new DDWCAC_Logs_Helper() )->ddwcac_insert_log(
				[
					'form'     => $form,
					'provider' => $this->configuration['captcha_type'],
					'status'   => $status,
					'reason'   => $reason,
					'ip'       => $ip,
				]
			);
		}
	}
}
