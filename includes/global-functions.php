<?php
/**
 * Global Functions
 *
 * @package Advanced CAPTCHA
 * @version 1.0.0
 */

use DDWCAdvancedCAPTCHA\Helper\Verification\DDWCAC_Verification_Helper;

defined( 'ABSPATH' ) || exit();

if ( ! function_exists( 'ddwcac_get_option_defaults' ) ) {
	/**
	 * Every option the plugin stores, keyed without the `_ddwcac_` prefix, with its first-install default.
	 * A default only applies while the option was never saved, so existing stores keep their choices.
	 *
	 * @return array
	 */
	function ddwcac_get_option_defaults() {
		return [
			// General.
			'enabled'                            => 'yes',
			'captcha_type'                       => 'builtin',
			'language'                           => 'auto',
			'error_message'                      => esc_html__( 'Please complete the CAPTCHA to verify that you are not a robot.', 'advanced-captcha' ),
			'excluded_user_roles'                => [],
			'whitelisted_ips'                    => '',

			// Forms.
			'wordpress_forms'                    => [ 'login', 'registration', 'lost_password', 'post_comment' ],
			'woocommerce_forms'                  => [ 'login', 'registration', 'lost_password', 'guest_checkout', 'review_product' ],
			'woocommerce_checkout_form_position' => 'before_place_order',
			'wpcf7_captcha_enabled'              => 'yes',
			'integrations'                       => array_keys( ddwcac_get_integrations() ),
			'shortcode'                          => '[ddwcac_captcha]',

			// Bot defense.
			'honeypot_enabled'                   => 'yes',
			'honeypot_min_time'                  => 2,

			// Visitor IP.
			'ip_header'                          => 'REMOTE_ADDR',

			// Advanced.
			'fail_open'                          => 'yes',
			'logging_enabled'                    => 'yes',

			// Built-in CAPTCHA.
			'builtin_mode'                       => 'math',
			'builtin_label'                      => esc_html__( 'Prove you are human', 'advanced-captcha' ),

			// Google reCAPTCHA.
			'google_captcha_version'             => 'v2_checkbox',
			'google_domain'                      => 'google.com',
			'google_captcha_size'                => 'normal',
			'google_captcha_theme'               => 'light',
			'google_disable_submit'              => '',
			'google_v3_score'                    => '0.5',
			'google_hide_badge'                  => '',
			'google_site_key'                    => '',
			'google_site_secret'                 => '',

			// Cloudflare Turnstile.
			'cloudflare_captcha_theme'           => 'auto',
			'cloudflare_captcha_size'            => 'normal',
			'cloudflare_disable_submit'          => '',
			'cloudflare_site_key'                => '',
			'cloudflare_site_secret'             => '',

			// hCaptcha.
			'h_captcha_theme'                    => 'light',
			'h_captcha_size'                     => 'normal',
			'h_disable_submit'                   => '',
			'h_site_key'                         => '',
			'h_site_secret'                      => '',
		];
	}
}

if ( ! function_exists( 'ddwcac_get_integrations' ) ) {
	/**
	 * Form plugins protected out of the box: key => [ label, class or function that proves it is active ].
	 *
	 * @return array
	 */
	function ddwcac_get_integrations() {
		return [
			'wpforms'    => [ 'WPForms', 'wpforms' ],
			'fluentform' => [ 'Fluent Forms', 'FluentForm\App\Modules\Form\Form' ],
		];
	}
}

if ( ! function_exists( 'ddwcac_is_integration_active' ) ) {
	/**
	 * Whether a form plugin is installed and active.
	 *
	 * @param string $key Integration key.
	 * @return bool
	 */
	function ddwcac_is_integration_active( $key ) {
		$probe = ddwcac_get_integrations()[ $key ][1] ?? '';

		return $probe && ( class_exists( $probe ) || function_exists( $probe ) );
	}
}

