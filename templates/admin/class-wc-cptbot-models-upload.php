<?php

/**
 * Model Lists template.
 *
 * @package Chatgpt bot for WooCommerce
 */

namespace WCAICHATBOT\Templates\Admin;

defined('ABSPATH') || exit(); // Exit if accessed directly.

if (! class_exists('Wc_Cptbot_Models_Upload')) {
	/**
	 * Slots list class.
	 */
	class Wc_Cptbot_Models_Upload
	{
		/**
		 * Constructor.
		 *
		 * @return void
		 */
		public function __construct() {
			$this->get_template();
		}

		/**
		 * Get template content.
		 *
		 * @return void
		 */
		public function get_template() {
			?>
			<div id="wc_cptbot_model_container"></div>
			<?php
		}
	}
}
