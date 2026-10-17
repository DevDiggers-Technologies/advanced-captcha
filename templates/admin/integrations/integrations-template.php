<?php
/**
 * Integrations screen: every supported plugin as a card with its own switch.
 *
 * @package Advanced CAPTCHA
 * @version 1.0.0
 */

namespace DDWCAdvancedCAPTCHA\Templates\Admin\Integrations;

use DDWCAdvancedCAPTCHA\Helper\Integrations\DDWCAC_Integrations_Helper;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCAC_Integrations_Template' ) ) {
	/**
	 * Integrations template class.
	 */
	class DDWCAC_Integrations_Template {
		/**
		 * Construct.
		 */
		public function __construct() {
			$helper  = new DDWCAC_Integrations_Helper();
			$catalog = $helper->ddwcac_get_catalog();
			$counts  = $helper->ddwcac_get_counts( $catalog );
			$filters = [
				'all'       => __( 'All', 'advanced-captcha' ),
				'protected' => __( 'Protected', 'advanced-captcha' ),
				'waiting'   => __( 'Needs Attention', 'advanced-captcha' ),
				'missing'   => __( 'Not Installed', 'advanced-captcha' ),
			];
			?>
			<div class="ddwcac-page ddwcac-int">
				<div class="ddwcac-page-header">
					<div>
						<h1><?php esc_html_e( 'Integrations', 'advanced-captcha' ); ?></h1>
						<p><?php esc_html_e( 'Switch a plugin on and the CAPTCHA appears on its forms automatically, no shortcodes needed. Changes save instantly.', 'advanced-captcha' ); ?></p>
					</div>
				</div>

				<div class="ddwcac-stats">
					<?php
					ddwcac_admin_stat( __( 'Plugins Protected', 'advanced-captcha' ), $counts['protected'], '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>', 'protected' );
					ddwcac_admin_stat( __( 'Forms Covered', 'advanced-captcha' ), $counts['forms'], '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/>', 'forms' );
					ddwcac_admin_stat( __( 'Needs Attention', 'advanced-captcha' ), $counts['waiting'], '<path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/>', 'waiting' );
					ddwcac_admin_stat( __( 'Supported Plugins', 'advanced-captcha' ), $counts['total'], '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>' );
					?>
				</div>

				<div class="ddwcac-toolbar">
					<ul class="subsubsub">
						<?php foreach ( $filters as $key => $label ) : ?>
							<li><a href="#" class="ddwcac-int-filter<?php echo 'all' === $key ? ' current' : ''; ?>" data-filter="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></a></li>
						<?php endforeach; ?>
					</ul>
					<label class="screen-reader-text" for="ddwcac-int-search"><?php esc_html_e( 'Search integrations', 'advanced-captcha' ); ?></label>
					<input type="search" id="ddwcac-int-search" class="ddwcac-int-search" placeholder="<?php esc_attr_e( 'Search plugins', 'advanced-captcha' ); ?>" />
				</div>

				<?php $all = $catalog + $helper->ddwcac_get_pro_catalog(); ?>
				<?php foreach ( $helper->ddwcac_get_groups() as $group => $title ) : ?>
					<?php $cards = array_filter( $all, fn( $card ) => $group === $card['group'] ); ?>
					<section class="ddwcac-section ddwcac-int-group" data-group="<?php echo esc_attr( $group ); ?>">
						<div class="ddwcac-section-header">
							<h2><?php echo esc_html( $title ); ?></h2>
						</div>
						<div class="ddwcac-int-list">
							<?php
							foreach ( $cards as $key => $card ) {
								$this->ddwcac_render_card( $key, $card );
							}
							?>
						</div>
					</section>
				<?php endforeach; ?>

				<p class="ddwcac-int-empty ddwcac-empty-text" hidden><?php esc_html_e( 'No plugins match your search.', 'advanced-captcha' ); ?></p>

				<div class="ddwcac-note">
					<strong><?php esc_html_e( 'Using a custom form?', 'advanced-captcha' ); ?></strong>
					<?php
					printf(
						/* translators: 1: shortcode, 2: PHP function. */
						esc_html__( 'Place %1$s inside it and verify the submission with %2$s.', 'advanced-captcha' ),
						'<code>[ddwcac_captcha]</code>',
						'<code>ddwcac_check_captcha_response()</code>'
					);
					?>
				</div>
			</div>
			<?php
		}

		/**
		 * One integration card.
		 *
		 * @param string $key  Card key.
		 * @param array  $card Card with state.
		 * @return void
		 */
		protected function ddwcac_render_card( $key, $card ) {
			$is_pro  = 'pro' === $card['type'];
			$filter  = $card['detected'] ? ( ! $is_pro && $card['enabled'] ? 'protected' : 'waiting' ) : 'missing';
			$id      = 'ddwcac-int-' . $key;
			$choices = 'choices' === $card['type'] && $card['detected'] && count( $card['choices'] ) > 1;
			?>
			<div class="ddwcac-int-card is-<?php echo esc_attr( $filter ); ?>" data-key="<?php echo esc_attr( $key ); ?>" data-filter="<?php echo esc_attr( $filter ); ?>" data-name="<?php echo esc_attr( strtolower( $card['name'] ) ); ?>">
				<div class="ddwcac-int-row">
					<span class="ddwcac-int-logo"><img src="<?php echo esc_url( $card['logo'] ); ?>" alt="" width="28" height="28" loading="lazy" /></span>
					<div class="ddwcac-int-body">
						<h3><?php echo esc_html( $card['name'] ); ?></h3>
						<p><?php echo esc_html( $card['description'] ); ?></p>
					</div>
					<span class="ddwcac-int-state"><mark class="ddwcac-status ddwcac-status-<?php echo esc_attr( $card['status'][0] ); ?>"><?php echo esc_html( $card['status'][1] ); ?></mark></span>
					<span class="ddwcac-int-switch">
						<?php if ( $is_pro ) : ?>
							<a class="ddwcac-inline-pro" href="https://devdiggers.com/product/woocommerce-advanced-captcha/" target="_blank" rel="noopener noreferrer">
								<?php
								/* translators: %s: plugin name. */
								echo '<span class="screen-reader-text">' . esc_html( sprintf( __( 'Protect %s with Advanced CAPTCHA Pro', 'advanced-captcha' ), $card['name'] ) ) . '</span>';
								echo wp_kses_post( ddfw_get_pro_tag() );
								?>
							</a>
						<?php elseif ( $card['detected'] ) : ?>
							<label class="screen-reader-text" for="<?php echo esc_attr( $id ); ?>">
								<?php
								/* translators: %s: plugin name. */
								echo esc_html( sprintf( __( 'Protect %s', 'advanced-captcha' ), $card['name'] ) );
								?>
							</label>
							<input type="checkbox" id="<?php echo esc_attr( $id ); ?>" class="ddwcac-int-toggle" <?php checked( $card['enabled'] ); ?> />
						<?php endif; ?>
					</span>
				</div>
				<?php if ( $choices ) : ?>
					<div class="ddwcac-int-forms">
						<?php foreach ( $card['choices'] as $value => $label ) : ?>
							<label class="ddwcac-chip">
								<input type="checkbox" class="ddwcac-int-form" value="<?php echo esc_attr( $value ); ?>" <?php checked( in_array( $value, $card['selected'], true ) ); ?> />
								<span><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg><?php echo esc_html( $label ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
			<?php
		}
	}
}