if ( ! function_exists( 'ddwcac_kses_captcha' ) ) {
	/**
	 * Tags the CAPTCHA markup needs (inputs, the built-in SVG), for wp_kses().
	 *
	 * @return array
	 */
	function ddwcac_kses_captcha() {
		$global = [
			'class'       => true,
			'id'          => true,
			'style'       => true,
			'aria-hidden' => true,
			'aria-label'  => true,
			'role'        => true,
		];

		return [
			'div'    => $global + [
				'data-version'  => true,
				'data-theme'    => true,
				'data-sitekey'  => true,
				'data-size'     => true,
				'data-language' => true,
				'data-issued'   => true,
			],
			'span'   => $global,
			'label'  => $global + [ 'for' => true ],
			'br'     => [],
			'button' => $global + [
				'type'     => true,
				'title'    => true,
				'tabindex' => true,
			],
			'input'  => $global + [
				'type'         => true,
				'name'         => true,
				'value'        => true,
				'autocomplete' => true,
				'tabindex'     => true,
				'inputmode'    => true,
				'maxlength'    => true,
				'placeholder'  => true,
				'required'     => true,
				'spellcheck'   => true,
			],
			'svg'    => $global + [
				'xmlns'   => true,
				'viewbox' => true,
				'width'   => true,
				'height'  => true,
				'fill'    => true,
			],
			'g'      => [ 'transform' => true ],
			'text'   => [
				'x'           => true,
				'y'           => true,
				'fill'        => true,
				'font-size'   => true,
				'font-family' => true,
				'font-weight' => true,
				'transform'   => true,
			],
			'line'   => [
				'x1'           => true,
				'y1'           => true,
				'x2'           => true,
				'y2'           => true,
				'stroke'       => true,
				'stroke-width' => true,
			],
			'circle' => [
				'cx'   => true,
				'cy'   => true,
				'r'    => true,
				'fill' => true,
			],
			'path'   => [
				'd'               => true,
				'stroke'          => true,
				'stroke-width'    => true,
				'fill'            => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
			],
		];
	}
}

if ( ! function_exists( 'ddwcac_get_captcha_field' ) ) {
	/**
	 * CAPTCHA markup for the selected provider, always preceded by the invisible honeypot.
	 *
	 * @param string      $style   Inline style for the wrapper.
	 * @param string|null $type    Provider override.
	 * @param bool        $preview Admin preview: always draw the widget.
	 * @return string
	 */
	function ddwcac_get_captcha_field( $style = '', $type = null, $preview = false ) {
		global $ddwcac_configuration;

		$config = $ddwcac_configuration;
		$type   = $type ? $type : $config['captcha_type'];
		$html   = ddwcac_get_honeypot_field();

		// Smart mode: a visitor with no failures behind them only gets the invisible checks.
		if ( 'builtin' === $type ) {
			// The -15px pull only widens third-party widgets to fit wp-login; the built-in field fits as is.
			return $html . ddwcac_get_builtin_field( false === strpos( $style, 'margin-left' ) ? $style : '' );
		}

		if ( ! ddwcac_is_provider_ready( $type, $config ) ) {
			return $html;
		}

		$handles = [
			'google'     => 'google-recaptcha',
			'cloudflare' => 'cloudflare-captcha',
			'h'          => 'hcaptcha',
		];

		if ( isset( $handles[ $type ] ) ) {
			wp_enqueue_script( $handles[ $type ] );
		}

		ob_start();

		if ( 'google' === $type ) {
			$version = $config['google_captcha_version'];
			?>
			<div class="ddwcac-captcha-wrapper g-recaptcha" style="<?php echo esc_attr( $style ); ?>" data-version="<?php echo esc_attr( $version ); ?>" data-theme="<?php echo esc_attr( $config['google_captcha_theme'] ); ?>" data-sitekey="<?php echo esc_attr( $config['google_site_key'] ); ?>" data-size="<?php echo esc_attr( 'v2_checkbox' === $version ? $config['google_captcha_size'] : 'invisible' ); ?>"></div>
			<?php
			if ( 'v3' === $version && 'yes' === $config['google_hide_badge'] ) {
				printf(
					'<span class="ddwcac-recaptcha-notice">%s</span>',
					wp_kses(
						/* translators: 1: privacy policy link, 2: terms link. */
						sprintf( __( 'This site is protected by reCAPTCHA and the Google %1$s and %2$s apply.', 'advanced-captcha' ), '<a href="https://policies.google.com/privacy" target="_blank" rel="noopener">' . __( 'Privacy Policy', 'advanced-captcha' ) . '</a>', '<a href="https://policies.google.com/terms" target="_blank" rel="noopener">' . __( 'Terms of Service', 'advanced-captcha' ) . '</a>' ),
						[
							'a' => [
								'href'   => true,
								'target' => true,
								'rel'    => true,
							],
						]
					)
				);
			}
		} elseif ( 'cloudflare' === $type ) {
			?>
			<div class="ddwcac-captcha-wrapper cf-turnstile" style="<?php echo esc_attr( $style ); ?>" data-theme="<?php echo esc_attr( $config['cloudflare_captcha_theme'] ); ?>" data-size="<?php echo esc_attr( $config['cloudflare_captcha_size'] ); ?>" data-language="<?php echo esc_attr( strtolower( ddwcac_get_language() ) ); ?>" data-sitekey="<?php echo esc_attr( $config['cloudflare_site_key'] ); ?>"></div>
			<?php
		} elseif ( 'h' === $type ) {
			?>
			<div class="ddwcac-captcha-wrapper h-captcha" style="<?php echo esc_attr( $style ); ?>" data-sitekey="<?php echo esc_attr( $config['h_site_key'] ); ?>" data-theme="<?php echo esc_attr( $config['h_captcha_theme'] ); ?>" data-size="<?php echo esc_attr( $config['h_captcha_size'] ); ?>"></div>
			<?php
		}

		return $html . ob_get_clean();
	}
}

