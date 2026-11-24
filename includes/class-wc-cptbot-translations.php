<?php
/**
 * File handler
 *
 * @package Chatgpt bot for WooCommerce
 * @version 1.0.0
 */

namespace WCAICHATBOT\Includes;

defined('ABSPATH') || exit();

if (!class_exists('Wc_Cptbot_Translations')) {

	/**
	 * Translations class
	 */
	class Wc_Cptbot_Translations
	{

		/**
		 * Retrieves all admin translations for the custom post type (CPT) bot.
		 *
		 * @return array Array of translations for various strings.
		 */
		public function wc_cpt_get_all_admin_translations()
		{
			return array(
				'model_loading' => esc_html__('Loading ONNX Model...', 'autonomous-ai-chatbot'),
				'model_ready' => esc_html__('✅ ONNX Model Ready', 'autonomous-ai-chatbot'),
				'model_error' => esc_html__('❌ ONNX Model Error', 'autonomous-ai-chatbot'),
				'please_wait' => esc_html__('Please wait', 'autonomous-ai-chatbot'),
				'sync_error' => esc_html__('An error occurred during synchronization', 'autonomous-ai-chatbot'),
				'index_creating' => esc_html__('Index Creating', 'autonomous-ai-chatbot'),
				'index_created' => esc_html__('Index Successfully Created', 'autonomous-ai-chatbot'),
				'failed_msg' => esc_html__('Something Went Wrong', 'autonomous-ai-chatbot'),
				'recreate_msg' => esc_html__('Recreate Index', 'autonomous-ai-chatbot'),
				'pro_syncing' => esc_html__('Products Syncing With Autonomous AI Chatbot...', 'autonomous-ai-chatbot'),
				'pro_synced' => esc_html__('Product Successfully exported to Autonomous AI Chatbot', 'autonomous-ai-chatbot'),
				'ask_again' => esc_html__('Ask Again', 'autonomous-ai-chatbot'),
				'thinking' => esc_html__('Thinking', 'autonomous-ai-chatbot') . '...',
				'cpt_avtar_txt' => esc_html__('ChatBot Avatar', 'autonomous-ai-chatbot'),
				'upl_logo' => esc_html__('Upload Invoice Logo', 'autonomous-ai-chatbot'),
				'bg_img' => esc_html__('ChatBot Background Image', 'autonomous-ai-chatbot'),
				'upl_bg_img' => esc_html__('Upload Background Image', 'autonomous-ai-chatbot'),
				'ai_logo' => esc_html__('ChatBot AI Logo', 'autonomous-ai-chatbot'),
				'upl_ai_logo' => esc_html__('Upload AI Logo', 'autonomous-ai-chatbot'),
				'pr_trained' => esc_html__('Product Trained Sucessfully', 'autonomous-ai-chatbot'),
				'img_found_txt' => esc_html__('Image Format Found', 'autonomous-ai-chatbot'),
				'need' => esc_html__('Need', 'autonomous-ai-chatbot'),
				'processing' => esc_html__('Processing', 'autonomous-ai-chatbot') . '...',
				'thank_you_msg' => sprintf( /* translators: %s WooCommerce plugin links */ esc_html__('Thank You for using %1$s by %2$s', 'autonomous-ai-chatbot'), '<strong>Autonomous AI Chatbot</strong>', '<a href="' . esc_url('https://store.webkul.com/woocommerce-plugins/WordPress.html') . '" target="_blank">Webkul</a>'),
				'version' => esc_html__('Chatbot version', 'autonomous-ai-chatbot') . ' - ' . WC_CPTBOT_SCRIPT_VERSION,
				'running' => esc_html__('Running', 'autonomous-ai-chatbot') . '...',
				'pinecone_success' => esc_html__('Pinecone Index Created Successfully', 'autonomous-ai-chatbot') . '...',
				'warning' => esc_html__('Warning', 'autonomous-ai-chatbot'),
				'reset_warning' => esc_html__('Changing the Embeddings Model or Vector DB will delete all existing trained data. You will need to retrain all products, posts, and pages from scratch.', 'autonomous-ai-chatbot'),
				'yes_delete' => esc_html__('Yes, Delete & Proceed', 'autonomous-ai-chatbot'),
				'cancel' => esc_html__('Cancel', 'autonomous-ai-chatbot'),

				// History Translations.
				'select2_enter_char' => esc_html__('Please enter %qty% or more characters', 'autonomous-ai-chatbot'),
				'not_found' => esc_html__('No Matches Found', 'autonomous-ai-chatbot'),
				'xlsx_header' => array(
					esc_html__('Message From', 'autonomous-ai-chatbot'),
					esc_html__('Message', 'autonomous-ai-chatbot'),
					esc_html__('Date', 'autonomous-ai-chatbot'),
				),
				'areyousure' => esc_html__('Are You Sure You Want to', 'autonomous-ai-chatbot'),
				'this_user' => esc_html__('This User', 'autonomous-ai-chatbot'),
				'chat_history' => esc_html__('Chat History', 'autonomous-ai-chatbot'),
				'chat_filter' => esc_html__('Chat Filter', 'autonomous-ai-chatbot'),
				'username' => esc_html__('UserName', 'autonomous-ai-chatbot'),
				'from' => esc_html__('From', 'autonomous-ai-chatbot'),
				'to' => esc_html__('To', 'autonomous-ai-chatbot'),
				'search' => esc_html__('Search', 'autonomous-ai-chatbot'),
				'download' => esc_html__('Download', 'autonomous-ai-chatbot'),
				'usr_block' => esc_html__('This User is Blocked', 'autonomous-ai-chatbot'),
				'block_placeholder' => esc_html__('Block This User For Not Using ChatBot', 'autonomous-ai-chatbot'),
				'unblock_txt' => esc_html__('Unblock', 'autonomous-ai-chatbot'),
				'block_txt' => esc_html__('Block', 'autonomous-ai-chatbot'),
				'created_by' => esc_html__('Created by', 'autonomous-ai-chatbot'),
				'on_date' => esc_html__('on date', 'autonomous-ai-chatbot'),
				'false_resp' => esc_html__('Looks responce is not correct check your settings.', 'autonomous-ai-chatbot'),
				'chat_not_found' => esc_html__('No Chat found between these dates', 'autonomous-ai-chatbot'),

				// Dashboard Translations.
				'chart_count' => esc_html__('Total Chats Count', 'autonomous-ai-chatbot'),
				'dashboard' => esc_html__('Dashboard', 'autonomous-ai-chatbot'),
				'usr_traffic' => esc_html__('User Traffic over chatbot', 'autonomous-ai-chatbot'),
				'msg' => esc_html__('Messages', 'autonomous-ai-chatbot'),
				'todays_usr_chat' => esc_html__('Todays Users over Chatbot', 'autonomous-ai-chatbot'),
				'email' => esc_html__('Email', 'autonomous-ai-chatbot'),
				'msg_count' => esc_html__('Message Count', 'autonomous-ai-chatbot'),
				'chat_not_found' => esc_html__('User Chat not Found.', 'autonomous-ai-chatbot'),
				'most_ask_pro' => esc_html__('Most Asked Products', 'autonomous-ai-chatbot'),
				'total' => esc_html__('Total', 'autonomous-ai-chatbot'),

			);
		}
		/**
		 * Get all translations for admin content.
		 *
		 * This function returns an array of translated strings used in the admin content.
		 *
		 * @return array An associative array of translated strings.
		 */
		public function wc_cpt_get_all_admin_content_translations()
		{
			return array(
				'recreate_topic' => esc_html__('Recreate Topic', 'autonomous-ai-chatbot'),
				'recreate_section' => esc_html__('Recreate Sections', 'autonomous-ai-chatbot'),
				'recreate_content' => esc_html__('Recreate Content', 'autonomous-ai-chatbot'),
				'recreate_excerpt' => esc_html__('Recreate Excerpt', 'autonomous-ai-chatbot'),
				'recreate_meta' => esc_html__('Recreate Meta', 'autonomous-ai-chatbot'),
				'topic_set' => esc_html__('Topic Set Successfully', 'autonomous-ai-chatbot'),
				'section_set' => esc_html__('Section created sucessfully', 'autonomous-ai-chatbot'),
				'content_set' => esc_html__('Content created sucessfully', 'autonomous-ai-chatbot'),
				'excerpt_set' => esc_html__('Excerpt created sucessfully', 'autonomous-ai-chatbot'),
				'meta_set' => esc_html__('Meta created sucessfully', 'autonomous-ai-chatbot'),
				'fill_topic' => esc_html__('Fill the topic first', 'autonomous-ai-chatbot'),
				'select_topic' => esc_html__('Select Topic First', 'autonomous-ai-chatbot'),
				'select_topic_content' => esc_html__('Topic or Content are Empty', 'autonomous-ai-chatbot'),
				'empty_topic' => esc_html__('Topic are Empty', 'autonomous-ai-chatbot'),
				'save_topic' => esc_html__('Save Topic', 'autonomous-ai-chatbot'),
				'suggest_topic' => esc_html__('Suggest Topic', 'autonomous-ai-chatbot'),
				'suggest_used' => esc_html__('Suggestion Used Successfully', 'autonomous-ai-chatbot'),
				'openai_setting' => esc_html__('Open AI Setting saved Successfully', 'autonomous-ai-chatbot'),
				'looks_same' => esc_html__('Everything looks Same', 'autonomous-ai-chatbot'),
				'created' => esc_html__('created sucessfully', 'autonomous-ai-chatbot'),
				'exist' => esc_html__('already exist', 'autonomous-ai-chatbot'),
				'all_required_field' => esc_html__('First Create All Required Fields', 'autonomous-ai-chatbot') . ' {Sections}, {Content}, {Excerpt}, {Meta}',
				'settings_txt' => esc_html__('Settings', 'autonomous-ai-chatbot'),
				'create_txt' => esc_html__('Create', 'autonomous-ai-chatbot'),
				'save_settings_txt' => esc_html__('Save Settings', 'autonomous-ai-chatbot'),
				'gpt_suggestion' => esc_html__('Suggestions From AI', 'autonomous-ai-chatbot'),
				'use' => esc_html__('Use', 'autonomous-ai-chatbot'),
				'processing' => esc_html__('Processing', 'autonomous-ai-chatbot'),
				'content_copy' => esc_html__('Content Copied Successfully', 'autonomous-ai-chatbot'),
				'copied_text' => esc_html__('Copied', 'autonomous-ai-chatbot'),
				'listen_text' => esc_html__('Listening', 'autonomous-ai-chatbot') . '...',
				'speak_text' => esc_html__('Speak', 'autonomous-ai-chatbot'),
				'user_text' => esc_html__('User Text', 'autonomous-ai-chatbot'),
				'ai_text' => esc_html__('AI Text', 'autonomous-ai-chatbot'),
				'voice_example' => __("Example: Write a blog post about the latest mobile phones and their features. Include an introduction that highlights the importance of mobile phones in today's world. In the body of the post, discuss the latest mobile phone trends, such as foldable screens, 5G connectivity, and high refresh rate displays. Also, mention the most popular mobile phone brands and their latest releases. Don't forget to discuss the benefits and drawbacks of each phone and how they compare to one another. In the conclusion, summarize the key points of the post", 'autonomous-ai-chatbot'),
			);
		}


		/**
		 * Retrieves all front translations for the custom post type (CPT) bot.
		 *
		 * @return array Array of translations for various strings.
		 */
		public function wc_cpt_get_all_front_translations()
		{
			return array(
				'welcome_msg' => esc_html__('Hello How can i Help You?', 'autonomous-ai-chatbot'),
				'speech_not_support' => esc_html__('Speech recognition not supported in this browser.', 'autonomous-ai-chatbot'),
				'false_res' => $this->wc_cpt_get_all_admin_translations()['false_resp'],
				'wnat_to_know' => esc_html__('Want to know something', 'autonomous-ai-chatbot') . '...',
			);
		}


		/**
		 * Get OpenAI settings for CPT-Bot.
		 *
		 * This function retrieves OpenAI settings from the database and returns them as an associative array.
		 *
		 * @return array An associative array containing OpenAI settings.
		 */
		public function wc_cptbot_openai_settings()
		{

			$setting_data = get_option('wc_cptbot_openai_content_setting', true);

			if (!empty($setting_data)) {
				$open_ai_temp = !empty($setting_data['open_ai_temp']) ? $setting_data['open_ai_temp'] : '0.7';
				$open_ai_token = !empty($setting_data['open_ai_token']) ? $setting_data['open_ai_token'] : '3000';
				$open_ai_top = !empty($setting_data['open_ai_top']) ? $setting_data['open_ai_top'] : '0.01';
				$open_ai_title = !empty($setting_data['open_ai_title']) ? $setting_data['open_ai_title'] : esc_html__('Suggest', 'autonomous-ai-chatbot') . ' {count} ' . esc_html__('title for an article about', 'autonomous-ai-chatbot') . ' {topic}';
				$open_ai_section = !empty($setting_data['open_ai_section']) ? $setting_data['open_ai_section'] : esc_html__('Write', 'autonomous-ai-chatbot') . ' {count} ' . esc_html__('consecutive headings for an article about', 'autonomous-ai-chatbot') . ' {topic}';
				$open_ai_content = !empty($setting_data['open_ai_content']) ? $setting_data['open_ai_content'] : esc_html__('Write a comprehensive article about', 'autonomous-ai-chatbot') . ' {topic}' . esc_html__(', covering the following subtopics', 'autonomous-ai-chatbot') . ' {sections}.' . esc_html__('Each subtopic should have at least', 'autonomous-ai-chatbot') . ' {count} ' . esc_html__('paragraphs. Use a cohesive structure to ensure smooth transitions between ideas. Include relevant statistics, examples, and quotes to support your arguments and engage the reader.', 'autonomous-ai-chatbot');
				$open_ai_excerpt = !empty($setting_data['open_ai_excerpt']) ? $setting_data['open_ai_excerpt'] : esc_html__('Generate an excerpt for', 'autonomous-ai-chatbot') . ' {topic}. ' . esc_html__('Max: 55 words', 'autonomous-ai-chatbot');
			}

			$openai_settings = array(
				array(
					'label' => esc_html__('Temperature', 'autonomous-ai-chatbot'),
					'class' => 'ai-col-setting',
					'field' => array(
						'name' => 'open_ai_temp',
						'type' => 'input',
						'value' => $open_ai_temp,
					),
				),
				array(
					'label' => esc_html__('Max Tokens', 'autonomous-ai-chatbot'),
					'class' => 'ai-col-setting',
					'field' => array(
						'name' => 'open_ai_token',
						'type' => 'input',
						'value' => $open_ai_token,
					),
				),
				array(
					'label' => esc_html__('Top P', 'autonomous-ai-chatbot'),
					'class' => 'ai-col-setting',
					'field' => array(
						'name' => 'open_ai_top',
						'type' => 'input',
						'value' => $open_ai_top,
					),
				),
				array(
					'label' => esc_html__('Prompt for Title', 'autonomous-ai-chatbot'),
					'class' => 'ai-row-setting',
					'field' => array(
						'name' => 'open_ai_topic',
						'type' => 'textarea',
						'height' => 50,
						'value' => $open_ai_title,
					),
					'footer' => esc_html__('Ensure', 'autonomous-ai-chatbot') . ' <code>{count}</code>' . esc_html__('and', 'autonomous-ai-chatbot') . '  <code>{topic}</code> ' . esc_html__('is included in your prompt.', 'autonomous-ai-chatbot'),
				),
				array(
					'label' => esc_html__('Prompt for Sections', 'autonomous-ai-chatbot'),
					'class' => 'ai-row-setting',
					'field' => array(
						'name' => 'open_ai_section',
						'type' => 'textarea',
						'height' => 70,
						'value' => $open_ai_section,
					),
					'footer' => esc_html__('Ensure', 'autonomous-ai-chatbot') . ' <code>{count}</code>' . esc_html__('and', 'autonomous-ai-chatbot') . '  <code>{topic}</code> ' . esc_html__('is included in your prompt.', 'autonomous-ai-chatbot'),
				),
				array(
					'label' => esc_html__('Prompt for Content', 'autonomous-ai-chatbot'),
					'class' => 'ai-row-setting',
					'field' => array(
						'name' => 'open_ai_content',
						'type' => 'textarea',
						'height' => 120,
						'value' => $open_ai_content,
					),
					'footer' => esc_html__('Ensure', 'autonomous-ai-chatbot') . ' <code>{topic}</code>, <code>{sections}</code>, ' . esc_html__('and', 'autonomous-ai-chatbot') . '  <code>{count}</code> ' . esc_html__('are included in your prompt.', 'autonomous-ai-chatbot'),
				),
				array(
					'label' => esc_html__('Prompt for Excerpt', 'autonomous-ai-chatbot'),
					'class' => 'ai-row-setting',
					'field' => array(
						'name' => 'open_ai_excerpt',
						'type' => 'textarea',
						'height' => 70,
						'value' => $open_ai_excerpt,
					),
					'footer' => esc_html__('Ensure', 'autonomous-ai-chatbot') . ' <code>{topic}</code> ' . esc_html__('is included in your prompt.', 'autonomous-ai-chatbot'),
				),
				array(
					'label' => esc_html__('Prompt for Meta', 'autonomous-ai-chatbot'),
					'class' => 'ai-row-setting',
					'field' => array(
						'name' => 'open_ai_meta',
						'type' => 'textarea',
						'height' => 50,
						'value' => $open_ai_excerpt,
					),
					'footer' => esc_html__('Ensure', 'autonomous-ai-chatbot') . ' <code>{topic}</code> ' . esc_html__('is included in your prompt.', 'autonomous-ai-chatbot'),
				),
			);

			/**
			 * Filters the OpenAI settings for CPT-Bot.
			 *
			 * @hook WCAICHATBOT_ai_bot_modify_openai_settings
			 */
			return apply_filters('wcaichatbot_ai_bot_modify_openai_settings', $openai_settings);
		}

	}

}
