<?php
/**
 * File handler
 *
 * @package Chatgpt bot for WooCommerce
 * @version 1.0.0
 */

namespace WCAICHATBOT\Templates\Admin;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'Wc_Cptbot_Template' ) ) {

	/**
	 * Wc_Cptbot_Template class
	 */
	class Wc_Cptbot_Template {

		/**
		 * Constructor
		 *
		 * Initializes the class and calls the wc_cptbot_template method.
		 */
		public function __construct() {
			$this->wc_cptbot_template();
		}

		/**
		 * Output the dashboard template
		 *
		 * Outputs the HTML template for the dashboard.
		 */
		public function wc_cptbot_template() {
			?>
				<div id="wc_cptbot_dashboard"></div>
			<?php
		}
	}

}