if ( ! function_exists( 'ddwcac_is_provider_ready' ) ) {
	/**
	 * Whether a provider can render: the built-in CAPTCHA always can, the others need both keys.
	 *
	 * @param string $type   Provider.
	 * @param array  $config Configuration.
	 * @return bool
	 */
	function ddwcac_is_provider_ready( $type, $config ) {
		return 'builtin' === $type || ( ! empty( $config[ "{$type}_site_key" ] ) && ! empty( $config[ "{$type}_site_secret" ] ) );
	}
}

if ( ! function_exists( 'ddwcac_get_honeypot_field' ) ) {
	/**
	 * Invisible honeypot plus a signed render time. Humans never see or fill it; bots that fill
	 * every field, or submit within a second or two, give themselves away.
	 *
	 * @return string
	 */
	function ddwcac_get_honeypot_field() {
		global $ddwcac_configuration;

		if ( 'yes' !== $ddwcac_configuration['honeypot_enabled'] ) {
			return '';
		}

		$time = time();

		return '<span class="ddwcac-hp" aria-hidden="true" style="display:none"><label for="ddwcac-hp-' . esc_attr( $time ) . '">' . esc_html__( 'Leave this field empty', 'advanced-captcha' ) . '</label><input type="text" id="ddwcac-hp-' . esc_attr( $time ) . '" name="ddwcac_hp" value="" tabindex="-1" autocomplete="off" /></span><input type="hidden" name="ddwcac_ts" value="' . esc_attr( $time . '.' . ddwcac_sign( (string) $time ) ) . '" />';
	}
}

if ( ! function_exists( 'ddwcac_sign' ) ) {
	/**
	 * Short HMAC used to sign honeypot timestamps and built-in challenges.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	function ddwcac_sign( $value ) {
		return substr( hash_hmac( 'sha256', $value, wp_salt( 'nonce' ) . 'ddwcac' ), 0, 32 );
	}
}

if ( ! function_exists( 'ddwcac_create_builtin_challenge' ) ) {
	/**
	 * A fresh built-in challenge: the question and a signed, stateless token.
	 *
	 * @return array{question:string,token:string}
	 */
	function ddwcac_create_builtin_challenge() {
		global $ddwcac_configuration;

		if ( 'text' === $ddwcac_configuration['builtin_mode'] ) {
			$alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
			$answer   = '';
			for ( $i = 0; $i < 5; $i++ ) {
				$answer .= $alphabet[ wp_rand( 0, strlen( $alphabet ) - 1 ) ];
			}
			$question = $answer;
		} else {
			$a  = wp_rand( 2, 12 );
			$b  = wp_rand( 1, 9 );
			$op = [ '+', '-', '×' ][ wp_rand( 0, 2 ) ];
			if ( '-' === $op && $b > $a ) {
				list( $a, $b ) = [ $b, $a ];
			}
			$answer   = (string) ( '+' === $op ? $a + $b : ( '-' === $op ? $a - $b : $a * $b ) );
			$question = "{$a} {$op} {$b} =";
		}

		$issued = time();
		$nonce  = wp_generate_password( 12, false );

		return [
			'question' => $question,
			'token'    => $issued . '.' . $nonce . '.' . ddwcac_sign( $issued . '|' . $nonce . '|' . strtolower( $answer ) ),
		];
	}
}

