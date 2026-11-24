<?php
/**
 * Data Training router class file.
 *
 * @package Autonomous AI Chatbot
 * @version 1.0.0
 */

namespace WCAICHATBOT\Templates\Admin\Training;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'Wc_Cptbot_Training' ) ) {
	/**
	 * Data Training class.
	 */
	class Wc_Cptbot_Training {
		/**
		 * Constructor for the Wc_Cptbot_Training class.
		 */
		public function __construct() {
			$this->wc_cptbot_datatraining_container();
		}

		/**
		 * Render the data training container.
		 */
		public function wc_cptbot_datatraining_container() {
			$submenu_page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$tab          = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$section      = filter_input( INPUT_GET, 'section', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

			if ( empty( $section ) ) {
				$section = 'posts_pages';
			}

			if ( 'chatgpt-bot' === $submenu_page && 'training' === $tab ) {
				$sections = array(
					'posts_pages' => esc_html__( 'WordPress Posts & Pages', 'autonomous-ai-chatbot' ),
				);

				// Only show WooCommerce product tab if WooCommerce is active or if PRO can train them
				if ( class_exists( 'WooCommerce' ) ) {
					$crown_html = ( ! function_exists( 'wcaichatbot_is_pro_active' ) || ! wcaichatbot_is_pro_active() ) && function_exists( 'wcaichatbot_get_crown_icon_html' )
						? ' ' . wcaichatbot_get_crown_icon_html()
						: '';
					$sections['products'] = esc_html__( 'WooCommerce Products', 'autonomous-ai-chatbot' ) . $crown_html;
				}

				echo '<div class="wrap cptbot-training-main-wrap" style="padding-top: 10px; margin: 0; width: 100%; max-width: 100%; box-sizing: border-box;">';

				// Render secondary sub-tab navigation if multiple sections exist
				if ( count( $sections ) > 1 ) {
					echo '<ul class="subsubsub cptbot-training-subtabs" style="margin-bottom: 16px; float: none; display: flex; flex-wrap: wrap; gap: 12px; border-bottom: 1px solid #ccd0d4; padding-bottom: 10px;">';
					$total_secs = count( $sections );
					$sec_idx    = 0;

					foreach ( $sections as $sec_key => $sec_label ) {
						$sec_idx++;
						$is_active = ( $section === $sec_key );
						$sec_url   = add_query_arg(
							array(
								'page'    => 'chatgpt-bot',
								'tab'     => 'training',
								'section' => $sec_key,
							),
							admin_url( 'admin.php' )
						);

						$active_style = $is_active
							? 'font-weight: 700; color: #1d2327; border-bottom: 2px solid #2271b1; padding-bottom: 10px;'
							: 'color: #50575e; text-decoration: none; padding-bottom: 10px;';

						echo '<li>';
						echo '<a href="' . esc_url( $sec_url ) . '" style="' . esc_attr( $active_style ) . ' font-size: 14px;">';
						echo wp_kses_post( $sec_label );
						echo '</a>';
						if ( $sec_idx < $total_secs ) {
							echo ' <span style="color:#dcdcde; margin-left:12px;">|</span>';
						}
						echo '</li>';
					}
					echo '</ul>';
					echo '<div class="clear"></div>';
				}

				// Render selected section content
				if ( 'products' === $section ) {
					do_action( 'wcaichatbot_product_training', $this );
				} else {
					$posts_pages_template = WC_CPTBOT_PLUGIN_FILE . 'templates/admin/training/class-wc-cptbot-posts-pages-training.php';
					if ( file_exists( $posts_pages_template ) ) {
						require_once $posts_pages_template;
					}
				}

				echo '</div>';
			}
		}
	}
}
