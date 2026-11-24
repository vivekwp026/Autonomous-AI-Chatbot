<?php
/**
 * File handler
 *
 * @package Chatgpt bot for WooCommerce
 * @version 1.0.0
 */

namespace WCAICHATBOT\Templates\Admin\Settings;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'Wc_Cptbot_Configuration_Ui' ) ) {

	/**
	 * File handler class
	 */
	class Wc_Cptbot_Configuration_Ui {


		/**
		 * Get the value of export_product_enable.
		 *
		 * @var string
		 *
		 * @var string
		 *
		 * @return bool
		 */
		protected $export_product_enable;

		/**
		 * Get the value of create_index_enable.
		 *
		 * @var string
		 *
		 * @var string
		 *
		 * @return bool
		 */
		protected $create_index_enable;

		/**
		 * Get the value of create_index_text.
		 *
		 * @var string
		 *
		 * @return bool
		 */
		protected $create_index_text;

		/**
		 * Get the value of base_index_enable.
		 *
		 * @var string
		 *
		 * @var string
		 *
		 * @return bool
		 */
		protected $base_index_enable;

		/**
		 * Get the value of avatar.
		 *
		 * @var string
		 *
		 * @var string
		 *
		 * @return string
		 */
		protected $avatar;

		/**
		 * Get the value of chatbot_bg_image.
		 *
		 * @var string
		 *
		 * @var string
		 *
		 * @return string
		 */
		protected $chatbot_bg_image;

		/**
		 * Get the value of ai_logo.
		 *
		 * @var string
		 *
		 * @var string
		 *
		 * @return string
		 */
		protected $ai_logo;

		/**
		 * Get the value of primary_color.
		 *
		 * @var string
		 *
		 * @var string
		 *
		 * @return string
		 */
		protected $primary_color;

		/**
		 * Get the value of primary_color_list.
		 *
		 * @var string
		 *
		 * @var string
		 *
		 * @return array
		 */
		protected $primary_color_list;

		/**
		 * Get the value of secondary_color.
		 *
		 * @var string
		 *
		 * @var string
		 *
		 * @return string
		 */
		protected $secondary_color;

		/**
		 * Get the value of secondary_color_list.
		 *
		 * @var string
		 *
		 * @var string
		 *
		 * @return array
		 */
		protected $secondary_color_list;

		/**
		 * Get the value of voice_assistance.
		 *
		 * @var string
		 *
		 * @var string
		 *
		 * @return bool
		 */
		protected $voice_assistance;

		/**
		 * Get the value of animation.
		 *
		 * @var string
		 *
		 * @var string
		 *
		 * @return bool
		 */
		protected $animation;

		/**
		 * Get the value of admin_background.
		 *
		 * @var string
		 *
		 * @var string
		 *
		 * @return string
		 */
		protected $admin_background;

		/**
		 * Get the value of cpt_bot_position.
		 *
		 * @var string
		 *
		 * @return string
		 */
		protected $cpt_bot_position;

		/**
		 * Get the value of cpt_bot_model_type.
		 *
		 * @var string
		 *
		 * @return string
		 */
		protected $cpt_bot_model_type;

		/**
		 * Get the value of Gemini model.
		 *
		 * @var string $cpt_bot_gemini_model Get the value of Gemini model.
		 */
		protected $cpt_bot_gemini_model;

		/**
		 * Get the value of guest_history_enable.
		 *
		 * @var bool
		 */
		protected $guest_history_enable;



		/**
		 * Get the value of wc_cpt_bot_status.
		 *
		 * @var string
		 *
		 * @return string
		 */
		protected $wc_cpt_bot_status;

		/**
		 * Construct.
		 */
		public function __construct() {
			// Set initial values for variables.
			$this->create_index_enable   = 0;
			$this->create_index_text     = esc_html__( 'Create Index', 'autonomous-ai-chatbot' );
			$this->base_index_enable     = 0;
			$this->export_product_enable = 0;

			$this->cpt_bot_model_type = ! empty( get_option( 'cpt_bot_model_type' ) ) ? get_option( 'cpt_bot_model_type' ) : 'onnx';

			// Determine if voice assistance is enabled based on option settings.
			$this->voice_assistance = ! empty( get_option( 'cpt_allow_voice_assistant' ) && 'on' === get_option( 'cpt_allow_voice_assistant' ) ) ? true : false;

			// Determine if admin background is enabled based on option settings.
			$this->admin_background = ! empty( get_option( 'cpt_allow_admin_background' ) ) && 'on' === get_option( 'cpt_allow_admin_background' ) ? true : false;

			// Determine if animations are enabled based on option settings.
			$this->animation = ! empty( get_option( 'cpt_allow_animations' ) ) && 'on' === get_option( 'cpt_allow_animations' ) ? true : false;

			// Determine if guest history is enabled based on option settings.
			$this->guest_history_enable = ! empty( get_option( 'cpt_enable_guest_history' ) ) && 'on' === get_option( 'cpt_enable_guest_history' ) ? true : false;

			// Check if required options are set to enable create_index.
			if ( ! empty( get_option( 'wc_cptbot_pinecone_key' ) ) && ! empty( get_option( 'wc_cptbot_pinecone_env' ) ) ) {
				$this->create_index_enable = 1;
				$this->create_index_text   = esc_html__( 'Recreate Index', 'autonomous-ai-chatbot' );

			}

			// Check if wc_cptbot_pinecone_index_host option is set to enable base_index.
			$pinecone_host_check = get_option( 'wc_cptbot_pinecone_index_host' );
			if ( empty( $pinecone_host_check ) ) {
				$pinecone_host_check = get_option( 'wc_cptbot_pinecone_host' );
			}
			if ( ! empty( $pinecone_host_check ) ) {
				$this->base_index_enable = 1;
			}

			// Check if required options are set to enable export_product.
			if ( ! empty( $pinecone_host_check ) && ! empty( get_option( 'wc_cptbot_pinecone_key' ) ) && ! empty( get_option( 'wc_cptbot_pinecone_env' ) ) ) {
				$model_type = get_option( 'cpt_bot_model_type', 'onnx' );
				if ( 'onnx' === $model_type || 'ternlight' === $model_type || ! empty( get_option( 'wc_cptbot_open_ai_key' ) ) || ! empty( get_option( 'wc_cptbot_api_key' ) ) || ! empty( get_option( 'wc_cptbot_gemini_key' ) ) ) {
					$this->export_product_enable = 1;
				}
			}

			// Set avatar image URL.
			$base_url     = wp_upload_dir()['baseurl'];
			$this->avatar = ! empty( get_option( 'chatbot_avatar_url' ) ) ? $base_url . get_option( 'chatbot_avatar_url' ) : WC_CPTBOT_PLUGIN_URL . 'assets/images/chatbot-logo.png';

			// Set chatbot background image URL.
			$this->chatbot_bg_image = ! empty( get_option( 'chatbot_bg_image_path' ) ) ? $base_url . get_option( 'chatbot_bg_image_path' ) : WC_CPTBOT_PLUGIN_URL . 'assets/images/bg.webp';

			// Set AI logo image URL.
			$this->ai_logo = ! empty( get_option( 'chatbot_ai_logo_path' ) ) ? $base_url . get_option( 'chatbot_ai_logo_path' ) : WC_CPTBOT_PLUGIN_URL . 'assets/images/chatbot-logo.png';

			// Set primary color.
			$this->primary_color = ! empty( get_option( 'chatbot_primary_color' ) ) ? get_option( 'chatbot_primary_color' ) : '#2C3145';

			// Set primary color list with custom colors.
			$this->primary_color_list = apply_filters( 'wcaichatbot_add_custom_primary_color_in_color_box', array( '#2C3145', '#670000', '#000000', '#36454F', '#023020', '#301934', '#343434', '#1B1212', '#191970', '#28282B', '#2B3D33' ) );

			// Set secondary color.
			$this->secondary_color = ! empty( get_option( 'chatbox_secondary_color' ) ) ? get_option( 'chatbox_secondary_color' ) : '#FFFFFF';

			// Set secondary color list with custom colors.
			$this->secondary_color_list = apply_filters( 'wcaichatbot_add_custom_secondary_color_in_color_box', array( '#FFFFFF', '#F2F4F7', '#ACDDDE', '#FEF8DD', '#F7D8BA', 'C5C086' ) );

			$this->cpt_bot_position = ! empty( get_option( 'cpt_bot_position' ) ) ? get_option( 'cpt_bot_position' ) : 'right';

			// Set the ai chatbot status
			$this->wc_cpt_bot_status = ! empty( get_option( 'wc_cpt_bot_status' ) ) ? get_option( 'wc_cpt_bot_status' ) : 'disable';

			// Call wc_cptbot_get_template function.
			$this->wc_cptbot_get_template();
		}