if ( ! function_exists( 'ddwcac_get_builtin_image' ) ) {
	/**
	 * Draw the challenge as a noisy SVG with every character offset and rotated.
	 * ponytail: SVG text is still in the DOM, so a determined OCR bot can read it; the invisible
	 * honeypot carries the weight against those. Swap in a GD raster if that matters.
	 *
	 * @param string $question Question text.
	 * @return string
	 */
	function ddwcac_get_builtin_image( $question ) {
		$chars  = preg_split( '//u', $question, -1, PREG_SPLIT_NO_EMPTY );
		$width  = max( 120, count( $chars ) * 18 + 20 );
		$colors = [ '#1e3a8a', '#0f766e', '#7c2d12', '#581c87', '#1f2937' ];
		$svg    = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $width . ' 44" width="' . $width . '" height="44" role="img" aria-label="' . esc_attr__( 'CAPTCHA challenge', 'advanced-captcha' ) . '">';

		for ( $i = 0; $i < 6; $i++ ) {
			$svg .= '<line x1="' . wp_rand( 0, $width ) . '" y1="' . wp_rand( 0, 44 ) . '" x2="' . wp_rand( 0, $width ) . '" y2="' . wp_rand( 0, 44 ) . '" stroke="' . $colors[ wp_rand( 0, 4 ) ] . '" stroke-width="1" />';
		}
		for ( $i = 0; $i < 18; $i++ ) {
			$svg .= '<circle cx="' . wp_rand( 0, $width ) . '" cy="' . wp_rand( 0, 44 ) . '" r="1" fill="' . $colors[ wp_rand( 0, 4 ) ] . '" />';
		}

		$x = 12;
		foreach ( $chars as $char ) {
			if ( ' ' === $char ) {
				$x += 8;
				continue;
			}
			$y    = wp_rand( 28, 34 );
			$svg .= '<text x="' . $x . '" y="' . $y . '" fill="' . $colors[ wp_rand( 0, 4 ) ] . '" font-size="' . wp_rand( 20, 25 ) . '" font-family="Georgia, serif" font-weight="700" transform="rotate(' . wp_rand( -18, 18 ) . ' ' . $x . ' ' . $y . ')">' . esc_html( $char ) . '</text>';
			$x   += 18;
		}

		return $svg . '</svg>';
	}
}

