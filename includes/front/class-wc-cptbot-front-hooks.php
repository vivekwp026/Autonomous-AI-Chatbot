<?php

/**
 * Front hooks
 *
 * @package Chatgpt bot for WooCommerce
 * @version 1.0.0
 */

namespace WCAICHATBOT\Includes\Front;

defined('ABSPATH') || exit();

if (! class_exists('Wc_Cptbot_Front_Hooks')) {
	/**
	 * Front hooks class
	 */
	class Wc_Cptbot_Front_Hooks
	{
		/**
		 * Constructor method.
		 *
		 * Initializes the front hooks and sets up the necessary actions and filters.
		 */
		public function __construct()
		{

			$function_handler = new Wc_Cptbot_Front_Functions();
			add_action('wp_enqueue_scripts', array($function_handler, 'wc_cptbot_enqueue_pos_scripts'));
			add_filter('wkwcpos_add_custom_css', array($function_handler, 'wc_cptbot_add_custom_handle'));
			add_action('wp_footer', array($function_handler, 'wc_cptbot_chatbox_template'));
			add_action('wp_ajax_wc_cptbot_client_query', array($function_handler, 'wc_cptbot_client_query'));
			add_action('wp_ajax_nopriv_wc_cptbot_client_query', array($function_handler, 'wc_cptbot_client_query'));

			add_action('wp_ajax_wc_cptbot_get_client_query_prompt', array($function_handler, 'wc_cptbot_get_client_query_prompt'));
			add_action('wp_ajax_nopriv_wc_cptbot_get_client_query_prompt', array($function_handler, 'wc_cptbot_get_client_query_prompt'));

			add_action('wp_ajax_wc_cptbot_client_query_for_onnx', array($function_handler, 'wc_cptbot_client_query_for_onnx'));
			add_action('wp_ajax_nopriv_wc_cptbot_client_query_for_onnx', array($function_handler, 'wc_cptbot_client_query_for_onnx'));

			add_action('wp_ajax_wc_cptbot_get_product_details', array($function_handler, 'wc_cptbot_get_product_details'));
			add_action('wp_ajax_nopriv_wc_cptbot_get_product_details', array($function_handler, 'wc_cptbot_get_product_details'));

			add_action('wp_ajax_wc_cptbot_get_server_side_embedding', array($function_handler, 'wc_cptbot_get_server_side_embedding'));
			add_action('wp_ajax_nopriv_wc_cptbot_get_server_side_embedding', array($function_handler, 'wc_cptbot_get_server_side_embedding'));

			add_filter('wp_plugin_check_ignore_directories', array( $function_handler, 'wc_cptbot_wp_plugin_check_ignore_directories' ) );

			add_filter('wp_plugin_check_ignore_files', array( $function_handler, 'wc_cptbot_wp_plugin_check_ignore_files' ) );
		}
	}
}
