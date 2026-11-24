<?php
/**
 * File handler
 *
 * @package Chatgpt bot for WooCommerce
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (!class_exists('Wc_Cptbot_Table_Install')) :

    /**
     * ChatGpt Bot Install Tables.
     */
    class Wc_Cptbot_Table_Install
    {

        /**
         * Install Tables.
         *
         * Creates and updates the necessary database tables for ChatGpt Bot.
         *
         * @return void
         */
        public static function wc_cptbot_install_tables()
        {
            global $wpdb;

            include_once ABSPATH . 'wp-admin/includes/upgrade.php';

            $charset_collate = $wpdb->get_charset_collate();

            $chat_table_name = $wpdb->prefix . 'cptbot_chat_history';

            // Create or update the chat history table.
            $chat_table = "
				CREATE TABLE IF NOT EXISTS $chat_table_name (
					id bigint NOT NULL auto_increment,
					user_id int(11) NOT NULL,
					message_type varchar(51) NOT NULL,
					message longtext NULL,
					chat_status int(2) DEFAULT 1,
					message_status int(2) DEFAULT 1,
					created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
					PRIMARY KEY (id)
				) $charset_collate;
			";

            dbDelta($chat_table);

            $schedule_action_table = $wpdb->prefix . 'cptbot_scheduled_actions';

            // Create or update the scheduled actions table.
            $schedule_table = "
				CREATE TABLE IF NOT EXISTS $schedule_action_table (
					id bigint NOT NULL auto_increment,
					schedule_products_id longtext NULL,
					action_type varchar(51) NOT NULL,
					action_status int(2) DEFAULT 0,
					created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
					PRIMARY KEY (id)
				) $charset_collate;
			";
            dbDelta($schedule_table);
        }
    }

endif;

return new Wc_Cptbot_Table_Install();

