<?php
/**
 * PRO Banner, Upgrade Helpers, and Core Fallbacks.
 *
 * @package AI Chatbot
 */

defined( 'ABSPATH' ) || exit();

defined( 'WCAICHATBOT_PRO_UPGRADE_URL' ) || define( 'WCAICHATBOT_PRO_UPGRADE_URL', 'https://store.webkul.com/woocommerce-plugins/WooCommerce.html?p=1' );

if ( ! function_exists( 'wcaichatbot_get_pro_upgrade_url' ) ) {
	/**
	 * Retrieves the PRO Upgrade link.
	 *
	 * @return string The upgrade URL.
	 */
	function wcaichatbot_get_pro_upgrade_url() {
		return apply_filters( 'wcaichatbot_pro_upgrade_url', WCAICHATBOT_PRO_UPGRADE_URL );
	}
}

if ( ! function_exists( 'wcaichatbot_is_pro_active' ) ) {
	/**
	 * Checks whether the WooCommerce PRO Addon is active.
	 *
	 * @return bool True if active, false otherwise.
	 */
	function wcaichatbot_is_pro_active() {
		return (bool) apply_filters( 'wcaichatbot_is_pro_active', false );
	}
}

if ( ! function_exists( 'wcaichatbot_get_pro_addon_file' ) ) {
	/**
	 * Locates the standalone PRO addon main file in WordPress plugins directory if installed.
	 *
	 * @return string|false Absolute file path if installed in wp-content/plugins, false otherwise.
	 */
	function wcaichatbot_get_pro_addon_file() {
		// 1. Check standard plugin directory.
		if ( defined( 'WP_PLUGIN_DIR' ) ) {
			$standard_autonomous = WP_PLUGIN_DIR . '/autonomous-ai-chatbot-pro/autonomous-ai-chatbot-pro.php';
			if ( file_exists( $standard_autonomous ) ) {
				return $standard_autonomous;
			}
			$standard_legacy = WP_PLUGIN_DIR . '/ai-chatbot-for-woocommerce-pro/autonomous-ai-chatbot-pro.php';
			if ( file_exists( $standard_legacy ) ) {
				return $standard_legacy;
			}
			$standard_old = WP_PLUGIN_DIR . '/ai-chatbot-for-woocommerce-pro/ai-chatbot-for-woocommerce-pro.php';
			if ( file_exists( $standard_old ) ) {
				return $standard_old;
			}
		}

		// 2. Also check via get_plugins() in case the addon directory was customized, but strictly ignore internal files inside the base plugin.
		if ( defined( 'ABSPATH' ) && defined( 'WP_PLUGIN_DIR' ) ) {
			if ( ! function_exists( 'get_plugins' ) && file_exists( ABSPATH . 'wp-admin/includes/plugin.php' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			if ( function_exists( 'get_plugins' ) ) {
				$all_plugins = get_plugins();
				foreach ( $all_plugins as $plugin_file => $plugin_data ) {
					// Strictly ignore any files located inside the base plugin directory.
					if ( defined( 'WC_CPTBOT_PLUGIN_BASENAME' ) && 0 === strpos( $plugin_file, dirname( WC_CPTBOT_PLUGIN_BASENAME ) . '/' ) ) {
						continue;
					}
					if ( isset( $plugin_data['TextDomain'] ) && in_array( $plugin_data['TextDomain'], array( 'autonomous-ai-chatbot-pro', 'ai-chatbot-for-woocommerce-pro' ), true ) ) {
						return WP_PLUGIN_DIR . '/' . $plugin_file;
					}
				}
			}
		}

		return false;
	}
}

if ( ! function_exists( 'wcaichatbot_get_pro_addon_basename' ) ) {
	/**
	 * Retrieves the relative plugin basename for the PRO addon.
	 *
	 * @return string Plugin basename.
	 */
	function wcaichatbot_get_pro_addon_basename() {
		$file = wcaichatbot_get_pro_addon_file();
		if ( $file ) {
			return plugin_basename( $file );
		}
		return 'autonomous-ai-chatbot-pro/autonomous-ai-chatbot-pro.php';
	}
}

if ( ! function_exists( 'wcaichatbot_is_pro_installed' ) ) {
	/**
	 * Checks whether the PRO addon is installed on this WordPress installation.
	 *
	 * @return bool True if installed, false otherwise.
	 */
	function wcaichatbot_is_pro_installed() {
		return false !== wcaichatbot_get_pro_addon_file();
	}
}

if ( ! function_exists( 'wcaichatbot_is_pro_installed_inactive' ) ) {
	/**
	 * Checks whether the PRO addon is installed in wp-content/plugins but currently inactive.
	 *
	 * @return bool True if installed and inactive, false otherwise.
	 */
	function wcaichatbot_is_pro_installed_inactive() {
		if ( function_exists( 'wcaichatbot_is_pro_active' ) && wcaichatbot_is_pro_active() ) {
			return false;
		}

		$pro_file = function_exists( 'wcaichatbot_get_pro_addon_file' ) ? wcaichatbot_get_pro_addon_file() : false;
		if ( ! $pro_file || ! file_exists( $pro_file ) ) {
			return false;
		}

		if ( ! function_exists( 'is_plugin_active' ) && file_exists( ABSPATH . 'wp-admin/includes/plugin.php' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( function_exists( 'is_plugin_active' ) ) {
			$basename = plugin_basename( $pro_file );
			return ! is_plugin_active( $basename );
		}

		return true;
	}
}

if ( ! function_exists( 'wcaichatbot_pro_activate_url' ) ) {
	/**
	 * One-click "Activate Pro" URL, or '' when Pro is not installed / already active / user cannot activate.
	 *
	 * @return string
	 */
	function wcaichatbot_pro_activate_url() {
		if ( ! current_user_can( 'activate_plugins' ) || ! wcaichatbot_is_pro_installed_inactive() ) {
			return '';
		}

		return add_query_arg(
			array(
				'action'   => 'wcaichatbot_activate_pro',
				'_wpnonce' => wp_create_nonce( 'wcaichatbot_activate_pro' ),
			),
			admin_url( 'admin-post.php' )
		);
	}
}

if ( ! function_exists( 'wcaichatbot_is_woocommerce_installed' ) ) {
	/**
	 * Checks whether WooCommerce is installed on this site.
	 *
	 * @return bool True if WooCommerce exists, false otherwise.
	 */
	function wcaichatbot_is_woocommerce_installed() {
		if ( class_exists( 'WooCommerce' ) ) {
			return true;
		}
		if ( defined( 'WP_PLUGIN_DIR' ) ) {
			return file_exists( WP_PLUGIN_DIR . '/woocommerce/woocommerce.php' );
		}
		return false;
	}
}

if ( ! function_exists( 'wcaichatbot_get_pro_activation_url' ) ) {
	/**
	 * Generates a secure 1-click activation URL for the PRO addon.
	 *
	 * @return string Activation URL with nonce.
	 */
	function wcaichatbot_get_pro_activation_url() {
		return add_query_arg(
			array(
				'action'   => 'wcaichatbot_activate_pro',
				'_wpnonce' => wp_create_nonce( 'wcaichatbot_activate_pro' ),
			),
			admin_url( 'admin-post.php' )
		);
	}
}

if ( ! function_exists( 'wcaichatbot_handle_activate_pro' ) ) {
	/**
	 * Handles 1-click activation of the PRO addon.
	 */
	function wcaichatbot_handle_activate_pro() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_die( esc_html__( 'You do not have permission to activate plugins.', 'autonomous-ai-chatbot' ) );
		}

		// Safeguard against HTML entity encoded query parameters (amp;_wpnonce).
		if ( empty( $_REQUEST['_wpnonce'] ) && ! empty( $_REQUEST['amp;_wpnonce'] ) ) {
			$_REQUEST['_wpnonce'] = sanitize_text_field( wp_unslash( $_REQUEST['amp;_wpnonce'] ) );
		}
		if ( empty( $_GET['_wpnonce'] ) && ! empty( $_GET['amp;_wpnonce'] ) ) {
			$_GET['_wpnonce'] = sanitize_text_field( wp_unslash( $_GET['amp;_wpnonce'] ) );
		}

		check_admin_referer( 'wcaichatbot_activate_pro' );

		if ( ! function_exists( 'activate_plugin' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// If WooCommerce is installed but inactive, activate it as well to satisfy PRO dependency.
		if ( ! class_exists( 'WooCommerce' ) && function_exists( 'wcaichatbot_is_woocommerce_installed' ) && wcaichatbot_is_woocommerce_installed() ) {
			activate_plugin( 'woocommerce/woocommerce.php' );
		}

		$pro_basename = wcaichatbot_get_pro_addon_basename();
		activate_plugin( $pro_basename );

		$redirect_url = wp_get_referer();
		if ( ! $redirect_url ) {
			$redirect_url = admin_url( 'admin.php?page=chatgpt-bot' );
		}

		wp_safe_redirect( $redirect_url );
		exit;
	}
	add_action( 'admin_post_wcaichatbot_activate_pro', 'wcaichatbot_handle_activate_pro' );
}

if ( ! function_exists( 'wcaichatbot_get_pro_banner_html' ) ) {
	/**
	 * Generates the HTML for the Upgrade / Activate PRO banner card.
	 *
	 * @param string      $title       Banner title.
	 * @param string      $description Banner description.
	 * @param string      $cta_text    Call-to-action button text.
	 * @param string|null $cta_url     Optional custom URL.
	 * @return string HTML markup of the banner.
	 */
	function wcaichatbot_get_pro_banner_html( $title, $description, $cta_text = '', $cta_url = null ) {
		$is_installed = wcaichatbot_is_pro_installed();
		$can_activate = current_user_can( 'activate_plugins' );

		if ( null === $cta_url ) {
			if ( $is_installed && $can_activate ) {
				$cta_url = wcaichatbot_get_pro_activation_url();
			} else {
				$cta_url = wcaichatbot_get_pro_upgrade_url();
			}
		}

		if ( empty( $cta_text ) ) {
			if ( $is_installed && $can_activate ) {
				$cta_text = esc_html__( 'Activate PRO Plugin', 'autonomous-ai-chatbot' );
			} else {
				$cta_text = esc_html__( 'Upgrade to PRO', 'autonomous-ai-chatbot' );
			}
		}

		if ( $is_installed && $can_activate ) {
			$description = str_ireplace(
				array( 'Upgrade to the PRO addon to', 'Upgrade to the PRO plugin to', 'Upgrade to PRO to' ),
				esc_html__( 'Activate the PRO plugin to', 'autonomous-ai-chatbot' ),
				$description
			);
		}

		ob_start();
		?>
		<div class="wcaichatbot-pro-banner-card">
			<span class="wcaichatbot-pro-badge">
				<span class="dashicons dashicons-star-filled" style="font-size: 13px; width: 13px; height: 13px; line-height: 13px;"></span>
				<?php esc_html_e( 'PRO Feature', 'autonomous-ai-chatbot' ); ?>
			</span>
			<h3><?php echo esc_html( $title ); ?></h3>
			<p><?php echo esc_html( $description ); ?></p>
			<div>
				<a href="<?php echo esc_url( $cta_url ); ?>" class="wcaichatbot-pro-btn">
					<?php echo esc_html( $cta_text ); ?> &rarr;
				</a>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}

if ( ! function_exists( 'wcaichatbot_get_pro_lock_html' ) ) {
	/**
	 * Generates HTML for a locked feature with a dimmed preview reflection and floating lock card.
	 *
	 * @param string      $title        Feature title.
	 * @param string      $description  Feature description.
	 * @param string      $preview_html Optional HTML markup to be rendered as the dimmed background reflection.
	 * @param string      $cta_text     CTA button text.
	 * @param string|null $cta_url      Target URL.
	 * @param bool        $compact      Whether to render in compact mode.
	 * @param bool        $full_height  Whether to extend full height with bottom fade (e.g. for full-page tabs).
	 * @return string HTML markup.
	 */
	function wcaichatbot_get_pro_lock_html( $title, $description, $preview_html = '', $cta_text = '', $cta_url = null, $compact = false, $full_height = false ) {
		$is_installed = wcaichatbot_is_pro_installed();
		$can_activate = current_user_can( 'activate_plugins' );

		if ( null === $cta_url ) {
			if ( $is_installed && $can_activate ) {
				$cta_url = wcaichatbot_get_pro_activation_url();
			} else {
				$cta_url = wcaichatbot_get_pro_upgrade_url();
			}
		}

		if ( empty( $cta_text ) ) {
			if ( $is_installed && $can_activate ) {
				$cta_text = esc_html__( 'Activate Pro', 'autonomous-ai-chatbot' );
			} else {
				$cta_text = esc_html__( 'Upgrade to PRO', 'autonomous-ai-chatbot' );
			}
		}

		$has_preview = ! empty( trim( (string) $preview_html ) );
		$classes     = array( 'wcaichatbot-pro-lock' );
		if ( $compact ) {
			$classes[] = 'is-compact';
		}
		if ( $full_height ) {
			$classes[] = 'is-full-height';
		}
		if ( ! $has_preview ) {
			$classes[] = 'no-preview';
		}

		ob_start();
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
			<?php if ( $has_preview ) : ?>
				<div class="wcaichatbot-pro-lock__preview" aria-hidden="true" inert="">
					<?php echo $preview_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php endif; ?>
			<div class="wcaichatbot-pro-lock__overlay">
				<div class="wcaichatbot-pro-lock__card">
					<span class="wcaichatbot-pro-lock__icon" aria-hidden="true">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none">
							<rect x="4" y="10" width="16" height="11" rx="2.5" stroke="currentColor" stroke-width="1.8"/>
							<path d="M8 10V7a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="1.8"/>
						</svg>
					</span>
					<div>
						<strong class="wcaichatbot-pro-lock__title">
							<?php echo esc_html( $title ); ?>
							<span class="wcaichatbot-pro-badge-pill"><?php esc_html_e( 'PRO', 'autonomous-ai-chatbot' ); ?></span>
						</strong>
						<p class="wcaichatbot-pro-lock__desc"><?php echo esc_html( $description ); ?></p>
					</div>
					<a href="<?php echo esc_url( $cta_url ); ?>" class="wcaichatbot-pro-lock__btn" <?php echo ! $is_installed ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
						<?php echo esc_html( $cta_text ); ?>
					</a>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}

if ( ! function_exists( 'wcaichatbot_get_crown_icon_html' ) ) {
	/**
	 * Generates HTML for the PRO golden crown icon.
	 *
	 * @return string HTML markup of the crown icon, or empty string if PRO is active.
	 */
	function wcaichatbot_get_crown_icon_html() {
		if ( function_exists( 'wcaichatbot_is_pro_active' ) && wcaichatbot_is_pro_active() ) {
			return '';
		}

		$icon_url = defined( 'WC_CPTBOT_PLUGIN_URL' ) ? WC_CPTBOT_PLUGIN_URL . 'assets/images/crown.svg' : '';
		return '<img src="' . esc_url( $icon_url ) . '" class="wcaichatbot-crown-icon" alt="' . esc_attr__( 'PRO Feature', 'autonomous-ai-chatbot' ) . '" title="' . esc_attr__( 'PRO Feature', 'autonomous-ai-chatbot' ) . '" width="16" height="13" />';
	}
}

if ( ! function_exists( 'wcaichatbot_get_edition_badge_html' ) ) {
	/**
	 * Generates HTML for the edition badge (Lite or Pro).
	 *
	 * @return string HTML markup of the edition badge.
	 */
	function wcaichatbot_get_edition_badge_html() {
		if ( function_exists( 'wcaichatbot_is_pro_active' ) && wcaichatbot_is_pro_active() ) {
			return '<span class="wcaichatbot-edition-badge wcaichatbot-badge-pro">' . esc_html__( 'Pro', 'autonomous-ai-chatbot' ) . '</span>';
		}
		return '<span class="wcaichatbot-edition-badge wcaichatbot-badge-lite">' . esc_html__( 'Lite', 'autonomous-ai-chatbot' ) . '</span>';
	}
}

// -------------------------------------------------------------------------
// Safe Namespaced Wrappers for WooCommerce functions (avoid global collision)
// -------------------------------------------------------------------------

if ( ! function_exists( 'wcaichatbot_clean' ) ) {
	/**
	 * Safe replacement for wc_clean without colliding with WooCommerce core.
	 *
	 * @param mixed $var Data to sanitize.
	 * @return mixed Sanitized data.
	 */
	function wcaichatbot_clean( $var ) {
		if ( function_exists( 'wc_clean' ) ) {
			return wc_clean( $var );
		}
		if ( is_array( $var ) ) {
			return array_map( 'wcaichatbot_clean', $var );
		}
		return is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
	}
}

if ( ! function_exists( 'wcaichatbot_attribute_label' ) ) {
	/**
	 * Safe replacement for wc_attribute_label without colliding with WooCommerce core.
	 *
	 * @param string $name Attribute name.
	 * @param mixed  $product Product object.
	 * @return string The attribute label.
	 */
	function wcaichatbot_attribute_label( $name, $product = '' ) {
		if ( function_exists( 'wc_attribute_label' ) ) {
			return wc_attribute_label( $name, $product );
		}
		return $name;
	}
}

if ( ! function_exists( 'wcaichatbot_get_currency_symbol' ) ) {
	/**
	 * Safe replacement for get_woocommerce_currency_symbol without colliding with WooCommerce core.
	 *
	 * @param string $currency Currency code.
	 * @return string Currency symbol.
	 */
	function wcaichatbot_get_currency_symbol( $currency = '' ) {
		if ( function_exists( 'get_woocommerce_currency_symbol' ) ) {
			return html_entity_decode( get_woocommerce_currency_symbol( $currency ), ENT_QUOTES, 'UTF-8' );
		}
		return '$';
	}
}

if ( ! function_exists( 'wcaichatbot_clear_dashboard_kpi_transients' ) ) {
	/**
	 * Clears dashboard KPI transients when plugins change state (activation/deactivation).
	 */
	function wcaichatbot_clear_dashboard_kpi_transients() {
		delete_transient( 'wcaichatbot_kpis_v4_7_pro' );
		delete_transient( 'wcaichatbot_kpis_v4_7_lite' );
		delete_transient( 'wcaichatbot_kpis_v4_30_pro' );
		delete_transient( 'wcaichatbot_kpis_v4_30_lite' );
		delete_transient( 'wcaichatbot_kpis_v3_7_pro' );
		delete_transient( 'wcaichatbot_kpis_v3_7_lite' );
		delete_transient( 'wcaichatbot_kpis_v3_30_pro' );
		delete_transient( 'wcaichatbot_kpis_v3_30_lite' );
		delete_transient( 'wcaichatbot_dashboard_kpis_v2_7' );
		delete_transient( 'wcaichatbot_dashboard_kpis_v2_30' );
		delete_transient( 'wcaichatbot_dashboard_kpis_7' );
		delete_transient( 'wcaichatbot_dashboard_kpis_30' );
	}
	add_action( 'activated_plugin', 'wcaichatbot_clear_dashboard_kpi_transients' );
	add_action( 'deactivated_plugin', 'wcaichatbot_clear_dashboard_kpi_transients' );
}
