<?php
/**
 * File handler
 *
 * @package Chatgpt bot for WooCommerce
 * @version 1.0.0
 */

namespace WCAICHATBOT\Includes;

use WCAICHATBOT\Includes\Admin;
use WCAICHATBOT\Includes\Front;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'Wc_Cptbot_File_Handler' ) ) {

	/**
	 * File handler class
	 */
	class Wc_Cptbot_File_Handler {

		/**
		 * Constructor method.
		 *
		 * Initializes the file handler and sets up the necessary hooks.
		 */
		public function __construct() {
			if ( is_admin() ) {
				new Admin\Wc_Cptbot_Admin_Hooks();
			}
			new Front\Wc_Cptbot_Front_Hooks();
		}

	}

}