		/**
		 * Display the configuration page template for the CPT Bot plugin.
		 */
		public function wc_cptbot_get_template() {
			$model_path = WC_CPTBOT_PLUGIN_FILE . 'assets/models/e5-small.onnx';
			$bot_status = get_option( 'wc_cpt_bot_status', 'disable' );
			$total_size = (int) get_option( 'wc_cptbot_model_total_size', 0 );
			$offset     = (int) get_option( 'wc_cptbot_download_offset', 0 );

			$is_file_exist = file_exists( $model_path );
			$offset        = $is_file_exist ? filesize( $model_path ) : 0;

			$percent = 0;
			if ( 0 < $total_size && 0 < $offset ) {
				$percent = min( 100, floor( ( $offset / $total_size ) * 100 ) );
			}

			$download_progress = get_option( 'wc_cptbot_model_downloaded', '' );
			$is_processing     = 'processing' === $download_progress;
			$is_completed      = 'completed' === $download_progress && $is_file_exist;

			?>

			<div id="wrapper">
				<div id="dashboard_right_now" class="formcontainer ">
					<div class="wc-pos-wrapper">
						<div class="wc-pos-container">
						<div id="wc-cptbot-notice-area">
						<?php
						$wcaichatbot_errors = function_exists( 'get_settings_errors' ) ? get_settings_errors() : array();
						$wcaichatbot_has_error = false;
						if ( ! empty( $wcaichatbot_errors ) ) {
							foreach ( $wcaichatbot_errors as $wcaichatbot_err ) {
								if ( 'error' === $wcaichatbot_err['type'] ) {
									$wcaichatbot_has_error = true;
								}
								if ( 'settings_updated' === $wcaichatbot_err['code'] ) {
									continue;
								}
								$wcaichatbot_err_type = ( 'error' === $wcaichatbot_err['type'] ) ? 'error' : ( ( 'updated' === $wcaichatbot_err['type'] || 'success' === $wcaichatbot_err['type'] ) ? 'success' : 'info' );
								?>
								<div class="wc-cptbot-notice wc-cptbot-<?php echo esc_attr( $wcaichatbot_err_type ); ?>">
									<p><?php echo esc_html( $wcaichatbot_err['message'] ); ?></p>
									<button type="button" class="wc-cptbot-notice-dismiss" aria-label="<?php esc_attr_e( 'Dismiss this notice', 'autonomous-ai-chatbot' ); ?>"></button>
								</div>
								<?php
							}
						}
						?>
						<?php
						// phpcs:ignore WordPress.Security.NonceVerification.Recommended
						if ( isset( $_GET['settings-updated'] ) && 'true' === sanitize_text_field( wp_unslash( $_GET['settings-updated'] ) ) && ! $wcaichatbot_has_error ) :
						?>
							<div class="wc-cptbot-notice wc-cptbot-success">
								<p>
								<?php
									esc_html_e( 'Configuration saved successfully.', 'autonomous-ai-chatbot' );
								?>
								</p>
								<button type="button" class="wc-cptbot-notice-dismiss" aria-label="<?php esc_attr_e( 'Dismiss this notice', 'autonomous-ai-chatbot' ); ?>"></button>
							</div>
						<?php endif; ?>
						<?php if ( 0 >= $percent && 'enable' === $bot_status ) : ?>
							<div class="wc-cptbot-notice wc-cptbot-info">
								<p>
								<?php
									esc_html_e( 'Please wait, We are preparing to download ai model.', 'autonomous-ai-chatbot' );
								?>
								</p>
							</div>

						<?php endif; ?>


			<?php if ( 0 < $percent && $is_processing && 'enable' === $bot_status ) : ?>
							<div class="wc-cptbot-notice wc-cptbot-info">
								<p>
								<?php

									echo esc_html(
										sprintf(
										/* translators: %s: Download percentage. */
											__( 'Model is downloading…%s', 'autonomous-ai-chatbot' ),
											absint( $percent ) . '%'
										)
									);

								?>
									</p>
							</div>
			<?php endif; ?>
			<?php if ( $is_completed ) : ?>
							<div class="wc-cptbot-notice wc-cptbot-success">
								<p>
								<?php
									esc_html_e( 'Model downloaded successfully.', 'autonomous-ai-chatbot' );
								?>
								</p>
								<button type="button" class="wc-cptbot-notice-dismiss" aria-label="<?php esc_attr_e( 'Dismiss this notice', 'autonomous-ai-chatbot' ); ?>"></button>
							</div>
						<?php endif; ?>
						<?php if ( ! $is_file_exist && 0 < $percent ) : ?>
							<div class="wc-cptbot-notice wc-cptbot-info">
								<p>
									<?php
									esc_html_e( 'Please click on download model button to download the model.', 'autonomous-ai-chatbot' );
									?>
								</p>
								<button type="button" class="wc-cptbot-notice-dismiss" aria-label="<?php esc_attr_e( 'Dismiss this notice', 'autonomous-ai-chatbot' ); ?>"></button>
							</div>
			<?php endif; ?>
						</div>

							<div class="wc-cptbot-head-button" style="display:flex;justify-content:space-between;align-items:center;">
								<h2 style="margin-top:10px;"><?php esc_html_e( 'Configuration', 'autonomous-ai-chatbot' ); ?></h2>
								<button type="button" id="wc-cptbot-download-model" class="button button-primary" style="display:none;"> <?php esc_html_e( 'Download model', 'autonomous-ai-chatbot' ); ?> </button>
							</div>


							<form method="post" action="options.php">
			<?php settings_fields( 'wc-cptbot-settings-group' ); ?>
			<?php do_settings_sections( 'wc-cptbot-settings-group' ); ?>
			<?php wp_nonce_field( 'wc_cptbot_settings_group_nonce', 'wc_cptbot_settings_nonce' ); ?>
								<div class="wc-pos-form-block">
									<div class="wc-pos-form-block-header">
										<span><?php esc_html_e( 'Model Settings', 'autonomous-ai-chatbot' ); ?></span>
									</div>
									<div class="wc-pos-form-block-body">
										<div class="options_group">
											<div class="wc-pos-form-field">
												<div class="wc-pos-input-label">
													<label
														for=""><?php esc_html_e( 'Select Model Type', 'autonomous-ai-chatbot' ); ?></label>


												</div>
												<div class="wc-pos-form-input-wrap">
													<select name="cpt_bot_model_type" id="cpt_bot_model_type">
														<option value="onnx" <?php selected( esc_attr( $this->cpt_bot_model_type ), 'onnx' ); ?>>
															<?php esc_html_e( 'E₅Small ONNX Model', 'autonomous-ai-chatbot' ); ?>
														</option>
														<option value="ternlight" <?php selected( esc_attr( $this->cpt_bot_model_type ), 'ternlight' ); ?>>
															<?php esc_html_e( 'Ternlight Base Model', 'autonomous-ai-chatbot' ); ?>
														</option>
														<option value="gemini" <?php selected( esc_attr( $this->cpt_bot_model_type ), 'gemini' ); ?>>
															<?php esc_html_e( 'Gemini Embedding Model', 'autonomous-ai-chatbot' ); ?>
														</option>
														<option value="openai" <?php selected( esc_attr( $this->cpt_bot_model_type ), 'openai' ); ?>>
															<?php esc_html_e( 'OpenAI Embedding Model', 'autonomous-ai-chatbot' ); ?>
														</option>
													</select>
												</div>

												<div class="wc-pos-input-label">
													<label for=""><?php esc_html_e( 'Status', 'autonomous-ai-chatbot' ); ?></label>
												</div>

												<div class="wc-pos-form-input-wrap">
													<select name="wc_cpt_bot_status"  id="wc_cpt_bot_status">
															<option value="disable" <?php selected( esc_attr( $this->wc_cpt_bot_status ), 'disable' ); ?>>
															<?php esc_html_e( 'Disable', 'autonomous-ai-chatbot' ); ?>
														</option>
														<option value="enable" <?php selected( esc_attr( $this->wc_cpt_bot_status ), 'enable' ); ?>>
															<?php esc_html_e( 'Enable', 'autonomous-ai-chatbot' ); ?>
														</option>

													</select>
												</div>
											</div>
										</div>

										<!-- Gemini Settings -->
										<div id="gemini_settings" style="display:none;">
											<div class="options_group">
												<div class="wc-pos-form-field">
													<div class="wc-pos-input-label">
														<label
															for="wc_cptbot_gemini_api_key"><?php esc_html_e( 'Gemini API Key', 'autonomous-ai-chatbot' ); ?>
															<b style="color:red"> *</b></label>
													</div>
													<div class="wc-pos-form-input-wrap">
														<input type="password" class="wc-pos-form-input"
															name="wc_cptbot_gemini_api_key" id="wc_cptbot_gemini_api_key"
															value="<?php echo esc_attr( get_option( 'wc_cptbot_gemini_api_key' ) ); ?>"
															placeholder="Gemini API Key">
													</div>
												</div>
											</div>
											<div class="options_group">
												<div class="wc-pos-form-field">
													<div class="wc-pos-input-label">
														<label
															for="wc_cptbot_gemini_embedding_model"><?php esc_html_e( 'Gemini Embedding Model Name', 'autonomous-ai-chatbot' ); ?>
															<b style="color:red"> *</b></label>
													</div>
													<div class="wc-pos-form-input-wrap">
														<input type="text" class="wc-pos-form-input"
															name="wc_cptbot_gemini_embedding_model"
															id="wc_cptbot_gemini_embedding_model"
															value="<?php echo esc_attr( get_option( 'wc_cptbot_gemini_embedding_model' ) ); ?>"
															placeholder="models/text-embedding-004">
													</div>
												</div>
											</div>
										</div>

										<!-- OpenAI Embedding Settings -->
										<div id="openai_embedding_settings" style="display:none;">
											<div class="options_group">
												<div class="wc-pos-form-field">
													<div class="wc-pos-input-label">
														<label
															for="wc_cptbot_openai_embedding_api_key"><?php esc_html_e( 'OpenAI API Key', 'autonomous-ai-chatbot' ); ?>
															<b style="color:red"> *</b></label>
													</div>
													<div class="wc-pos-form-input-wrap">
														<input type="password" class="wc-pos-form-input"
															name="wc_cptbot_openai_embedding_api_key"
															id="wc_cptbot_openai_embedding_api_key"
															value="<?php echo esc_attr( get_option( 'wc_cptbot_openai_embedding_api_key' ) ); ?>"
															placeholder="sk-...">
													</div>
												</div>
											</div>
											<div class="options_group">
												<div class="wc-pos-form-field">
													<div class="wc-pos-input-label">
														<label
															for="wc_cptbot_openai_embedding_model"><?php esc_html_e( 'OpenAI Embedding Model Name', 'autonomous-ai-chatbot' ); ?>
															<b style="color:red"> *</b></label>
													</div>
													<div class="wc-pos-form-input-wrap">
														<input type="text" class="wc-pos-form-input"
															name="wc_cptbot_openai_embedding_model"
															id="wc_cptbot_openai_embedding_model"
															value="<?php echo esc_attr( get_option( 'wc_cptbot_openai_embedding_model' ) ); ?>"
															placeholder="text-embedding-3-small">
													</div>
												</div>
											</div>
										</div>

										<script>
											document.addEventListener('DOMContentLoaded', function () {
												var modelTypeSelect = document.getElementById('cpt_bot_model_type');
												var geminiSettings = document.getElementById('gemini_settings');
												var openaiSettings = document.getElementById('openai_embedding_settings');

												function toggleSettings() {
													var selected = modelTypeSelect.value;
													if (selected === 'gemini') {
														geminiSettings.style.display = 'block';
														openaiSettings.style.display = 'none';
													} else if (selected === 'openai') {
														geminiSettings.style.display = 'none';
														openaiSettings.style.display = 'block';
													} else {
														geminiSettings.style.display = 'none';
														openaiSettings.style.display = 'none';
													}
												}

												modelTypeSelect.addEventListener('change', toggleSettings);
												toggleSettings(); // Run on load
											});
										</script>
									</div>
								</div>
								<div class="wc-pos-form-block">
									<div class="wc-pos-form-block-header chatbox-header">
										<span><?php esc_html_e( 'Vector DB Selection', 'autonomous-ai-chatbot' ); ?></span>
									</div>
									<div class="wc-pos-form-block-body">
										<div class="options_group">
											<div class="wc-pos-form-field">
												<div class="wc-pos-input-label">
													<label
														for=""><?php esc_html_e( 'Vector DB', 'autonomous-ai-chatbot' ); ?></label>
												</div>
												<div class="wc-pos-form-input-wrap">
													<select name="wc_cptbot_vector_db" id="wc_cptbot_vector_db"
														class="wc-pos-form-input">
														<option value="pinecone" <?php selected( get_option( 'wc_cptbot_vector_db', 'pinecone' ), 'pinecone' ); ?>>
															<?php esc_html_e( 'Pinecone', 'autonomous-ai-chatbot' ); ?>
														</option>
														<option value="chromadb" <?php selected( get_option( 'wc_cptbot_vector_db' ), 'chromadb' ); ?>>
															<?php esc_html_e( 'ChromaDB', 'autonomous-ai-chatbot' ); ?>
														</option>
													</select>
												</div>
											</div>
										</div>
										<div id="chromadb_settings_wrapper">
											<div class="options_group">
												<div class="wc-pos-form-field">
													<div class="wc-pos-input-label">
														<label
															for=""><?php esc_html_e( 'Chroma Base URL', 'autonomous-ai-chatbot' ); ?></label>
													</div>
													<div class="wc-pos-form-input-wrap">
														<input type="url" class="wc-pos-form-input" name="wc_cptbot_chroma_base_url"
															id="wc_cptbot_chroma_base_url"
															value="<?php echo esc_url( get_option( 'wc_cptbot_chroma_base_url' ) ); ?>"
															placeholder="https://localhost:8000/">

													</div>
												</div>
											</div>
											<div class="options_group">
												<div class="wc-pos-form-field">
													<div class="wc-pos-input-label">
														<label
															for=""><?php esc_html_e( 'Chroma Collection Name', 'autonomous-ai-chatbot' ); ?></label>
													</div>
													<div class="wc-pos-form-input-wrap">
														<input type="text" class="wc-pos-form-input"
															name="wc_cptbot_chroma_collection" id="wc_cptbot_chroma_collection"
															value="<?php echo esc_attr( get_option( 'wc_cptbot_chroma_collection' ) ); ?>"
															placeholder="chatgpt">

													</div>
												</div>
											</div>
											<div class="options_group">
												<div class="wc-pos-form-field">
													<div class="wc-pos-input-label">
														<label
															for=""><?php esc_html_e( 'Chroma API Key (optional)', 'autonomous-ai-chatbot' ); ?></label>
													</div>
													<div class="wc-pos-form-input-wrap">
														<input type="text" class="wc-pos-form-input" name="wc_cptbot_chroma_api_key"
															id="wc_cptbot_chroma_api_key"
															value="<?php echo esc_attr( get_option( 'wc_cptbot_chroma_api_key' ) ); ?>"
															placeholder="Optional API Key">

													</div>
												</div>
											</div>
										</div>
									</div>
						</div>
									<div class="wc-pos-form-block">
									<div id="pinecone_settings_wrapper">
										<div class="wc-pos-form-block-header chatbox-header">
											<span><?php esc_html_e( 'PineCone Settings', 'autonomous-ai-chatbot' ); ?></span>
											<?php
											if ( esc_attr( $this->create_index_enable ) ) {
												?>
												<button type="button" class="button" id="cptbot_create_index"
													title="<?php esc_html_e( 'Create Pinecone Index for collecting your WooCommerce Products embadded data', 'autonomous-ai-chatbot' ); ?>"><?php echo esc_attr( $this->create_index_text ); ?></button>
												<?php
											}
											?>
										</div>

										<div class="wc-pos-form-block-body">
											<div class="options_group">
												<div class="wc-pos-form-field">
													<div class="wc-pos-input-label">
														<label
															for=""><?php esc_html_e( 'Secret Key', 'autonomous-ai-chatbot' ); ?>
															<b style="color:red"> *</b></label>
													</div>
													<div class="wc-pos-form-input-wrap">
														<input type="text" class="wc-pos-form-input" name="wc_cptbot_pinecone_key"
															id="wc_cptbot_pinecone_key"
															value="<?php echo esc_attr( get_option( 'wc_cptbot_pinecone_key' ) ); ?>"
															title="<?php echo esc_attr( get_option( 'wc_cptbot_pinecone_key' ) ); ?>"
															placeholder="XXXXXX">
													</div>
												</div>
											</div>
											<div class="options_group">
												<div class="wc-pos-form-field">
													<div class="wc-pos-input-label">
														<label
															for=""><?php esc_html_e( 'Environment', 'autonomous-ai-chatbot' ); ?>
															<b style="color:red"> *</b></label>
													</div>
													<div class="wc-pos-form-input-wrap">
														<input type="text" class="wc-pos-form-input" name="wc_cptbot_pinecone_env"
															id="wc_cptbot_pinecone_env"
															value="<?php echo esc_attr( get_option( 'wc_cptbot_pinecone_env' ) ); ?>"
															title="<?php echo esc_attr( get_option( 'wc_cptbot_pinecone_env' ) ); ?>"
															placeholder="XXXXXX">
													</div>
												</div>
											</div>

											<div class="options_group">
												<div class="wc-pos-form-field">
													<div class="wc-pos-input-label">
														<label
															for=""><?php esc_html_e( 'Base URL', 'autonomous-ai-chatbot' ); ?></label>
													</div>
													<div class="wc-pos-form-input-wrap">
														<?php
														$raw_pinecone_host = get_option( 'wc_cptbot_pinecone_index_host' );
														if ( empty( $raw_pinecone_host ) ) {
															$raw_pinecone_host = get_option( 'wc_cptbot_pinecone_host' );
														}
														$clean_pinecone_host   = ! empty( $raw_pinecone_host ) ? preg_replace( '#^https?://#i', '', rtrim( $raw_pinecone_host, '/' ) ) : '';
														$display_pinecone_host = ! empty( $clean_pinecone_host ) ? 'https://' . $clean_pinecone_host . '/' : '';
														?>
														<input type="url" id="wc_cptbot_pinecone_index_host"
															class="wc-pos-form-input"
															value="<?php echo esc_attr( $display_pinecone_host ); ?>"
															title="<?php echo esc_attr( $display_pinecone_host ); ?>"
															placeholder="https://your-index-xxxx.svc.pinecone.io/"
															disabled>
														<span id="wc_cptbot_pinecone_index_host_edit"
															class="dashicons dashicons-edit"></span>
														<input type="hidden" name="wc_cptbot_pinecone_index_host"
															id="wc_cptbot_pinecone_index_host_hidden"
															value="<?php echo esc_attr( $clean_pinecone_host ); ?>">
														<input type="hidden" name="wc_cptbot_pinecone_host"
															id="wc_cptbot_pinecone_host_hidden"
															value="<?php echo esc_attr( $clean_pinecone_host ); ?>">
													</div>
												</div>
											</div>
											<div class="options_group">
												<div class="wc-pos-form-field">
													<div class="wc-pos-input-label">
														<label
															for=""><?php esc_html_e( 'Index Name', 'autonomous-ai-chatbot' ); ?></label>
													</div>
													<div class="wc-pos-form-input-wrap">
														<input id="wc_cptbot_pinecone_index_name" type="text"
															class="wc-pos-form-input"
															value="<?php echo ! empty( get_option( 'wc_cptbot_pinecone_indexname' ) ) ? esc_attr( get_option( 'wc_cptbot_pinecone_indexname' ) ) : 'chatgpt'; ?>"
															title="product" disabled>
														<span id="wc_cptbot_pinecone_index_name_edit"
															class="dashicons dashicons-edit"></span>
														<input type="hidden" name="wc_cptbot_pinecone_indexname"
															id="wc_cptbot_pinecone_indexname"
															value="<?php echo ! empty( get_option( 'wc_cptbot_pinecone_indexname' ) ) ? esc_attr( get_option( 'wc_cptbot_pinecone_indexname' ) ) : 'chatgpt'; ?>">
													</div>
												</div>
											</div>

										</div>
										<div class="wc-cptbot-keys">
				<?php printf( /* translators: %s pinecone links */ esc_html__( '%s to Generate The Pinecone Keys.', 'autonomous-ai-chatbot' ), '<a href="https://www.pinecone.io/" target="_blank">' . esc_html__( 'Click Here', 'autonomous-ai-chatbot' ) . '</a>' ); ?>
										</div>
									</div>
								</div>
								<script>
									document.addEventListener('DOMContentLoaded', function () {
										var vectorDbSelect = document.getElementById('wc_cptbot_vector_db');
										var pineconeSettings = document.getElementById('pinecone_settings_wrapper');
										var chromadbSettings = document.getElementById('chromadb_settings_wrapper');

										function toggleVectorDbSettings() {
											var selected = vectorDbSelect.value;
											if (selected === 'pinecone') {
												if (pineconeSettings) pineconeSettings.style.display = 'block';
												if (chromadbSettings) chromadbSettings.style.display = 'none';
											} else {
												if (pineconeSettings) pineconeSettings.style.display = 'none';
												if (chromadbSettings) chromadbSettings.style.display = 'block';
											}
										}

										if (vectorDbSelect) {
											vectorDbSelect.addEventListener('change', toggleVectorDbSettings);
											toggleVectorDbSettings(); // Run on load
										}
									});
								</script>

								<div class="wc-pos-form-block">
									<div class="wc-pos-form-block-header chatbox-header">
										<span>
				<?php esc_html_e( 'API Configuration', 'autonomous-ai-chatbot' ); ?>
										</span>
									</div>

									<div class="wc-pos-form-block-body">
										<div class="options_group">
											<div class="wc-pos-form-field">
												<div class="wc-pos-input-label">
													<label for=""><?php esc_html_e( 'API Key', 'autonomous-ai-chatbot' ); ?> <b
															style="color:red"> *</b></label>
												</div>
												<div class="wc-pos-form-input-wrap">
													<input type="password" class="wc-pos-form-input" name="wc_cptbot_api_key"
														id="wc_cptbot_api_key"
														value="<?php echo esc_attr( get_option( 'wc_cptbot_api_key' ) ); ?>"
														title="API Key" placeholder="sk-...">

												</div>
											</div>
										</div>
										<div class="options_group">
											<div class="wc-pos-form-field">
												<div class="wc-pos-input-label">
													<label for=""><?php esc_html_e( 'API Base URL', 'autonomous-ai-chatbot' ); ?>
														<b style="color:red"> *</b></label>
												</div>
												<div class="wc-pos-form-input-wrap">
													<input type="url" class="wc-pos-form-input" name="wc_cptbot_api_base_url"
														id="wc_cptbot_api_base_url"
														value="<?php echo esc_url( get_option( 'wc_cptbot_api_base_url' ) ); ?>"
														title="API Base URL" placeholder="https://api.openai.com/v1/">

												</div>
											</div>
										</div>
										<div class="options_group">
											<div class="wc-pos-form-field">
												<div class="wc-pos-input-label">
													<label for=""><?php esc_html_e( 'Model Name', 'autonomous-ai-chatbot' ); ?>
														<b style="color:red"> *</b></label>
												</div>
												<div class="wc-pos-form-input-wrap">
													<input type="text" class="wc-pos-form-input" name="wc_cptbot_model_name"
														id="wc_cptbot_model_name"
														value="<?php echo esc_attr( get_option( 'wc_cptbot_model_name' ) ); ?>"
														title="Model Name" placeholder="gpt-3.5-turbo">

												</div>
											</div>
										</div>
										<div class="options_group">
											<div class="wc-pos-form-field">
												<div class="wc-pos-input-label">
													<label for=""><?php esc_html_e( 'TypeSafe API Key (Jev Intent)', 'autonomous-ai-chatbot' ); ?></label>
												</div>
												<div class="wc-pos-form-input-wrap">
													<input type="password" class="wc-pos-form-input" name="wc_cptbot_typesafe_api_key" id="wc_cptbot_typesafe_api_key" value="<?php echo esc_attr( get_option( 'wc_cptbot_typesafe_api_key' ) ); ?>" title="TypeSafe API Key" placeholder="ts-..." >
												</div>
											</div>
										</div>
										<div class="options_group">
											<div class="wc-pos-form-field">
												<div class="wc-pos-input-label">
													<label for=""><?php esc_html_e( 'TypeSafe Model', 'autonomous-ai-chatbot' ); ?></label>
												</div>
												<div class="wc-pos-form-input-wrap">
													<input type="text" class="wc-pos-form-input" name="wc_cptbot_typesafe_model" id="wc_cptbot_typesafe_model" value="<?php echo esc_attr( get_option( 'wc_cptbot_typesafe_model', 'jev-latest' ) ); ?>" title="TypeSafe Model" placeholder="jev-latest" >
												</div>
											</div>
										</div>
										<div class="options_group">
											<div class="wc-pos-form-field">
												<div class="wc-pos-input-label">
													<label for=""><?php esc_html_e( 'TypeSafe Confidence Threshold', 'autonomous-ai-chatbot' ); ?></label>
												</div>
												<div class="wc-pos-form-input-wrap">
													<input type="number" min="0" max="1" step="0.05" class="wc-pos-form-input" name="wc_cptbot_typesafe_confidence_threshold" id="wc_cptbot_typesafe_confidence_threshold" value="<?php echo esc_attr( get_option( 'wc_cptbot_typesafe_confidence_threshold', '0.5' ) ); ?>" title="TypeSafe Confidence Threshold" placeholder="0.5" >
												</div>
											</div>
										</div>
									</div>
									<!-- <div class="wc-cptbot-keys">
				<?php printf( /* translators: %s openai links */ esc_html__( 'Embeddings will be computed locally using ONNX model (e5-small). Get API keys from: %s', 'autonomous-ai-chatbot' ), '<a href="https://platform.openai.com/api-keys" target="_blank">' . esc_html__( 'OpenAI', 'autonomous-ai-chatbot' ) . '</a>' ); ?>
										</div> -->
								</div>
								<div class="wc-pos-form-block">
									<div class="wc-pos-form-block-header chatbox-header">
										<span><?php esc_html_e( 'Stripe MCP In-Chat Checkout', 'autonomous-ai-chatbot' ); ?> <?php echo wp_kses_post( wcaichatbot_get_crown_icon_html() ); ?></span>
									</div>
									<div class="wc-pos-form-block-body">
										<?php
										if ( function_exists( 'wcaichatbot_is_pro_active' ) && wcaichatbot_is_pro_active() ) {
											do_action( 'wcaichatbot_admin_settings_payment_fields' );
										} else {
											$ghost_stripe = '<div style="display:grid; grid-template-columns:repeat(2, 1fr); grid-gap:20px 24px; width:100%; pointer-events:none; user-select:none;">
												<div class="options_group" style="padding:0; margin:0;">
													<div class="wc-pos-form-field" style="display:flex; flex-direction:column; gap:6px;">
														<div class="wc-pos-input-label" style="margin:0; display:flex; align-items:center;">
															<label style="font-size:13px; font-weight:600; color:#475569; margin:0;">' . esc_html__( 'Enable Stripe MCP', 'autonomous-ai-chatbot' ) . '</label>
														</div>
														<div class="wc-pos-form-input-wrap" style="position:relative; display:flex; width:100%;">
															<select disabled style="width:100%; height:40px; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; background-color:#fff; color:#334155; font-size:14px; appearance:none; background-image:url(\'data:image/svg+xml;charset=UTF-8,%3csvg xmlns=\\\'http://www.w3.org/2000/svg\\\' viewBox=\\\'0 0 24 24\\\' fill=\\\'none\\\' stroke=\\\'%23475569\\\' stroke-width=\\\'2\\\' stroke-linecap=\\\'round\\\' stroke-linejoin=\\\'round\\\'%3e%3cpolyline points=\\\'6 9 12 15 18 9\\\'%3e%3c/polyline%3e%3c/svg%3e\'); background-repeat:no-repeat; background-position:right 14px center; background-size:16px;">
																<option>' . esc_html__( 'Disable', 'autonomous-ai-chatbot' ) . '</option>
															</select>
														</div>
													</div>
												</div>
												<div class="options_group" style="padding:0; margin:0;">
													<div class="wc-pos-form-field" style="display:flex; flex-direction:column; gap:6px;">
														<div class="wc-pos-input-label" style="margin:0; display:flex; align-items:center;">
															<label style="font-size:13px; font-weight:600; color:#475569; margin:0;">' . esc_html__( 'Stripe Publishable Key', 'autonomous-ai-chatbot' ) . '</label>
														</div>
														<div class="wc-pos-form-input-wrap" style="position:relative; display:flex; width:100%;">
															<input type="text" class="wc-pos-form-input" placeholder="pk_test_..." disabled style="width:100%; height:40px; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; background-color:#fff; color:#334155; font-size:14px;" />
														</div>
													</div>
												</div>
												<div class="options_group" style="padding:0; margin:0;">
													<div class="wc-pos-form-field" style="display:flex; flex-direction:column; gap:6px;">
														<div class="wc-pos-input-label" style="margin:0; display:flex; align-items:center;">
															<label style="font-size:13px; font-weight:600; color:#475569; margin:0;">' . esc_html__( 'Stripe Secret Key', 'autonomous-ai-chatbot' ) . '</label>
														</div>
														<div class="wc-pos-form-input-wrap" style="position:relative; display:flex; width:100%;">
															<input type="password" class="wc-pos-form-input" placeholder="sk_test_..." disabled style="width:100%; height:40px; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; background-color:#fff; color:#334155; font-size:14px;" />
														</div>
													</div>
												</div>
											</div>';

											if ( function_exists( 'wcaichatbot_get_pro_lock_html' ) ) {
												echo wcaichatbot_get_pro_lock_html( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
													esc_html__( 'Stripe MCP In-Chat Checkout', 'autonomous-ai-chatbot' ),
													esc_html__( 'Enable in-chat conversational checkout and direct Stripe payments for WooCommerce orders via Model Context Protocol (MCP).', 'autonomous-ai-chatbot' ),
													$ghost_stripe,
													'',
													null,
													true
												);
											} else {
												echo wcaichatbot_get_pro_banner_html( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
													esc_html__( 'Stripe MCP In-Chat Checkout', 'autonomous-ai-chatbot' ),
													esc_html__( 'Upgrade to the PRO addon to enable in-chat checkout and direct Stripe payments for WooCommerce orders via Model Context Protocol (MCP).', 'autonomous-ai-chatbot' )
												);
											}
										}
										?>
									</div>
								</div>

									<div class="wc-pos-form-block">



									<div class="wc-pos-form-block-header chatbox-header">
										<span><?php esc_html_e( 'Chat Box Settings', 'autonomous-ai-chatbot' ); ?></span>
									</div>

									<div class="wc-pos-form-block-body">
										<div class="options_group">
											<div class="wc-pos-form-field">
												<div class="wc-pos-input-label">
													<label for=""><?php esc_html_e( 'ChatBot Name', 'autonomous-ai-chatbot' ); ?>
														<b style="color:red"> *</b></label>
												</div>
												<div class="wc-pos-form-input-wrap">
													<input type="text" class="wc-pos-form-input" name="chatbot_name"
														id="chatbot_name"
														value="<?php echo esc_attr( get_option( 'chatbot_name' ) ); ?>"
														title="<?php echo esc_attr( get_option( 'chatbot_name' ) ); ?>"
														placeholder="<?php esc_html_e( 'Ex: WooCommerce ChatBot', 'autonomous-ai-chatbot' ); ?>">
												</div>
											</div>
										</div>
										<div class="options_group">
											<div class="wc-pos-form-field">
												<div class="wc-pos-input-label">
													<label
														for=""><?php esc_html_e( 'Chatbot Position', 'autonomous-ai-chatbot' ); ?></label>
												</div>
												<div class="wc-pos-form-input-wrap">
													<select name="cpt_bot_position">
														<option value="right" <?php selected( esc_attr( $this->cpt_bot_position ), 'right' ); ?>>
					<?php esc_html_e( 'Right', 'autonomous-ai-chatbot' ); ?>
														</option>
														<option value="left"
														<?php
														selected( esc_attr( $this->cpt_bot_position ), 'left' );
														?>
														>
					<?php esc_html_e( 'Left', 'autonomous-ai-chatbot' ); ?>
														</option>
													</select>
												</div>
											</div>
										</div>
										<div class="options_group">
											<div class="wc-pos-form-field">
												<div class="wc-pos-input-label">
													<label
														for=""><?php esc_html_e( 'Primary Color', 'autonomous-ai-chatbot' ); ?>
														<b style="color:red"> *</b></label>
												</div>
												<div class="wc-pos-form-input-wrap">
													<input type="color" class="wc-pos-form-input" name="chatbot_primary_color"
														id="chatbot_primary_color"
														title="<?php echo esc_attr( get_option( 'chatbot_primary_color' ) ); ?>"
														list="preset-colors" value="<?php echo esc_attr( $this->primary_color ); ?>">
													<datalist id="preset-colors">
														<?php
														foreach ( array_reverse( (array) $this->primary_color_list ) as $color ) {
															?>
															<option><?php echo esc_attr( $color ); ?></option>
														<?php } ?>
													</datalist>
												</div>
											</div>
										</div>
										<div class="options_group">
											<div class="wc-pos-form-field">
												<div class="wc-pos-input-label">
													<label
														for=""><?php esc_html_e( 'Secondary Color', 'autonomous-ai-chatbot' ); ?>
														<b style="color:red"> *</b></label>
												</div>
												<div class="wc-pos-form-input-wrap">
													<input type="color" class="wc-pos-form-input" name="chatbox_secondary_color"
														id="chatbox_secondary_color"
														title="<?php echo esc_attr( get_option( 'chatbox_secondary_color' ) ); ?>"
														list="preset-secondary-colors"
														value="<?php echo esc_attr( $this->secondary_color ); ?>">
													<datalist id="preset-secondary-colors">
					<?php
					foreach ( array_reverse( (array) $this->secondary_color_list ) as $color ) {
						?>
															<option><?php echo esc_attr( $color ); ?></option>
					<?php } ?>
													</datalist>
												</div>
											</div>
										</div>
										<div class="options_group">
											<div class="wc-pos-form-field">
												<div class="wc-pos-input-label">
													<label
														for=""><?php esc_html_e( 'ChatBot Avatar', 'autonomous-ai-chatbot' ); ?>
														<b style="color:red"> *</b></label>
												</div>
												<div class="wc-pos-form-input-wrap img-wrap">
													<div class="wc-cpt-image">
														<input type="hidden" name="chatbot_avatar_url" id="chatbot_avatar_url"
															value="<?php echo esc_attr( get_option( 'chatbot_avatar_url' ) ); ?>">
														<img src="<?php echo esc_attr( $this->avatar ); ?>" alt="Chatbot Avatar"
															class="wc-chatbot-avatar">
														<button type="button" id="chatbot_avatar"
															class="button-secondary btn-upload">
															<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
																viewBox="0 0 24 24" fill="none" stroke="currentColor"
																stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
																class="feather feather-upload">
																<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
																<polyline points="17 8 12 3 7 8" />
																<line x1="12" y1="3" x2="12" y2="15" />
															</svg>
															<span><?php esc_html_e( 'Upload', 'autonomous-ai-chatbot' ); ?></span>
														</button>
													</div>
													<div class="wc-cpt-err"></div>
												</div>
											</div>
										</div>
										<div class="options_group">
											<div class="wc-pos-form-field">
												<div class="wc-pos-input-label">
													<label for=""><?php esc_html_e( 'AI Logo', 'autonomous-ai-chatbot' ); ?> <b
															style="color:red"> *</b></label>
												</div>
												<div class="wc-pos-form-input-wrap img-wrap">
													<div class="wc-cpt-image">
														<input type="hidden" name="chatbot_ai_logo_path" id="chatbot_ai_logo_path"
															value="<?php echo esc_attr( get_option( 'chatbot_ai_logo_path' ) ); ?>">
														<img src="<?php echo esc_attr( $this->ai_logo ); ?>" alt="<?php esc_html_e( 'Chatbot AI Logo', 'autonomous-ai-chatbot' ); ?>"
															class="wc-chatbot-logo-img">
														<button type="button" id="chatbot_ai_logo"
															class="button-secondary btn-upload">
															<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
																viewBox="0 0 24 24" fill="none" stroke="currentColor"
																stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
																class="feather feather-upload">
																<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
																<polyline points="17 8 12 3 7 8" />
																<line x1="12" y1="3" x2="12" y2="15" />
															</svg>
															<span><?php esc_html_e( 'Upload', 'autonomous-ai-chatbot' ); ?></span>
														</button>
													</div>
													<div class="wc-cpt-err"></div>
												</div>
											</div>
										</div>
										<div class="options_group">
											<div class="wc-pos-form-field checkbox-field" style="flex-direction: row !important; align-items: center; gap: 10px !important;">
												<div class="wc-pos-form-input-wrap" style="width: auto;">
													<div class="checkbox-wrapper-19">
														<input type="checkbox" id="cpt_allow_voice_assistant"
															name="cpt_allow_voice_assistant" <?php echo esc_attr( $this->voice_assistance ) ? 'checked' : ''; ?> />
														<label for="cpt_allow_voice_assistant" class="check-box"></label>
													</div>
												</div>
												<div class="wc-pos-input-label">
													<label
														for="cpt_allow_voice_assistant" style="cursor: pointer;"><?php esc_html_e( 'Allow Voice Assistant', 'autonomous-ai-chatbot' ); ?></label>
												</div>
											</div>
										</div>
										<div class="options_group">
											<div class="wc-pos-form-field checkbox-field" style="flex-direction: row !important; align-items: center; gap: 10px !important;">
												<div class="wc-pos-form-input-wrap" style="width: auto;">
													<div class="checkbox-wrapper-19">
														<input type="checkbox" id="cpt_allow_animations" name="cpt_allow_animations"
															<?php echo esc_attr( $this->animation ) ? 'checked' : ''; ?> />
														<label for="cpt_allow_animations" class="check-box"></label>
													</div>
												</div>
												<div class="wc-pos-input-label">
													<label
														for="cpt_allow_animations" style="cursor: pointer;"><?php esc_html_e( 'Allow Animations', 'autonomous-ai-chatbot' ); ?></label>
												</div>
											</div>
										</div>
										<div class="options_group">
											<div class="wc-pos-form-field checkbox-field" style="flex-direction: row !important; align-items: center; gap: 10px !important;">
												<div class="wc-pos-form-input-wrap" style="width: auto;">
													<div class="checkbox-wrapper-19">
														<input type="checkbox" id="cpt_enable_guest_history"
															name="cpt_enable_guest_history" <?php echo esc_attr( $this->guest_history_enable ) ? 'checked' : ''; ?> />
														<label for="cpt_enable_guest_history" class="check-box"></label>
													</div>
												</div>
												<div class="wc-pos-input-label">
													<label
														for="cpt_enable_guest_history" style="cursor: pointer;"><?php esc_html_e( 'Enable Guest History', 'autonomous-ai-chatbot' ); ?></label>
												</div>
											</div>
										</div>
										<div class="options_group">
											<div class="wc-pos-form-field checkbox-field" style="flex-direction: row !important; align-items: center; gap: 10px !important;">
												<div class="wc-pos-form-input-wrap" style="width: auto;">
													<div class="checkbox-wrapper-19">
														<input type="checkbox" id="cpt_allow_admin_background"
															name="cpt_allow_admin_background" <?php echo esc_attr( $this->admin_background ) ? 'checked' : ''; ?> />
														<label for="cpt_allow_admin_background" class="check-box"></label>
													</div>
												</div>
												<div class="wc-pos-input-label">
													<label
														for="cpt_allow_admin_background" style="cursor: pointer;"><?php esc_html_e( 'Allow Background image in Conversation History', 'autonomous-ai-chatbot' ); ?></label>
												</div>
											</div>
										</div>
										<div class="options_group">
											<div class="wc-pos-form-field">
												<div class="wc-pos-input-label">
													<label
														for=""><?php esc_html_e( 'ChatBox Background Image', 'autonomous-ai-chatbot' ); ?>
														<b style="color:red"> *</b></label>
												</div>
												<div class="wc-pos-form-input-wrap img-wrap">
													<div class="wc-cpt-image">
														<input type="hidden" name="chatbot_bg_image_path" id="chatbot_bg_image_path"
															value="<?php echo esc_attr( get_option( 'chatbot_bg_image_path' ) ); ?>">
														<img src="<?php echo esc_attr( $this->chatbot_bg_image ); ?>"
															alt="Chatbot Background image" class="wc-chatbot-bg-image">
														<button type="button" id="chatbot_bg_image"
															class="button-secondary btn-upload">
															<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
																viewBox="0 0 24 24" fill="none" stroke="currentColor"
																stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
																class="feather feather-upload">
																<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
																<polyline points="17 8 12 3 7 8" />
																<line x1="12" y1="3" x2="12" y2="15" />
															</svg>
															<span><?php esc_html_e( 'Upload', 'autonomous-ai-chatbot' ); ?></span>
														</button>
													</div>
													<div class="wc-cpt-err"></div>
												</div>
											</div>
										</div>
									</div>
								</div>
								<div class="wc-pos-form-block-footer">
									<button type="submit"
										class="button-primary"><?php esc_html_e( 'Save Configurations', 'autonomous-ai-chatbot' ); ?></button>
								</div>
							</form>
						</div>
					</div>
				</div>
			</div>
			<?php
		}
	}
}
