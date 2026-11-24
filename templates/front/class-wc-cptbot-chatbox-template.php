<?php
/**
 * File handler
 *
 * @package Chatgpt bot for WooCommerce
 * @version 1.0.0
 */

namespace WCAICHATBOT\Templates\Front;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'Wc_Cptbot_Chatbox_Template' ) ) {

	/**
	 * Wc_Cptbot_Chatbox_Template class
	 */
	class Wc_Cptbot_Chatbox_Template {
		/**
		 * Constructor
		 *
		 * Initializes the class and calls the wc_cptbot_template method.
		 */
		public function __construct() {
			$this->wc_cptbot_template();
		}

		/**
		 * Output the chatbox template
		 *
		 * Outputs the HTML template for the chatbox.
		 */
		public function wc_cptbot_template() {
			?>
				<div id='front_chatbox'></div>
			<?php
		}
	}
}