if ( ! function_exists( 'ddwcac_get_builtin_field' ) ) {
	/**
	 * Built-in CAPTCHA markup.
	 *
	 * @param string $style Inline style.
	 * @return string
	 */
	function ddwcac_get_builtin_field( $style = '' ) {
		global $ddwcac_configuration;

		$challenge = ddwcac_create_builtin_challenge();
		$id        = 'ddwcac-builtin-' . wp_rand( 1000, 999999 );
		$numeric   = 'math' === $ddwcac_configuration['builtin_mode'];

		ob_start();
		?>
		<div class="ddwcac-captcha-wrapper ddwcac-builtin" style="<?php echo esc_attr( $style ); ?>" data-issued="<?php echo esc_attr( time() ); ?>">
			<label class="ddwcac-builtin-label" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $ddwcac_configuration['builtin_label'] ); ?></label>
			<span class="ddwcac-builtin-row">
				<span class="ddwcac-builtin-image"><?php echo ddwcac_get_builtin_image( $challenge['question'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from escaped parts above. ?></span>
				<button type="button" class="ddwcac-builtin-refresh" title="<?php esc_attr_e( 'New challenge', 'advanced-captcha' ); ?>" aria-label="<?php esc_attr_e( 'New challenge', 'advanced-captcha' ); ?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="none" aria-hidden="true"><path d="M21 12a9 9 0 1 1-3-6.7L21 8M21 3v5h-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
				<input type="text" id="<?php echo esc_attr( $id ); ?>" class="ddwcac-builtin-answer" name="ddwcac_builtin_answer" value="" autocomplete="off" spellcheck="false" maxlength="6" inputmode="<?php echo esc_attr( $numeric ? 'numeric' : 'text' ); ?>" placeholder="<?php echo $numeric ? esc_attr__( 'Answer', 'advanced-captcha' ) : esc_attr__( 'Type the characters', 'advanced-captcha' ); ?>" required />
			</span>
			<input type="hidden" class="ddwcac-builtin-token" name="ddwcac_builtin_token" value="<?php echo esc_attr( $challenge['token'] ); ?>" />
		</div>
		<?php
		return ob_get_clean();
	}
}

if ( ! function_exists( 'ddwcac_get_language' ) ) {
	/**
	 * Widget language: the saved choice, or the site/WPML language on auto, as a BCP 47 tag.
	 *
	 * @return string
	 */
	function ddwcac_get_language() {
		global $ddwcac_configuration;

		$language = 'auto' === $ddwcac_configuration['language'] ? get_locale() : $ddwcac_configuration['language'];

		/**
		 * WPML multilingual hook to get the current selected language.
		 *
		 * @since 1.0.0
		 */
		$language = apply_filters( 'wpml_current_language', $language ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML's own filter.

		return str_replace( '_', '-', (string) $language );
	}
}

if ( ! function_exists( 'ddwcac_register_front_scripts' ) ) {
	/**
	 * Register the plugin script and the provider script for a configuration.
	 *
	 * @param array $config Configuration (the admin preview passes a provider override).
	 * @return void
	 */
	function ddwcac_register_front_scripts( $config ) {
		$locale = ddwcac_get_language();
		$type   = $config['captcha_type'];

		wp_register_style( 'ddwcac-front-style', DDWCAC_PLUGIN_URL . 'assets/css/front.css', [], filemtime( DDWCAC_PLUGIN_FILE . 'assets/css/front.css' ) );
		wp_enqueue_style( 'ddwcac-front-style' );
		wp_register_script( 'ddwcac-front-script', DDWCAC_PLUGIN_URL . 'assets/js/front.js', [ 'jquery' ], filemtime( DDWCAC_PLUGIN_FILE . 'assets/js/front.js' ), true );

		// phpcs:disable WordPress.WP.EnqueuedResourceParameters.MissingVersion, PluginCheck.CodeAnalysis.EnqueuedResourceOffloading.OffloadedContent -- The CAPTCHA provider's own widget script (a service the site owner chose and configured), which cannot be bundled. Disclosed in readme.txt. A version query would break its cache.
		if ( 'google' === $type ) {
			$args = 'v3' === $config['google_captcha_version']
				? [
					'hl'     => $locale,
					'render' => $config['google_site_key'],
				]
				: [
					'hl'     => $locale,
					'onload' => 'ddwcacRecaptchaCallback',
					'render' => 'explicit',
				];

			wp_register_script( 'google-recaptcha', add_query_arg( $args, "https://www.{$config['google_domain']}/recaptcha/api.js" ), [ 'ddwcac-front-script' ], null, true );
		} elseif ( 'cloudflare' === $type ) {
			wp_register_script(
				'cloudflare-captcha',
				add_query_arg(
					[
						'onload' => 'ddwcacOnloadTurnstileCallback',
						'render' => 'explicit',
					],
					'https://challenges.cloudflare.com/turnstile/v0/api.js'
				),
				[ 'ddwcac-front-script' ],
				null,
				true
			);
		} elseif ( 'h' === $type ) {
			wp_register_script(
				'hcaptcha',
				add_query_arg(
					[
						'hl'              => $locale,
						'onload'          => 'ddwcacOnloadHCaptcha',
						'recaptchacompat' => 'off',
						'render'          => 'explicit',
					],
					'https://js.hcaptcha.com/1/api.js'
				),
				[ 'ddwcac-front-script' ],
				null,
				true
			);
		}
		// phpcs:enable

		$public_keys = [ 'captcha_type', 'woocommerce_forms', 'woocommerce_checkout_form_position', 'google_captcha_version', 'google_captcha_theme', 'google_captcha_size', 'google_disable_submit', 'google_site_key', 'cloudflare_captcha_theme', 'cloudflare_captcha_size', 'cloudflare_disable_submit', 'cloudflare_site_key', 'h_captcha_theme', 'h_captcha_size', 'h_disable_submit', 'h_site_key' ];

		wp_localize_script(
			'ddwcac-front-script',
			'ddwcacFrontObject',
			[
				// Only what the widgets need: secrets and security rules never reach the browser.
				'ddwcac_configuration' => array_intersect_key( $config, array_flip( $public_keys ) ) + [ 'language' => $locale ],
				'captcha_html'         => ddwcac_get_captcha_field( '', $type ),
				'ajaxUrl'              => admin_url( 'admin-ajax.php' ),
			]
		);

		wp_enqueue_script( 'ddwcac-front-script' );
	}
}

if ( ! function_exists( 'ddwcac_check_captcha_response' ) ) {
	/**
	 * Verify the CAPTCHA posted with the current request.
	 *
	 * @param string     $form Form key used in logs.
	 * @param array|null $data Submitted fields; defaults to $_POST (form plugins that post JSON pass their own).
	 * @return array{success:bool,reason:string,message:string}
	 */
	function ddwcac_check_captcha_response( $form = 'other', $data = null ) {
		global $ddwcac_configuration;

		$result = ( new DDWCAC_Verification_Helper( $ddwcac_configuration ) )->ddwcac_verify( $form, $data );

		/**
		 * Filter the verification result.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $result Result with success, reason and message.
		 * @param string $form   Form key.
		 */
		return apply_filters( 'ddwcac_verification_result', $result, $form );
	}
}

if ( ! function_exists( 'ddwcac_get_ip_address' ) ) {
	/**
	 * Visitor IP. Proxy headers are read only when the admin says the site sits behind that proxy,
	 * because anyone can forge them otherwise.
	 *
	 * @return string
	 */
	function ddwcac_get_ip_address() {
		global $ddwcac_configuration;

		$header = $ddwcac_configuration['ip_header'] ?? 'REMOTE_ADDR';
		$header = in_array( $header, [ 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP' ], true ) ? $header : 'REMOTE_ADDR';
		$value  = isset( $_SERVER[ $header ] ) ? sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) ) : '';

		if ( ! $value && 'REMOTE_ADDR' !== $header && isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$value = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		// X-Forwarded-For holds a chain; the client is the first entry.
		$ip = trim( explode( ',', $value )[0] );

		return (string) filter_var( $ip, FILTER_VALIDATE_IP );
	}
}

if ( ! function_exists( 'ddwcac_ip_in_list' ) ) {
	/**
	 * Whether an IP matches a list of rules: exact IPs, IPv4 wildcards (192.168.1.*) or CIDR ranges (IPv4/IPv6).
	 *
	 * @param string       $ip    IP to test.
	 * @param string|array $rules Newline/comma separated rules or an array.
	 * @return bool
	 */
	function ddwcac_ip_in_list( $ip, $rules ) {
		if ( ! $ip || empty( $rules ) ) {
			return false;
		}

		$rules  = is_array( $rules ) ? $rules : preg_split( '/[\s,]+/', (string) $rules, -1, PREG_SPLIT_NO_EMPTY );
		$packed = inet_pton( $ip );

		foreach ( $rules as $rule ) {
			$rule = trim( $rule );

			if ( $rule === $ip ) {
				return true;
			}

			if ( false !== strpos( $rule, '*' ) ) {
				if ( preg_match( '/^' . str_replace( '\*', '\d{1,3}', preg_quote( $rule, '/' ) ) . '$/', $ip ) ) {
					return true;
				}
				continue;
			}

			if ( false === strpos( $rule, '/' ) ) {
				continue;
			}

			list( $subnet, $bits ) = explode( '/', $rule, 2 );

			if ( ! filter_var( $subnet, FILTER_VALIDATE_IP ) || ! is_numeric( $bits ) ) {
				continue;
			}

			$subnet = inet_pton( $subnet );
			$bits   = (int) $bits;

			if ( strlen( $subnet ) !== strlen( $packed ) || $bits < 0 || $bits > strlen( $packed ) * 8 ) {
				continue;
			}

			$bytes = intdiv( $bits, 8 );
			$rest  = $bits % 8;

			if ( substr( $packed, 0, $bytes ) !== substr( $subnet, 0, $bytes ) ) {
				continue;
			}

			if ( $rest && ( ( ord( $packed[ $bytes ] ) ^ ord( $subnet[ $bytes ] ) ) & ( 0xFF << ( 8 - $rest ) ) & 0xFF ) ) {
				continue;
			}

			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'ddwcac_get_form_labels' ) ) {
	/**
	 * Readable names for the form keys stored in logs.
	 *
	 * @return array
	 */
	function ddwcac_get_form_labels() {
		$labels = [
			'login'              => esc_html__( 'Login', 'advanced-captcha' ),
			'registration'       => esc_html__( 'Registration', 'advanced-captcha' ),
			'lost_password'      => esc_html__( 'Lost Password', 'advanced-captcha' ),
			'comment'            => esc_html__( 'Comment', 'advanced-captcha' ),
			'review'             => esc_html__( 'Product Review', 'advanced-captcha' ),
			'checkout'           => esc_html__( 'Checkout', 'advanced-captcha' ),
			'pay_for_order'      => esc_html__( 'Pay for Order', 'advanced-captcha' ),
			'add_payment_method' => esc_html__( 'Add Payment Method', 'advanced-captcha' ),
			'account_details'    => esc_html__( 'Account Details', 'advanced-captcha' ),
			'address'            => esc_html__( 'Address', 'advanced-captcha' ),
			'contact_form_7'     => esc_html__( 'Contact Form 7', 'advanced-captcha' ),
		];

		foreach ( ddwcac_get_integrations() as $key => $integration ) {
			$labels[ $key ] = $integration[0];
		}

		$labels['other'] = esc_html__( 'Other', 'advanced-captcha' );

		/**
		 * Filter the form labels used in logs and the dashboard.
		 *
		 * @since 1.0.0
		 */
		return apply_filters( 'ddwcac_form_labels', $labels );
	}
}

if ( ! function_exists( 'ddwcac_get_status_labels' ) ) {
	/**
	 * Readable log statuses.
	 *
	 * @return array
	 */
	function ddwcac_get_status_labels() {
		return [
			'passed' => esc_html__( 'Passed', 'advanced-captcha' ),
			'failed' => esc_html__( 'Failed', 'advanced-captcha' ),
			'error'  => esc_html__( 'Provider Error', 'advanced-captcha' ),
		];
	}
}

if ( ! function_exists( 'ddwcac_get_reason_labels' ) ) {
	/**
	 * Readable text for every reason code.
	 *
	 * @return array
	 */
	function ddwcac_get_reason_labels() {
		return [
			''                        => esc_html__( 'Verified', 'advanced-captcha' ),
			'missing-input-response'  => esc_html__( 'CAPTCHA not completed', 'advanced-captcha' ),
			'invalid-input-response'  => esc_html__( 'Invalid or expired token', 'advanced-captcha' ),
			'timeout-or-duplicate'    => esc_html__( 'Token expired or reused', 'advanced-captcha' ),
			'missing-input-secret'    => esc_html__( 'Secret key missing', 'advanced-captcha' ),
			'invalid-input-secret'    => esc_html__( 'Secret key invalid', 'advanced-captcha' ),
			'sitekey-secret-mismatch' => esc_html__( 'Site key and secret do not match', 'advanced-captcha' ),
			'low-score'               => esc_html__( 'reCAPTCHA v3 score too low', 'advanced-captcha' ),
			'provider-unreachable'    => esc_html__( 'Provider unreachable', 'advanced-captcha' ),
			'builtin-wrong'           => esc_html__( 'Wrong answer', 'advanced-captcha' ),
			'builtin-expired'         => esc_html__( 'Challenge expired', 'advanced-captcha' ),
			'builtin-reused'          => esc_html__( 'Challenge reused', 'advanced-captcha' ),
			'honeypot'                => esc_html__( 'Honeypot filled', 'advanced-captcha' ),
			'too-fast'                => esc_html__( 'Submitted too fast', 'advanced-captcha' ),
		];
	}
}

if ( ! function_exists( 'ddwcac_get_reason' ) ) {
	/**
	 * Readable text for a verification reason code.
	 *
	 * @param string $code Reason code.
	 * @return string
	 */
	function ddwcac_get_reason( $code ) {
		// Codes this plugin does not write (an earlier Pro install) read as plain words.
		return ddwcac_get_reason_labels()[ $code ] ?? ucfirst( str_replace( '-', ' ', (string) $code ) );
	}
}

if ( ! function_exists( 'ddwcac_get_capability' ) ) {
	/**
	 * Capability that manages the plugin: Shop Managers on a store, administrators on any other site.
	 *
	 * @return string
	 */
	function ddwcac_get_capability() {
		/**
		 * Filter the capability needed to manage Advanced CAPTCHA.
		 *
		 * @since 1.0.0
		 *
		 * @param string $capability Capability.
		 */
		return apply_filters( 'ddwcac_modify_admin_menu_capability', class_exists( 'WooCommerce' ) ? 'manage_woocommerce' : 'manage_options' );
	}
}

if ( ! function_exists( 'ddwcac_get_pro_image' ) ) {
	/**
	 * Screenshot shown behind an upgrade card, or '' when it is not shipped (the card then renders on its own).
	 *
	 * @param string $name Image name in assets/images/pro, without extension.
	 * @return string
	 */
	function ddwcac_get_pro_image( $name ) {
		$file = 'assets/images/pro/' . sanitize_file_name( $name ) . '.webp';

		return file_exists( DDWCAC_PLUGIN_FILE . $file ) ? DDWCAC_PLUGIN_URL . $file : '';
	}
}

if ( ! function_exists( 'ddwcac_get_dashboard_url' ) ) {
	/**
	 * Plugin dashboard URL.
	 *
	 * @param array $args Query args.
	 * @return string
	 */
	function ddwcac_get_dashboard_url( $args = [] ) {
		return add_query_arg( array_merge( [ 'page' => DDWCAC_DASHBOARD_PAGE ], $args ), admin_url( 'admin.php' ) );
	}
}

if ( ! function_exists( 'ddwcac_get_brand_mark' ) ) {
	/**
	 * Plugin mark: a shield with a check, in the same circle style as the other DevDiggers plugins.
	 *
	 * @param int $size Pixel size.
	 * @return string
	 */
	function ddwcac_get_brand_mark( $size = 30 ) {
		$size = absint( $size );

		return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 30 30" fill="none" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">'
			. '<circle cx="15" cy="15" r="14" fill="var(--ddfw-tab-background-color)"/>'
			. '<path d="M15 6.5 22 9.2v5.1c0 4.4-3 7.6-7 8.7-4-1.1-7-4.3-7-8.7V9.2l7-2.7Z" fill="var(--ddfw-tab-background-color)" stroke="var(--ddfw-primary-color)" stroke-width="1.6" stroke-linejoin="round"/>'
			. '<path d="m12 14.8 2.1 2.1 4-4.2" stroke="var(--ddfw-primary-color)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>'
			. '</svg>';
	}
}

if ( ! function_exists( 'ddwcac_admin_stat' ) ) {
	/**
	 * Stat card in the framework dashboard style: tinted icon, label, value.
	 *
	 * @param string     $label Label.
	 * @param int|string $value Value.
	 * @param string     $paths SVG inner markup, drawn with a 1.75 stroke.
	 * @param string     $count Key the Integrations script updates live, if any.
	 * @return void
	 */
	function ddwcac_admin_stat( $label, $value, $paths, $count = '' ) {
		?>
		<div class="ddwcac-stat">
			<span class="ddwcac-stat-icon"><?php echo wp_kses( '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths . '</svg>', ddfw_kses_allowed_svg_tags() ); ?></span>
			<div>
				<p><?php echo esc_html( $label ); ?></p>
				<h3 <?php echo $count ? 'data-count="' . esc_attr( $count ) . '"' : ''; ?>><?php echo esc_html( is_numeric( $value ) ? number_format_i18n( $value ) : $value ); ?></h3>
			</div>
		</div>
		<?php
	}
}
