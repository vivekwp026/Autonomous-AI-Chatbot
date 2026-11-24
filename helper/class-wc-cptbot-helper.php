<?php
/**
 * Admin hooks
 *
 * @package Chatgpt bot for WooCommerce
 * @version 1.0.0
 */

namespace WCAICHATBOT\Helper;

defined( 'ABSPATH' ) || exit();

use HelgeSverre\Toon\Toon;

if ( ! class_exists( 'Wc_Cptbot_Helper' ) ) {
	/**
	 * Admin hooks class.
	 */
	class Wc_Cptbot_Helper {
		/**
		 * The WordPress database object used for database operations.
		 *
		 * @var object $db Database object.
		 */
		protected $db;

		/**
		 * OpenAI instance.
		 *
		 * @var object $openai OpenAI instance.
		 */
		protected $openai;

		/**
		 * Class constructor.
		 * Initializes the WordPress database object.
		 */
		public function __construct() {
			global $wpdb;

			$this->db     = $wpdb;
			$this->openai = new Wc_Cptbot_Openai();
		}


		/**
		 * Retrieves WooCommerce products with optional limit and offset.
		 *
		 * @param  int $limit  The maximum number of products to retrieve. Default is 5.
		 * @param  int $offset The offset for pagination. Default is 0.
		 * @return array Array of retrieved products.
		 */
		public function wc_cptbot_get_all_woo_products( $limit = 6, $offset = 0 ) {

			$products = $this->db->get_results(
				$this->db->prepare(
					"SELECT posts.ID
					FROM `{$this->db->prefix}posts` AS posts
					INNER JOIN `{$this->db->prefix}term_relationships` AS term_relationships
						ON posts.ID = term_relationships.object_id
					INNER JOIN `{$this->db->prefix}term_taxonomy` AS term_taxonomy
						ON term_relationships.term_taxonomy_id = term_taxonomy.term_taxonomy_id
					INNER JOIN `{$this->db->prefix}terms` AS terms
						ON term_taxonomy.term_id = terms.term_id
					WHERE term_taxonomy.taxonomy = %s
					AND terms.slug IN ('variable', 'simple', 'external', 'grouped')
					AND posts.post_type = %s
					AND posts.post_status = %s
					ORDER BY posts.ID ASC
					LIMIT %d OFFSET %d",
					'product_type',
					'product',
					'publish',
					$limit,
					$offset
				),
				ARRAY_A
			);

			$products = apply_filters( 'wcaichatbot_modify_all_products', $products );

			return array(
				'products' => $products,
			);
		}



		/**
		 * Fetch the currency type from the database.
		 *
		 * @return string Currency code (e.g., USD, INR).
		 */
		public function wc_cptbot_get_currency_type() {
			$currency = $this->db->get_var(
				$this->db->prepare(
					"SELECT option_value FROM {$this->db->prefix}options WHERE option_name = %s",
					'woocommerce_currency'
				)
			);
			return $currency ? $currency : 'USD';
		}


		/**
		 * Retrieves the total count of WooCommerce products.
		 *
		 * @return array Array containing the total count of products.
		 */
		public function wc_cptbot_get_all_woo_products_count() {

			$total_products_count = $this->db->get_row(
				$this->db->prepare(
					"SELECT COUNT(posts.ID) as count
					FROM `{$this->db->prefix}posts` AS posts
					INNER JOIN `{$this->db->prefix}term_relationships` AS term_relationships ON posts.ID = term_relationships.object_id
					INNER JOIN `{$this->db->prefix}term_taxonomy` AS term_taxonomy ON term_relationships.term_taxonomy_id = term_taxonomy.term_taxonomy_id
					INNER JOIN `{$this->db->prefix}terms` AS terms ON term_taxonomy.term_id = terms.term_id
					WHERE term_taxonomy.taxonomy = '%s'
					AND terms.slug IN ('variable', 'simple', 'external', 'grouped')
					AND posts.post_type = '%s'
					AND posts.post_status='%s'",
					'product_type',
					'product',
					'publish'
				),
				ARRAY_A
			);

			$total_products_count = apply_filters( 'wcaichatbot_modify_products_count', $total_products_count );

			return array(
				'total_count' => $total_products_count,
			);
		}


		/**
		 * Retrieves product data for the specified search data.
		 *
		 * @param  array $search_data The search data to retrieve product data for.
		 * @return array Product data for the specified search data.
		 */
		public function wc_chatgpt_get_vertex_data( $search_data ) {
			$product_data = array();

			if ( ! empty( $search_data ) ) {
				switch ( $search_data[0] ) {
					case 'product':
						$product = wc_get_product( $search_data[1] );
						if ( $product ) {
							$product_data = $this->wc_cptbot_get_product_data_for_bot( $product );
						} else {
							$product_data = array();
						}
						break;
					default:
						$product_data = array();
						break;
				}
			}

			return $product_data;
		}


		/**
		 * Retrieves product data for the bot based on the given WooCommerce product.
		 *
		 * @param  WC_Product $product The WooCommerce product object.
		 * @return array Product data for the bot.
		 */
		public function wc_cptbot_get_product_data_for_bot( $product ) {
			$terms               = get_the_terms( (int) $product->get_id(), 'product_cat' );
			$categories          = array();
			$categories_link_arr = array();
			$categories_name     = '';
			$categories_link     = '';

			if ( ! empty( $terms ) ) {
				foreach ( $terms as $value ) {
					$link = esc_url( get_term_link( (int) $value->term_id, 'product_cat' ) );
					array_push( $categories_link_arr, $link );
					array_push( $categories, $value->name );
				}

				if ( ! empty( $categories ) ) {
					$categories_name = implode( ',', $categories );
					$categories_link = implode( ',', $categories_link_arr );
				}
			}

			$product_data_arr = $this->wc_cptbot_product_structure( $product, $categories_name, $categories_link );

			if ( 'variable' === $product->get_type() ) {
				$variations_data = $product->get_available_variations();
				$var_data        = array();
				$atr_values      = $this->wc_cptbot_display_product_attributes( $product );
				foreach ( $variations_data as $value ) {
					$variation = wc_get_product( (int) $value['variation_id'] );
					$price     = ! empty( $variation->get_price() ) ? wcaichatbot_get_currency_symbol() . $variation->get_price() : wcaichatbot_get_currency_symbol() . '0';
					foreach ( $variation->get_attributes() as $key => $value ) {
						$name = ! empty( $variation->get_attribute( $key ) ) ? $variation->get_name() . ' ,' . esc_html__( 'Price', 'autonomous-ai-chatbot' ) . ' ' . $price : $variation->get_name() . ', ' . $key . '' . ( isset( $atr_values[ $key ] ) ? $atr_values[ $key ] : '' );

					}
					$var_data[ $name ] = wp_json_encode( $this->wc_cptbot_product_structure_for_training( $variation, $categories_name, $categories_link, true, $atr_values ) );
				}
				$product_data_arr = array_merge( $var_data, $product_data_arr );

			}

			return apply_filters( 'wcaichatbot_modify_product_data_for_bot', $product_data_arr );
		}

		/**
		 * Generates the product data structure for the bot based on the given WooCommerce product.
		 *
		 * @param  WC_Product $product         The WooCommerce product object.
		 * @param  string     $categories_name The names of the categories associated with the product.
		 * @param  string     $categories_link The links of the categories associated with the product.
		 * @param  bool       $is_variation    Whether the product is a variation or not. Default is false.
		 * @param  array      $atr_values      contains attribute values.
		 * @return array The product data structure for the bot.
		 */
		public function wc_cptbot_product_structure( $product, $categories_name, $categories_link, $is_variation = false, $atr_values = array() ) {
			$product_data = array(
				'Type' => $product->get_type(),
				'Name' => $product->get_name(),
			);

			if ( 'variable' === $product->get_type() ) {
				$product_data['Lowest Price']  = ! empty( $product->get_variation_price( 'min', true ) ) ? wcaichatbot_get_currency_symbol() . $product->get_variation_price( 'min', true ) : '0';
				$product_data['Highest Price'] = ! empty( $product->get_variation_price( 'max', true ) ) ? wcaichatbot_get_currency_symbol() . $product->get_variation_price( 'max', true ) : '0';
				$product_data['price_numeric'] = ! empty( $product->get_variation_price( 'min', true ) ) ? floatval( $product->get_variation_price( 'min', true ) ) : 0.0;
			}

			$type = array( 'simple', 'variation' );
			if ( in_array( $product->get_type(), $type, true ) ) {
				$product_data['Quantity'] = ! empty( $product->get_stock_quantity() ) ? $product->get_stock_quantity() : ( 'instock' === $product->get_stock_status() ? 'Available' : 0 );
				$product_data['Price']    = ! empty( $product->get_price() ) ? $product->get_price() : '0';
				$product_data['price_numeric'] = ! empty( $product->get_price() ) ? floatval( $product->get_price() ) : 0.0;
			}
			if ( ! $is_variation ) {
				$product_data['ID']        = $product->get_id();
				$product_data['SKU']       = $product->get_sku();
				$product_data['Permalink'] = $product->get_permalink();
				$product_data['Stock']     = $product->get_stock_status();
				if ( ! empty( $categories_name ) ) {
					$product_data['Categories']     = $categories_name;
					$product_data['Categories url'] = $categories_link;
				}
			} else {
				$attributes = array();
				foreach ( $product->get_attributes() as $key => $value ) {
					if ( ! empty( $product->get_attribute( $key ) ) ) {
						$attributes[ wcaichatbot_attribute_label( $key ) ] = $product->get_attribute( $key );
						$product_data['Name']                     = $product->get_name();
					} else {
						$product_data['Name'] = $product->get_name() . ', ' . ( isset( $atr_values[ $key ] ) ? $atr_values[ $key ] : '' );
						$attributes[ wcaichatbot_attribute_label( $key ) ] = isset( $atr_values[ $key ] ) ? $atr_values[ $key ] : '';
					}
				}
				$product_data = array_merge( $product_data, $attributes );
			}
			return apply_filters( 'wcaichatbot_modify_product_structure', $product_data );
		}


	 /**
	 * Retrieves cleaned product data for embedding generation as ONE final string.
	 *
	 * @param  WC_Product $product The WooCommerce product object.
	 * @return string Final cleaned string for embedding.
	 */
	public function wc_cptbot_get_product_data_for_training( $product ) {

		// ------------------------
		// Get Product Categories
		// ------------------------
		$terms       = get_the_terms( $product->get_id(), 'product_cat' );
		$categories  = array();

		if ( ! empty( $terms ) ) {
			foreach ( $terms as $t ) {
				$categories[] = $t->name;
			}
		}

		$categories_clean = ! empty( $categories ) ? implode(', ', $categories) : '';

		// ------------------------
		// Get Product Attributes (simple product)
		// ------------------------
		$attr_clean_arr = array();
		$attributes     = $product->get_attributes();

		if ( ! empty( $attributes ) ) {
			foreach ( $attributes as $key => $attr ) {
				if ( $attr->is_taxonomy() ) {
					$terms = wp_get_post_terms( $product->get_id(), $attr->get_name(), array( 'fields' => 'names' ) );
					$attr_clean_arr[] = ucfirst( wcaichatbot_attribute_label( $attr->get_name() ) ) . ': ' . implode(', ', $terms);
				} else {
					$attr_clean_arr[] = ucfirst( wcaichatbot_attribute_label( $attr->get_name() ) ) . ': ' . implode(', ', $attr->get_options());
				}
			}
		}

		$attributes_clean = ! empty( $attr_clean_arr ) ? implode(' | ', $attr_clean_arr) : '';

		// ------------------------
		// Base Product Data (Simple)
		// ------------------------
		$final_text  = "Name: " . $product->get_name();
		$final_text .= "\nDescription: " . wp_strip_all_tags( $product->get_description() );
		$final_text .= "\nShort Description: " . wp_strip_all_tags( $product->get_short_description() );
		$final_text .= "\nCategories: " . $categories_clean;
		// $final_text .= "\nType: " . $product->get_type();

		if ( ! empty( $attributes_clean ) ) {
			$final_text .= "\nAttributes: " . $attributes_clean;
		}

		// ------------------------
		// VARIATION DATA (if product is variable)
		// ------------------------
		if ( $product->is_type( 'variable' ) ) {

			$final_text .= "\nVariations:";

			$variations = $product->get_available_variations();

			foreach ( $variations as $v ) {

				$variation = wc_get_product( $v['variation_id'] );
				$price     = $variation->get_price() ? $variation->get_price() : '0';

				$var_line = array();

				foreach ( $variation->get_attributes() as $key => $val ) {

					$clean_key   = ucfirst( wcaichatbot_attribute_label( $key ) );
					$clean_value = $variation->get_attribute( $key );

					if ( ! empty( $clean_value ) ) {
						$var_line[] = "$clean_key: $clean_value";
					}
				}

				$var_string = implode(' | ', $var_line);

				$final_text .= "\n- " . $var_string . " | Price: " . wcaichatbot_get_currency_symbol() . $price;
			}
		}

		// ------------------------
		// Return SINGLE CLEAN STRING
		// ------------------------
		return trim( $final_text );
	}


	/**
	 * Generates the product data structure for the bot based on the given WooCommerce product.
	 *
	 * @param  WC_Product $product         The WooCommerce product object.
	 * @param  string     $categories_name The names of the categories associated with the product.
	 * @param  string     $categories_link The links of the categories associated with the product.
	 * @param  bool       $is_variation    Whether the product is a variation or not. Default is false.
	 * @param  array      $atr_values      Contains attribute values.
	 * @return array The product data structure for the bot.
	 */
	public function wc_cptbot_product_structure_for_training( $product, $categories_name, $categories_link, $is_variation = false, $atr_values = array() ) {
		$product_data = array(
			'Name' => $product->get_name(),
			'Type' => $product->get_type(),
		);
		$attributes   = array();

		if ( 'variable' === $product->get_type() ) {
			$product_data['Lowest Price']  = ! empty( $product->get_variation_price( 'min', true ) ) ? wcaichatbot_get_currency_symbol() . $product->get_variation_price( 'min', true ) : wcaichatbot_get_currency_symbol() . '0';
			$product_data['Highest Price'] = ! empty( $product->get_variation_price( 'max', true ) ) ? wcaichatbot_get_currency_symbol() . $product->get_variation_price( 'max', true ) : wcaichatbot_get_currency_symbol() . '0';
		}
		$type = array( 'simple', 'variation' );
		if ( in_array( $product->get_type(), $type, true ) ) {
			$product_data['Quantity'] = ! empty( $product->get_stock_quantity() ) ? $product->get_stock_quantity() : ( 'instock' === $product->get_stock_status() ? 'Available' : 0 );
			$product_data['Price']    = ! empty( $product->get_price() ) ? wcaichatbot_get_currency_symbol() . $product->get_price() : wcaichatbot_get_currency_symbol() . '0';
		}

		if ( ! $is_variation ) {
			$product_data['SKU']   = $product->get_sku();
			$product_data['Stock'] = $product->get_stock_status();

			if ( ! empty( $categories_name ) ) {
				$product_data['Categories'] = $categories_name;

				$category_descriptions = $this->wc_cptbot_get_category_descriptions( $product );
				if ( ! empty( $category_descriptions ) ) {
					$product_data['Category Description'] = $category_descriptions;
				}
			}

			$short_desc = $this->wc_cptbot_get_product_short_description( $product, 300 );
			if ( ! empty( $short_desc ) ) {
				$product_data['Short Description'] = $short_desc;
			}

			$full_desc = $this->wc_cptbot_get_product_description( $product, 500 );
			if ( ! empty( $full_desc ) ) {
				$product_data['Description'] = $full_desc;
			}

			$product_tags = $this->wc_cptbot_get_product_tags( $product );
			if ( ! empty( $product_tags ) ) {
				$product_data['Tags'] = $product_tags;
			}
		} else {
			foreach ( $product->get_attributes() as $key => $value ) {
				$attributes[ wcaichatbot_attribute_label( $key ) ] = ! empty( $product->get_attribute( $key ) ) ? $product->get_attribute( $key ) : ( isset( $atr_values[ $key ] ) ? $atr_values[ $key ] : '' );
			}
			$product_data = array_merge( $product_data, $attributes );
		}

		return apply_filters( 'wcaichatbot_modify_product_structure', $product_data );
	}		/**
		 * Retrieves product data for the bot based on the given WooCommerce product.
		 *
		 * @param  WC_Product $product The WooCommerce product object.
		 * @return array Product data for the bot.
		 */
		public function wc_cptbot_get_product_data_for_description( $product ) {
			// Generate the product data array using the defined structure.
			$product_data_arr = $this->wc_cptbot_product_structure_for_description( $product );

			// Check if the product type is 'variable'.
			if ( 'variable' === $product->get_type() ) {
				$variations_data = $product->get_available_variations();
				$var_data        = array();

				// Retrieve data for each variation.
				foreach ( $variations_data as $value ) {
					$variation  = wc_get_product( (int) $value['variation_id'] );
					$var_data[] = $this->wc_cptbot_product_structure_for_description( $variation, true );
				}

				// Add the variations data to the product data array.
				$product_data_arr['variations'] = $var_data;
			}

			// Return the product data array.
			return apply_filters( 'wcaichatbot_modify_product_description', $product_data_arr );
		}


		/**
		 * Generates the product data structure for the bot based on the given WooCommerce product.
		 *
		 * @param  WC_Product $product      The WooCommerce product object.
		 * @param  bool       $is_variation Whether the product is a variation or not. Default is false.
		 * @return array The product data structure for the bot.
		 */
		public function wc_cptbot_product_structure_for_description( $product, $is_variation = false ) {
			$terms               = get_the_terms( (int) $product->get_id(), 'product_cat' );
			$categories          = array();
			$categories_link_arr = array();
			$categories_name     = '';

			if ( ! empty( $terms ) ) {
				foreach ( $terms as $value ) {
					$link = get_term_link( (int) $value->term_id, 'product_cat' );
					array_push( $categories_link_arr, $link );
					array_push( $categories, '<a target="_blank" href="' . $link . '">' . $value->name . '</a>' );
				}
				if ( ! empty( $categories ) ) {
					$categories_name = implode( ',', $categories );
				}
			}
			$product_data                     = array(
				'Name' => ! empty( $product->get_name() ) ? $product->get_name() : '',
				'type' => ! empty( $product->get_type() ) ? $product->get_type() : 'simple',
			);
			$product_data['product url link'] = $product->get_permalink();
			$product_data['categories']       = $categories_name;

			$type = array( 'simple', 'variation' );
			if ( in_array( $product->get_type(), $type, true ) ) {
				$product_data['stock quantity'] = ! empty( $product->get_stock_quantity() ) ? $product->get_stock_quantity() : 0;
				$product_data['price']          = ! empty( $product->get_regular_price() ) ? wcaichatbot_get_currency_symbol() . $product->get_regular_price() : 0;
				if ( ! empty( $product->get_sale_price() ) ) {
					$product_data['sale price'] = wcaichatbot_get_currency_symbol() . $product->get_sale_price();
				}
			}
			return apply_filters( 'wcaichatbot_modify_product_description_structure', $product_data );
		}

		/**
		 * Saves chat data to the chat history table.
		 *
		 * @param  array $data The chat data to be saved.
		 * @return int The ID of the inserted row in the chat history table.
		 */
		public function wc_cptbot_save_chat_data( $data ) {
			$table  = $this->db->prefix . 'cptbot_chat_history';
			$format = array( '%d', '%s', '%s', '%d', '%d' );

			$this->db->insert( $table, $data, $format );

			return $this->db->insert_id;
		}

		/**
		 * Update chat data in the 'cptbot_chat_history' table.
		 *
		 * @param array  $data          An associative array of data to update.
		 * @param array  $where         An associative array representing the WHERE clause for the update.
		 * @param array  $format        An array of formats for the data.
		 * @param array  $where_format  An array of formats for the WHERE clause.
		 */
		public function wc_cptbot_update_chat_data( $data, $where, $format, $where_format ) {
			$table = $this->db->prefix . 'cptbot_chat_history';

			$this->db->update( $table, $data, $where, $format, $where_format );
		}


		/**
		 * Retrieves user data based on the given query.
		 *
		 * @param  array $post The query data.
		 * @return mixed The result of checking the history existence for the retrieved users.
		 */
		public function wc_cptbot_get_user( $post ) {
			$users = $this->db->get_results( $this->db->remove_placeholder_escape( $this->db->prepare( "SELECT * FROM `{$this->db->prefix}users` WHERE user_nicename LIKE %s", '%' . $post['query'] . '%' ) ), ARRAY_A );

			$users = $this->wc_cptbot_check_history_exist( $users );

			// Check if guest history exists and matches the query
			$guest_label = esc_html__( 'Guest', 'autonomous-ai-chatbot' );
			if ( empty( $post['query'] ) || stripos( $guest_label, $post['query'] ) !== false ) {
				$guest_history_exist = $this->db->get_var( "SELECT COUNT(id) FROM `{$this->db->prefix}cptbot_chat_history` WHERE user_id = 0" );
				if ( $guest_history_exist > 0 ) {
					$users[] = array(
						'ID'            => 'guest',
						'user_nicename' => $guest_label,
						'display_name'  => $guest_label,
						'exist'         => true,
					);
				}
			}

			return $users;
		}


		/**
		 * Checks the history existence for the given users.
		 *
		 * @param  array $users The array of users to check.
		 * @return array An array of users with the 'exist' flag indicating their history existence.
		 */
		public function wc_cptbot_check_history_exist( $users ) {
			return array_map(
				function ( $user ) {
					$data_exist    = $this->db->get_row( $this->db->prepare( "SELECT COUNT(id) as count FROM `{$this->db->prefix}cptbot_chat_history` WHERE user_id = %d", $user['ID'] ), ARRAY_A );
					$user['exist'] = false;

					if ( 0 < (int) $data_exist['count'] ) {
						$user['exist'] = true;
					}

					return $user;
				},
				$users
			);
		}

		/**
		 * Retrieves user chat data based on the given parameters.
		 *
		 * @param  array $post The parameters for retrieving user chat data.
		 * @return array|null The retrieved user chat data as an array of associative arrays, or null if no data is found.
		 */
		public function wc_cptbot_get_user_chat( $post ) {
			$user_id = $post['cptbot_users_list'];
			if ( 'guest' === $user_id ) {
				$user_id = 0;
			}
			$user_chat = $this->db->get_results(
				$this->db->prepare(
					"SELECT `message_type`, `message`, `created_at`
					FROM `{$this->db->prefix}cptbot_chat_history`
					WHERE user_id = %d
					AND created_at BETWEEN %s AND %s
					AND chat_status = %d",
					(int) $user_id,
					$post['wc-cptbot-from'] . ' 00:00:00',
					$post['wc-cptbot-to'] . ' 23:59:59',
					1
				),
				ARRAY_A
			);

			return apply_filters( 'wcaichatbot_modify_user_chat', $user_chat );
		}


		/**
		 * Retrieves chat data from the database based on specified criteria.
		 *
		 * @param  array $post The post data containing the number of days and user type.
		 * @return array|null The chat data retrieved from the database.
		 */
		public function wc_cptbot_get_chat_data( $post ) {
			$chat_data = $this->db->get_results(
				$this->db->prepare(
					"SELECT DATE(created_at) AS entry_date, COUNT(*) AS entry_count FROM {$this->db->prefix}cptbot_chat_history WHERE `created_at` >= CURDATE() - INTERVAL %s DAY AND message_type=%s GROUP BY DATE(created_at)",
					$post['days'],
					$post['user_type']
				),
				ARRAY_A
			);
			return apply_filters( 'wcaichatbot_modify_user_chat', $chat_data );
		}

		/**
		 * Retrieves visited user data from the database based on specified criteria.
		 *
		 * @param  array $post The post data containing the user type.
		 * @return array|null The visited user data retrieved from the database.
		 */
		public function wc_cptbot_get_visited_user( $post ) {
			$visited_users = $this->db->get_results(
				$this->db->prepare(
					"SELECT user_id, COUNT(*) AS entry_count FROM {$this->db->prefix}cptbot_chat_history WHERE `created_at` BETWEEN %s AND %s AND chat_status = %d AND message_type=%s GROUP BY user_id",
					wp_date( 'Y-m-d' ) . ' 00:00:00',
					wp_date( 'Y-m-d' ) . ' 23:59:59',
					1,
					$post['user_type']
				),
				ARRAY_A
			);
			return apply_filters( 'wcaichatbot_modify_visited_users', $visited_users );
		}

	/**
	 * Check if a product already exists in scheduled actions table.
	 *
	 * @param  int    $product_id  The product ID to check.
	 * @param  string $action_type The action type to check. Default 'products'.
	 * @return int The action ID if exists, 0 otherwise.
	 */
	public function wc_cptbot_product_exists_in_actions( $product_id, $action_type = 'products' ) {
		$table = $this->db->prefix . 'cptbot_scheduled_actions';

		// Get all scheduled actions.
		$actions = $this->db->get_results(
			$this->db->prepare( "SELECT `id`, `schedule_products_id` FROM {$table} WHERE `action_type`=%s", $action_type ),
			ARRAY_A
		);

		if ( ! empty( $actions ) ) {
			foreach ( $actions as $action ) {
				$products = maybe_unserialize( $action['schedule_products_id'] );

				// Check if products is an array and if product exists.
				if ( is_array( $products ) ) {
					foreach ( $products as $product ) {
						$pid = is_array( $product ) ? ( isset( $product['ID'] ) ? (int) $product['ID'] : 0 ) : (int) $product;
						if ( $pid === (int) $product_id ) {
							return (int) $action['id'];
						}
					}
				}
			}
		}

		return 0;
	}

	/**
	 * Deletes scheduled actions associated with a specific product ID.
	 *
	 * @param int $product_id The ID of the product.
	 * @return void
	 */
	public function wc_cptbot_delete_scheduled_action_by_product_id( $product_id ) {
		$action_id = $this->wc_cptbot_product_exists_in_actions( $product_id );
		if ( $action_id > 0 ) {
			$table = $this->db->prefix . 'cptbot_scheduled_actions';
			$this->db->delete( $table, array( 'id' => $action_id ), array( '%d' ) );
		}
	}



	/**
	 * Saves scheduled actions into the database based on the provided products and status.
	 * Prevents duplicate entries by checking if products already exist.
	 *
	 * @param  array  $products    The products to be scheduled for action.
	 * @param  array  $status      The status of the scheduled action.
	 * @param  string $action_type The action type ('products' or 'posts_pages'). Default 'products'.
	 * @return int The insert ID of the new record or existing action ID.
	 */
	public function wc_cptbot_save_scheduled_actions( $products, $status, $action_type = 'products' ) {
		$table  = $this->db->prefix . 'cptbot_scheduled_actions';
		$action_status = ( 'success' === $status['status'] ) ? 1 : 0;

		// Handle single product ID or array of products.
		$product_array = array();
		if ( is_array( $products ) ) {
			foreach ( $products as $product ) {
				$product_data = array();
				if ( is_array( $product ) ) {
					// Product is array with 'ID' key
					if ( isset( $product['ID'] ) ) {
						$product_data = $product;
					}
				} else {
					// Product is just an ID
					$product_data = array( 'ID' => (int) $product );
				}

				if ( ! empty( $product_data ) && isset( $product_data['ID'] ) && $product_data['ID'] > 0 ) {
					$product_array[] = $product_data;
				}
			}
		} else {
			// Single product ID
			$product_array[] = array( 'ID' => (int) $products );
		}

		// Create separate action IDs for each product (just like ONNX)
		// This allows proper tracking in the action scheduler table
		$inserted_action_ids = array();

		foreach ( $product_array as $single_product ) {
			$product_id = isset( $single_product['ID'] ) ? (int) $single_product['ID'] : 0;

			if ( $product_id <= 0 ) {
				continue;
			}

			// Check if this product already exists in the scheduled actions table
			$existing_action_id = $this->wc_cptbot_product_exists_in_actions( $product_id, $action_type );

			if ( $existing_action_id > 0 ) {
				// Check if this action has multiple products (legacy data)
				$existing_products_raw = $this->wc_cptbot_get_product_ids( $existing_action_id );
				$existing_products     = maybe_unserialize( $existing_products_raw );

				$p_count = is_array( $existing_products ) ? count( $existing_products ) : 0;

				if ( is_array( $existing_products ) && count( $existing_products ) > 1 ) {
					// It's a grouped action. Remove current product from it so we can create a separate action.
					$new_group_products = array();
					foreach ( $existing_products as $ep ) {
						$ep_id = is_array( $ep ) ? ( isset( $ep['ID'] ) ? (int) $ep['ID'] : 0 ) : (int) $ep;
						if ( $ep_id !== $product_id ) {
							$new_group_products[] = $ep;
						}
					}

					// Update the old action with removed product
					$this->db->update(
						$table,
						array( 'schedule_products_id' => maybe_serialize( $new_group_products ) ),
						array( 'id' => $existing_action_id ),
						array( '%s' ),
						array( '%d' )
					);

					// Reset existing_action_id so we create a new one for this product.
					$existing_action_id = 0;
				}
			}

			if ( $existing_action_id > 0 ) {
				// Product already exists, update its status instead of creating duplicate.
				$this->wc_cptbot_update_action_scheduler_status( $existing_action_id, $action_status );
				$inserted_action_ids[] = $existing_action_id;
				continue;
			}

			$data   = array(
				'schedule_products_id' => maybe_serialize( array( $single_product ) ),
				'action_type'          => $action_type,
				'action_status'        => $action_status,
			);
			$format = array( '%s', '%s', '%d' );
			$this->db->insert( $table, $data, $format );
			$inserted_action_ids[] = $this->db->insert_id;
		}

		// Return the first inserted action ID (or last one if updating existing)
		return ! empty( $inserted_action_ids ) ? end( $inserted_action_ids ) : 0;
	}

	/**
	 * Clean up scheduled actions table to ensure post/page entries have action_type = 'posts_pages'.
	 */
	public function wc_cptbot_cleanup_scheduled_actions_types() {
		$table = $this->db->prefix . 'cptbot_scheduled_actions';
		$actions = $this->db->get_results(
			$this->db->prepare( "SELECT `id`, `schedule_products_id` FROM {$table} WHERE `action_type`=%s", 'products' ),
			ARRAY_A
		);
		if ( ! empty( $actions ) ) {
			foreach ( $actions as $action ) {
				$prods = maybe_unserialize( $action['schedule_products_id'] );
				if ( is_array( $prods ) ) {
					foreach ( $prods as $p ) {
						$pid = is_array( $p ) ? ( isset( $p['ID'] ) ? (int) $p['ID'] : 0 ) : (int) $p;
						if ( $pid > 0 ) {
							$post_type = get_post_type( $pid );
							if ( in_array( $post_type, array( 'post', 'page' ), true ) ) {
								$this->db->update(
									$table,
									array( 'action_type' => 'posts_pages' ),
									array( 'id' => (int) $action['id'] ),
									array( '%s' ),
									array( '%d' )
								);
							}
						}
					}
				}
			}
		}
	}


		/**
		 * Get scheduled actions data from the database.
		 *
		 * @param string $action_type     Type of action to retrieve.
		 * @param string $selected_status Selected status of the action. Optional.
		 * @param int    $offset          Number of items to skip. Optional.
		 * @param int    $limit           Number of items to return. Optional.
		 *
		 * @return array|null Returns an array of scheduled actions data, or null if none found.
		 */
		public function wc_cptbot_get_action_scheduler_data( $action_type, $selected_status = '', $offset = 0, $limit = 5 ) {
			// Check if a status is selected. If so, filter by status, otherwise get all actions.
			if ( ! empty( $selected_status ) ) {
				// Convert the selected status to a boolean.
				$status = ( 'failed' === $selected_status ) ? 0 : 1;
				// Query the database for scheduled actions that match the type and status.
				$data = $this->db->get_results(
					$this->db->prepare( "SELECT * FROM {$this->db->prefix}cptbot_scheduled_actions WHERE `action_type`=%s AND `action_status`=%d LIMIT %d OFFSET %d", $action_type, (int) $status, $limit, $offset ),
					ARRAY_A
				);
			} else {
				// If no status is selected, get all scheduled actions that match the type.
				$data = $this->db->get_results(
					$this->db->prepare( "SELECT * FROM {$this->db->prefix}cptbot_scheduled_actions WHERE `action_type`=%s LIMIT %d OFFSET %d", $action_type, $limit, $offset ),
					ARRAY_A
				);
			}
			// Return the scheduled actions data.
			return $data;
		}

		/**
		 * Retrieves the count of action scheduler data based on the selected status and action type.
		 *
		 * @param  string $selected_status The selected status of the action.
		 * @param  string $action_type     The type of action.
		 * @return int The count of action scheduler data.
		 */
		public function wc_cptbot_get_action_scheduler_data_count( $selected_status, $action_type ) {
			if ( ! empty( $selected_status ) ) {
				// Determine the status value based on the selected status.
				$status = ( 'failed' === $selected_status ) ? 0 : 1;
				// Get the total count of items with the specified action type and status.
				$total_items = $this->db->get_row(
					$this->db->prepare( "SELECT COUNT(*) as count FROM {$this->db->prefix}cptbot_scheduled_actions WHERE `action_type`=%s AND `action_status`=%d", $action_type, $status ),
					ARRAY_A
				);
			} else {
				// Get the total count of items with the specified action type.
				$total_items = $this->db->get_row(
					$this->db->prepare( "SELECT COUNT(*) as count FROM {$this->db->prefix}cptbot_scheduled_actions WHERE `action_type`=%s", $action_type ),
					ARRAY_A
				);
			}
			// Return the count of total items.
			return $total_items['count'];
		}

		/**
		 * Retrieves the product IDs associated with a specific action ID.
		 *
		 * @param  int $action_id The ID of the action.
		 * @return array The array of product IDs.
		 */
		public function wc_cptbot_get_product_ids( $action_id ) {
			$data        = $this->db->get_row(
				$this->db->prepare( "SELECT `schedule_products_id` FROM {$this->db->prefix}cptbot_scheduled_actions WHERE `id`=%d", $action_id ),
				ARRAY_A
			);
			$product_arr = ! empty( $data['schedule_products_id'] ) ? $data['schedule_products_id'] : array();
			return $product_arr;
		}

		/**
		 * Retrieves the chat history for the current user within a specified time range.
		 *
		 * @param  int $count count of chats.
		 * @return array The chat history data retrieved from the database.
		 */
		public function wc_chatgpt_get_chat_history( $count ) {
			$user_id = get_current_user_id();
			if ( 0 === $user_id ) {
				return array();
			}
			$chat_history = $this->db->get_results(
				$this->db->prepare(
				// SQL query to select chat history data based on specified criteria.
					"SELECT `message` FROM {$this->db->prefix}cptbot_chat_history WHERE `created_at` BETWEEN %s AND %s AND chat_status = %d AND user_id=%s AND message_type=%s ORDER BY created_at DESC LIMIT %d OFFSET %d",
					wp_date( 'Y-m-d' ) . ' 00:00:00', // Start of the current date.
					wp_date( 'Y-m-d' ) . ' 23:59:59', // End of the current date.
					1, // Chat status (e.g., 1 for active).
					$user_id, // Current user ID.
					'user',
					$count, // Limit number of results to 5.
					1 // Offset for pagination (starting from 0).
				),
				ARRAY_A // Return results as associative array.
			);
			return apply_filters( 'wcaichatbot_modify_chat_history', $chat_history );
		}

		/**
		 * Converts links in the provided text to HTML anchor tags.
		 *
		 * @param  string $text The text to be converted.
		 * @return string The converted text with anchor tags.
		 */
		public function convert_link_to_anchor( $text ) {
			$linkregex      = '/(https?:\/\/[^\s`]+)(?<![`.,!?;:])/'; // Regular expression to match URLs, excluding trailing punctuation and backticks.
			$anchor_tag     = '<a target="_blank" href="$1">$1</a>'; // HTML anchor tag template with target="_blank" to open links in a new tab/window.
			$converted_text = preg_replace( $linkregex, $anchor_tag, $text ); // Replace URLs with anchor tags.
			return $converted_text; // Return the converted text with anchor tags.
		}

		/**
		 * Retrieves the count of untrained products from the database.
		 *
		 * @return array The count of untrained products.
		 */
		public function wc_cptbot_get_untrained_products() {
			$untrained_products = $this->db->get_results(
				$this->db->prepare(
				// SQL query to select the count of untrained products based on meta key and value.
					"SELECT COUNT(*) FROM {$this->db->prefix}postmeta WHERE `meta_key`=%s AND `meta_value`=%s",
					'cptbot_product_data', // Meta key for identifying the product data.
					false // Meta value indicating untrained products.
				),
				ARRAY_A // Return results as an associative array.
			);

			return apply_filters( 'wcaichatbot_modify_untrained_products', $untrained_products );
		}

		/**
		 * Updates the action scheduler status for a specific action ID.
		 * When status is set to 1 (success), also ensures embeddings are created and saved.
		 *
		 * @param  int    $action_id The ID of the action to update.
		 * @param  int    $status The scheduler status (0 = failed, 1 = success).
		 * @param  bool   $create_embeddings Whether to create embeddings when updating to success status. Default true.
		 * @return bool|int|false The result of the update operation. False on failure, the number of rows updated on success.
		 */
		public function wc_cptbot_update_action_scheduler_status( $action_id, $status, $create_embeddings = true ) {
			$action_id = ! empty( $action_id ) ? intval( $action_id ) : 0;

			if ( $action_id === 0 ) {
				return $this->db->query(
					$this->db->prepare(
						"UPDATE {$this->db->prefix}cptbot_scheduled_actions SET action_status = %d",
						$status
					)
				);
			}

			$data         = array( 'action_status' => $status ); // Data to update the action_status field to 1 (assuming 1 represents the desired status).
			$format       = array( '%d' );  // Format for the data. Ignored when corresponding data is NULL, set to NULL for readability.
			$where        = array( 'id' => (int) $action_id ); // WHERE clause to identify the specific action ID to update. Assumes the ID is an integer.
			$where_format = array( '%d' );  // Format for the WHERE clause data. Ignored when corresponding WHERE data is NULL, set to NULL for readability.

			$result = $this->db->update( $this->db->prefix . 'cptbot_scheduled_actions', $data, $where, $format, $where_format );

			// If status is being updated to 1 (success) and create_embeddings is enabled,
			// ensure embeddings are created for any untrained products in this action
			if ( 1 === intval( $status ) && $create_embeddings && $result ) {
				$this->wc_cptbot_ensure_embeddings_created( $action_id );
			}

			return $result;
		}

		/**
		 * Ensures embeddings are created and saved for products in a scheduled action.
		 * This is called when action status is being updated to success.
		 * IMPORTANT: Only verifies metadata existence, NOT whether embeddings actually exist in vector DB!
		 *
		 * @param  int $action_id The ID of the action.
		 * @return bool True if all products have metadata, false if any are untrained.
		 */
		public function wc_cptbot_ensure_embeddings_created( $action_id ) {
			// Get product IDs from the action
			$product_ids_data = $this->wc_cptbot_get_product_ids( $action_id );

			if ( empty( $product_ids_data ) ) {
				return false;
			}

			$product_ids = maybe_unserialize( $product_ids_data );
			if ( ! is_array( $product_ids ) ) {
				return false;
			}

			$untrained_products = array();
			$trained_products = array();

			foreach ( $product_ids as $product_data ) {
				$product_id = is_array( $product_data ) && isset( $product_data['ID'] ) ? intval( $product_data['ID'] ) : intval( $product_data );

				if ( $product_id <= 0 ) {
					continue;
				}

				// Check if product already has embeddings metadata
				$is_trained = get_post_meta( $product_id, 'cptbot_product_data', true );

				if ( empty( $is_trained ) ) {
					// Product doesn't have metadata
					$untrained_products[] = $product_id;
				} else {
					$trained_products[] = $product_id;
				}
			}

			$summary = "Action $action_id Summary: " . count( $trained_products ) . " with metadata, " . count( $untrained_products ) . " without";

			if ( ! empty( $untrained_products ) ) {
				return false;
			}

			return true;
		}

	/**
	 * Display product attributes for a WooCommerce product.
		 *
		 * @param  WC_Product $product The product object.
		 * @return array Array of attributes to display; label, value.
		 */
		public function wc_cptbot_display_product_attributes( $product ) {
			$product_attributes = array();

			// Add product attributes to the list.
			$attributes = array_filter( $product->get_attributes(), 'wc_attributes_array_filter_visible' );

			// Loop through each attribute and process its values.
			foreach ( $attributes as $attribute ) {
				$values = array();

				if ( $attribute->is_taxonomy() ) {
					// For taxonomy attributes, get the values from the product and build links if the attribute is public.
					$attribute_taxonomy = $attribute->get_taxonomy_object();
					$attribute_values   = wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'all' ) );

					foreach ( $attribute_values as $attribute_value ) {
						$value_name = esc_html( $attribute_value->name );

						if ( $attribute_taxonomy->attribute_public ) {
							$values[] = '<a href="' . esc_url( get_term_link( $attribute_value->term_id, $attribute->get_name() ) ) . '" rel="tag">' . $value_name . '</a>';
						} else {
							$values[] = $value_name;
						}
					}
				} else {
					// For non-taxonomy attributes, get the options and make them clickable.
					$values = $attribute->get_options();

					foreach ( $values as &$value ) {
						$value = make_clickable( esc_html( $value ) );
					}
				}

				// Store the processed values in the product_attributes array with the attribute name as the key.
				$product_attributes[ $attribute->get_name() ] = implode( ', ', $values );
			}

			return $product_attributes;
		}


	/**
	 * Get data from the OpenAI language model based on the selected model and user prompt.
	 *
	 * @param  string $model    The selected OpenAI model.
	 * @param  string $prompt   The user input prompt for the language model.
	 * @param  array  $formdata The form data containing configuration for the language model.
	 * @return array Response data from the OpenAI chat API.
	 */
	public function wc_cptbot_getdata_from_model( $model, $prompt, $formdata ) {
		// Prepare the data request using OpenAI SDK compatible format.
		// The model parameter is now specified directly in the request.
		$wk_ai_data_request = array(
			'model'             => $model,
			'messages'          => array(
				array(
					'role'    => 'user',
					'content' => $prompt,
				),
			),
			'temperature'       => floatval( $formdata['open_ai_temp'] ),
			'max_tokens'        => intval( $formdata['open_ai_token'] ),
			'top_p'             => floatval( $formdata['open_ai_top'] ),
		);

		// Make the API call to the OpenAI chat endpoint to get the response data.
		$chat_data = $this->openai->chat( $wk_ai_data_request );

		return $chat_data;
	}
	/**
	 * Get speech data from the OpenAI language model using the configured model.
	 *
	 * @param  string $prompt The user input prompt for the language model.
	 * @return array Response data from the OpenAI chat API.
	 */
	public function wc_cptbot_get_speech_data_from_model( $prompt ) {
		// Prepare the data request for the configured model using 'messages' format for chat-based conversation.
		$wk_ai_data_request = array(
			'model'             => get_option( 'wc_cptbot_model_name', 'gpt-3.5-turbo' ),
			'messages'          => array(
				array(
					'role'    => 'user',
					'content' => $prompt,
				),
			),
			'temperature'       => floatval( 0.7 ),     // Controls the randomness of the generated response.
			'max_tokens'        => intval( 1000 ),     // The maximum number of tokens in the response.
			'top_p'             => floatval( 0.01 ),   // Top-p (nucleus) sampling probability for diversity.
		);

		// Make the API call to the OpenAI chat endpoint to get the response data.
		return $this->openai->chat( $wk_ai_data_request );
	}

	/**
	 * Classify the current user intent using TypeSafe Jev.
	 *
	 * @param string $client_query    Current user query.
	 * @param array  $history_content Chat history.
	 * @return array Empty array when Jev is not configured or confidence is too low.
	 */
	public function wc_cptbot_get_intent_from_jev( $client_query, $history_content ) {
		$api_key = get_option( 'wc_cptbot_typesafe_api_key', '' );

		if ( empty( $api_key ) || empty( $client_query ) ) {
			return array();
		}

		$history_data = ! empty( $history_content ) ? Toon::encode( $history_content ) : '';
		$state        = array(
			'user_query'      => wp_strip_all_tags( $client_query ),
			'recent_history'  => $history_data,
			'store_context'   => 'WooCommerce shopping assistant. Classify the customer intent for routing only.',
		);

		$payload = array(
			'state'     => $state,
			'model'     => get_option( 'wc_cptbot_typesafe_model', 'jev-latest' ),
			'questions' => array(
				'intent'                 => array(
					'type'         => 'choice',
					'instructions' => 'Choose the single best routing intent for this WooCommerce chatbot message.',
					'criteria'     => array(
						'product_search'       => 'User wants to find, search, compare, recommend, or ask details about products that may require product data.',
						'product_followup'     => 'User asks a follow-up about products already shown in recent chat history.',
						'category_discovery'   => 'User asks for categories, departments, collections, or product types.',
						'coupon_lookup'        => 'User asks about available coupons, promo codes, offers, deals, or discounts.',
						'coupon_apply'         => 'User wants to apply or use a specific coupon code.',
						'shipping'             => 'User asks about shipping, delivery options, shipping methods, or delivery availability.',
						'add_to_cart'          => 'User wants to add a product to the cart.',
						'remove_from_cart'     => 'User wants to remove a product from the cart.',
						'view_cart'            => 'User wants to see or inspect their cart.',
						'checkout'             => 'User wants to checkout, place an order, or provide order details.',
						'sku_search'           => 'User asks to find a product by SKU or gives a SKU-like product identifier.',
						'price_low_to_high'    => 'User asks for cheapest, lowest priced, budget, inexpensive, or low-to-high products.',
						'price_high_to_low'    => 'User asks for expensive, premium, highest priced, or high-to-low products.',
						'casual'               => 'Greeting, thanks, goodbye, or small talk with no store action needed.',
						'other'                => 'Anything else that can be answered by normal chat reasoning.',
					),
				),
				'needs_new_product_data' => array(
					'type'         => 'noul',
					'instructions' => 'Does the user need product data that is not already present in recent_history?',
					'criteria'     => array(
						'true'  => 'Needs retrieval of product, price, stock, variant, or catalog data.',
						'false' => 'Can be answered from chat history or general conversation without retrieving new product documents.',
					),
				),
			),
		);

		$result = $this->wc_cptbot_typesafe_systemone_request( $payload, $api_key );

		if ( empty( $result['answers']['intent']['choice'] ) ) {
			return array();
		}

		$intent_answer = $result['answers']['intent'];
		$threshold     = (float) get_option( 'wc_cptbot_typesafe_confidence_threshold', 0.5 );
		$confidence    = isset( $intent_answer['confidence'] ) ? (float) $intent_answer['confidence'] : 0;

		if ( $confidence < $threshold ) {
			return array();
		}

		return $this->wc_cptbot_map_jev_intent_to_chat_data(
			$intent_answer['choice'],
			$result['answers'],
			$client_query,
			$history_content,
			$confidence
		);
	}

	/**
	 * Send a System One request to TypeSafe.
	 *
	 * @param array  $payload Request payload.
	 * @param string $api_key TypeSafe API key.
	 * @return array Decoded response.
	 */
	private function wc_cptbot_typesafe_systemone_request( $payload, $api_key ) {
		$response = wp_remote_post(
			'https://api.typesafe.ai/v1/systemone',
			array(
				'headers'     => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'timeout'     => 15,
				'redirection' => 5,
				'body'        => wp_json_encode( $payload ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( 200 !== $response_code ) {
			return array();
		}

		$result = json_decode( $response_body, true );

		return is_array( $result ) ? $result : array();
	}

	/**
	 * Map a Jev typed intent to the existing main-agent response contract.
	 *
	 * @param string $intent          Jev intent choice.
	 * @param array  $answers         All Jev answers.
	 * @param string $client_query    Current user query.
	 * @param array  $history_content Chat history.
	 * @param float  $confidence      Jev confidence.
	 * @return array Existing chat data shape.
	 */
	private function wc_cptbot_map_jev_intent_to_chat_data( $intent, $answers, $client_query, $history_content, $confidence ) {
		$chat_data = $this->wc_cptbot_default_intent_chat_data();
		$chat_data['_intent_source']     = 'jev';
		$chat_data['_intent']            = $intent;
		$chat_data['_intent_confidence'] = $confidence;

		$needs_new_product_data = isset( $answers['needs_new_product_data']['noul'] ) ? (float) $answers['needs_new_product_data']['noul'] >= 0.5 : false;
		$sku                   = $this->wc_cptbot_extract_sku_from_query( $client_query );
		$coupon_code           = $this->wc_cptbot_extract_coupon_code_from_query( $client_query );
		$quantity              = $this->wc_cptbot_extract_quantity_from_query( $client_query );
		$price_filter          = $this->wc_cptbot_extract_price_filter_from_query( $client_query );

		switch ( $intent ) {
			case 'category_discovery':
				$chat_data['get_categories'] = true;
				break;

			case 'coupon_lookup':
				$chat_data['coupon'] = true;
				break;

			case 'coupon_apply':
				$chat_data['apply_coupon'] = true;
				$chat_data['coupon_code']  = $coupon_code;
				break;

			case 'shipping':
				$chat_data['shipping'] = true;
				break;

			case 'view_cart':
				$chat_data['view_cart'] = true;
				break;

			case 'checkout':
				$chat_data['checkout'] = true;
				break;

			case 'add_to_cart':
				$cart_data = $this->wc_cptbot_extract_cart_reference_from_history( $history_content, $client_query );
				$chat_data = array_merge( $chat_data, $cart_data );
				$chat_data['add_cart'] = ! empty( $cart_data['sku'] ) || ! empty( $cart_data['product_id'] ) || ! empty( $cart_data['variation_id'] );
				if ( ! empty( $quantity ) ) {
					$chat_data['quantity'] = $quantity;
				}
				if ( empty( $chat_data['quantity'] ) ) {
					$chat_data['add_cart'] = false;
					$chat_data['answer']   = esc_html__( 'How many units would you like to add to your cart?', 'autonomous-ai-chatbot' );
				} elseif ( ! $chat_data['add_cart'] ) {
					$chat_data['rag_doc_required'] = true;
					$chat_data['doc_query']        = $client_query;
				}
				break;

			case 'remove_from_cart':
				$cart_data = $this->wc_cptbot_extract_cart_reference_from_history( $history_content, $client_query );
				$chat_data = array_merge( $chat_data, $cart_data );
				$chat_data['remove_cart'] = ! empty( $cart_data['sku'] ) || ! empty( $cart_data['product_id'] ) || ! empty( $cart_data['variation_id'] );
				if ( ! empty( $quantity ) ) {
					$chat_data['quantity'] = $quantity;
				}
				if ( empty( $chat_data['quantity'] ) ) {
					$chat_data['remove_cart'] = false;
					$chat_data['answer']      = esc_html__( 'How many units would you like to remove from your cart?', 'autonomous-ai-chatbot' );
				} elseif ( ! $chat_data['remove_cart'] ) {
					$chat_data['rag_doc_required'] = true;
					$chat_data['doc_query']        = $client_query;
				}
				break;

			case 'sku_search':
				$chat_data['sku'] = $sku;
				if ( empty( $sku ) ) {
					$chat_data['rag_doc_required'] = true;
					$chat_data['doc_query']        = $client_query;
				}
				break;

			case 'price_low_to_high':
				$chat_data['quality_type'] = 'min';
				break;

			case 'price_high_to_low':
				$chat_data['quality_type'] = 'max';
				break;

			case 'casual':
				$chat_data['answer'] = $this->wc_cptbot_get_casual_intent_answer( $client_query );
				break;

			case 'product_followup':
				$chat_data['rag_doc_required'] = $needs_new_product_data;
				$chat_data['doc_query']        = $needs_new_product_data ? $client_query : '';
				break;

			case 'product_search':
				$chat_data['rag_doc_required'] = true;
				$chat_data['doc_query']        = $client_query;
				break;

			case 'other':
			default:
				$chat_data['rag_doc_required'] = false;
				break;
		}

		if ( ! empty( $price_filter ) && ! in_array( $intent, array( 'price_low_to_high', 'price_high_to_low' ), true ) ) {
			$chat_data['chromadb_where']   = $price_filter;
			$chat_data['rag_doc_required'] = true;
			$chat_data['doc_query']        = ! empty( $chat_data['doc_query'] ) ? $chat_data['doc_query'] : $client_query;
		}

		return $chat_data;
	}

	/**
	 * Existing main-agent response defaults.
	 *
	 * @return array Defaults.
	 */
	private function wc_cptbot_default_intent_chat_data() {
		return array(
			'rag_doc_required' => false,
			'doc_query'        => '',
			'chromadb_where'   => new \stdClass(),
			'get_categories'   => false,
			'coupon'           => false,
			'apply_coupon'     => false,
			'coupon_code'      => '',
			'add_cart'         => false,
			'remove_cart'      => false,
			'view_cart'        => false,
			'checkout'         => false,
			'shipping'         => false,
			'quality_type'     => '',
			'sku'              => '',
			'variation_id'     => '',
			'product_id'       => '',
			'quantity'         => '',
			'answer'           => '',
		);
	}

	/**
	 * Extract a coupon code from a user query.
	 *
	 * @param string $query Query.
	 * @return string Coupon code.
	 */
	private function wc_cptbot_extract_coupon_code_from_query( $query ) {
		if ( preg_match( '/(?:apply|use)\s+(?:coupon|code|promo)?\s*([A-Z0-9_-]{3,30})/i', $query, $matches ) ) {
			return sanitize_text_field( $matches[1] );
		}

		if ( preg_match( '/(?:coupon|code|promo)\s*:?\s*([A-Z0-9_-]{3,30})/i', $query, $matches ) ) {
			return sanitize_text_field( $matches[1] );
		}

		return '';
	}

	/**
	 * Extract SKU-like value from a user query.
	 *
	 * @param string $query Query.
	 * @return string SKU.
	 */
	private function wc_cptbot_extract_sku_from_query( $query ) {
		if ( preg_match( '/\bsku(?:\s*#|\s*:)?\s*([A-Z0-9_-]{2,40})\b/i', $query, $matches ) ) {
			return sanitize_text_field( $matches[1] );
		}

		return '';
	}

	/**
	 * Extract a requested quantity.
	 *
	 * @param string $query Query.
	 * @return string Quantity.
	 */
	private function wc_cptbot_extract_quantity_from_query( $query ) {
		if ( preg_match( '/\b(?:qty|quantity)\s*:?\s*(\d+)\b/i', $query, $matches ) ) {
			return (string) max( 1, absint( $matches[1] ) );
		}

		if ( preg_match( '/\b(\d+)\s*(?:x|pcs|pieces|items|units)\b/i', $query, $matches ) ) {
			return (string) max( 1, absint( $matches[1] ) );
		}

		if ( preg_match( '/\b(?:add|buy|remove)\s+(\d+)\b/i', $query, $matches ) ) {
			return (string) max( 1, absint( $matches[1] ) );
		}

		return '';
	}

	/**
	 * Extract simple price filters for ChromaDB metadata filters.
	 *
	 * @param string $query Query.
	 * @return array Price filter.
	 */
	private function wc_cptbot_extract_price_filter_from_query( $query ) {
		$normalized = strtolower( wp_strip_all_tags( $query ) );
		$number     = '(?:\$|₹|rs\.?|inr)?\s*([0-9]+(?:\.[0-9]+)?)';

		if ( preg_match( '/\b(?:between|from)\s+' . $number . '\s+(?:and|to|-)\s+' . $number . '/i', $normalized, $matches ) ) {
			return array(
				'$and' => array(
					array( 'price_numeric' => array( '$gte' => (float) $matches[1] ) ),
					array( 'price_numeric' => array( '$lte' => (float) $matches[2] ) ),
				),
			);
		}

		if ( preg_match( '/\b(?:under|below|less than|up to|max|maximum)\s+' . $number . '/i', $normalized, $matches ) ) {
			return array( 'price_numeric' => array( '$lte' => (float) $matches[1] ) );
		}

		if ( preg_match( '/\b(?:above|over|more than|min|minimum)\s+' . $number . '/i', $normalized, $matches ) ) {
			return array( 'price_numeric' => array( '$gte' => (float) $matches[1] ) );
		}

		return array();
	}

	/**
	 * Extract product/cart identifiers from recent history or current query.
	 *
	 * @param array  $history_content Chat history.
	 * @param string $query           Query.
	 * @return array Identifiers.
	 */
	private function wc_cptbot_extract_cart_reference_from_history( $history_content, $query ) {
		$history_text = ! empty( $history_content ) ? Toon::encode( $history_content ) : '';
		$search_text  = $query . "\n" . $history_text;
		$data         = array();
		$sku          = $this->wc_cptbot_extract_sku_from_query( $search_text );

		if ( ! empty( $sku ) ) {
			$data['sku'] = $sku;
		}

		if ( preg_match( '/\b(?:variation_id|Variation ID)\s*:?\s*([0-9]+)/i', $search_text, $matches ) ) {
			$data['variation_id'] = sanitize_text_field( $matches[1] );
		}

		if ( preg_match( '/\b(?:product_id|Product ID|ID)\s*:?\s*([0-9]+)/i', $search_text, $matches ) ) {
			$data['product_id'] = sanitize_text_field( $matches[1] );
		}

		return $data;
	}

	/**
	 * Generate a deterministic response for casual intents.
	 *
	 * @param string $query Query.
	 * @return string Answer.
	 */
	public function wc_cptbot_get_casual_intent_answer( $query ) {
		$normalized = strtolower( trim( wp_strip_all_tags( $query ) ) );

		if ( preg_match( '/\b(thanks|thank you)\b/i', $normalized ) ) {
			return esc_html__( 'You are welcome. I can help you find products, compare options, check coupons, or continue to checkout.', 'autonomous-ai-chatbot' );
		}

		if ( preg_match( '/\b(hi|hello|hey)\b/i', $normalized ) ) {
			return esc_html__( 'Hi! What would you like to shop for today?', 'autonomous-ai-chatbot' );
		}

		return esc_html__( 'I can help with products, coupons, shipping, cart updates, or checkout. What would you like to do next?', 'autonomous-ai-chatbot' );
	}
		/**
		 * Check if a post with the specified topic and post type exists in the WordPress database.
		 *
		 * @param  string $topic    The topic to check for existence.
		 * @param  string $posttype The post type to check within (e.g., 'post', 'page', custom post type).
		 * @return int|null The post ID if the post title exists, null if not found.
		 */
		public function wc_cptbot_check_topic_exist( $topic, $posttype ) {
			// Prepare the post title for the query by sanitizing the topic input.
			$post_title = sanitize_text_field( $topic );

			// Use the WordPress database object to construct the query to find the post with the given title and post type.
			$query = $this->db->prepare( "SELECT ID FROM {$this->db->posts} WHERE post_title = %s AND post_type = %s ", $post_title, $posttype );

			// Retrieve the post ID if the post title exists, otherwise, return null.
			return $this->db->get_var( $query );
		}

	public function wc_cptbot_check_bot_online() {
		$online = true;

		// Check if new OpenAI SDK configuration is set
		if ( empty( get_option( 'wc_cptbot_api_key' ) ) || empty( get_option( 'wc_cptbot_api_base_url' ) ) || empty( get_option( 'wc_cptbot_model_name' ) ) ) {
			$online = false;
		}

		return $online;
	}

	/**
	 * Get agent prompt.
	 *
	 * @param array $history_content History content.
	 *
	 * @return array $agent_msg Agent message.
	 */
	public function wc_cptbot_get_agent_prompt( $history_content,$include_full_history = false ) {


		$history_data = ! empty( $history_content ) ? Toon::encode( $history_content ) : '';

		$currency_code = $this->wc_cptbot_get_currency_type();
		$currency_symbol = wcaichatbot_get_currency_symbol( $currency_code );


	$agent_msg = array(
		array(
			"role" => "system",
			"content" => "
	You are a decision-making assistant in an E-commerce chatbot.

	Your task is to decide whether the user's query requires:
	1) Product-related document retrieval (RAG),
	2) Category discovery,
	3) Or can be answered using existing chat history / LLM reasoning only.

	Your PRIMARY goal is to MINIMIZE unnecessary RAG calls.

	---

	### MOST IMPORTANT RULE (STRICT):
	Before deciding RAG is required, ALWAYS check the chat history.

	- If product information already exists in chat history
	AND the user is asking a follow-up question about those products
	(price, variants, availability, comparison, clarification),
	then RAG is NOT REQUIRED.

	- Only trigger RAG when the requested information
	is NOT present or NOT sufficient in chat history.

	---

	### RAG retrieval IS REQUIRED when:
	- The user asks to find, search, or recommend NEW products
	that are NOT already shown in chat history.
	- The user asks product questions AND:
	- No product data exists in chat history, OR
	- Existing product data is incomplete or insufficient.
	- The user introduces new constraints
	(new category, new price range, new product type)
	that were NOT previously retrieved.
	- The user ask for specefic detail which not available in chat history.
	- Accurate information depends on product or commerce documents.

	---

	### RAG retrieval is NOT REQUIRED when:
	- The query is casual or conversational (\"hi\", \"thanks\").
	- The user asks about products already shown in chat history except if any detail is missing.
	- The query refers to previous AI responses
	(\"this product\", \"the first one\", \"that item\") but except if any detail is missing.
	- The user asks to connect to a human agent.
	- The query is unrelated to products.

	---

	### Follow-up Query Handling (CRITICAL):
	- If RAG is required AND the query relates to a previously searched product:
	- Reuse the ORIGINAL product intent from chat history
	- Use that intent as doc_query
	- Do NOT invent a new doc_query unless the intent has changed

	---

	### ChromaDB Price Filter Detection:
	- Detect price constraints and generate chromadb_where filters.
	- Use \$and ONLY when there are TWO OR MORE conditions.
	- If only ONE price condition exists, do NOT use \$and.

	Examples:

	\"under " . $currency_symbol . "100\" →
	{
	\"price_numeric\": { \"\$lte\": 100 }
	}

	\"above " . $currency_symbol . "500\" →
	{
	\"price_numeric\": { \"\$gte\": 500 }
	}

	\"between " . $currency_symbol . "50 and " . $currency_symbol . "100\" →
	{
	\"\$and\": [
		{ \"price_numeric\": { \"\$gte\": 50 } },
		{ \"price_numeric\": { \"\$lte\": 100 } }
	]
	}

	---

	### Category Discovery Trigger:
	- If the user asks about categories, departments, or product types:
	- Set get_categories = true
	- Set rag_doc_required = false
	- Do NOT generate doc_query
	- Do NOT generate answer

	---

	### Quality Type Detection:
	- Detect quality constraints and generate quality_type.
	- Use \"min\" or \"max\" ONLY when there is a cheap or expensive constraint.
	- If no cheap or expensive constraint exists, do NOT generate quality_type.

	### SKU Search Trigger:
	- If the user provides a specific SKU (e.g., \"search for SKU 12345\"):
	- Set sku = \"12345\"
	- Set rag_doc_required = false
	- Set get_categories = false
	- doc_query = empty string
	- chromadb_where = empty object
	- answer = empty string

	### OUTPUT RULES (STRICT):
	- If RAG is required:
	- rag_doc_required = true
	- doc_query = refined or reused product query from chat history
	- Include chromadb_where ONLY if filters exist
	- answer = empty string
	- get_categories = false

	- If RAG is NOT required:
	- rag_doc_required = false
	- doc_query = empty string
	- chromadb_where = empty object
	- get_categories = false (unless category trigger)
	- answer = helpful e-commerce style response

	---
	### COUPON / DISCOUNT INTENT (HIGH PRIORITY):
	- If the user asks about:
	- discount coupons
	- promo codes
	- coupon codes
	- offers, deals, or discounts
	- available coupons
	THEN:
	- Set coupon = true
	- Set apply_coupon = false
	- Set rag_doc_required = false
	- Set get_categories = false

	- If the user wants to APPLY a specific coupon code (e.g., \"apply TEST\", \"use coupon BLACKFRIDAY\"):
	- Set apply_coupon = true
	- Set coupon_code = [The coupon code provided by the user]
	- Set coupon = false
	- Set rag_doc_required = false
	- Set get_categories = false

	Coupons NEVER require RAG or category discovery.

	---

	### SHIPPING INTENT:
	- If the user asks about:
	- shipping availability
	- shipping methods
	- delivery options
	- \"do you ship to...\"
	- \"can you deliver to...\"

	THEN:
	- Set shipping = true
	- Set rag_doc_required = false
	- Set get_categories = false
	- doc_query = empty string
	- chromadb_where = empty object
	- answer = empty string
	- coupon = false
	- quality_type = empty string

	Shipping queries NEVER require RAG or category discovery.

	---

	### ADD TO CART INTENT (CRITICAL):
	- If the user wants to add a product to the cart:
	- 1. **Search History First**: Look through the previous AI responses in the chat history.
	- 2. **Find the IDs**:
	    - Look for `SKU`, `Product ID`, and especially `VARIATION_DATA` or `VARIANTS` for variable products.
	    - `VARIATION_DATA` or `VARIANTS` contains a list of specific variations with their `variation_id` and attributes.
	- 3. **Check if it's a Variable Product**:
	    - If the product has variations (multiple colors, sizes, etc.), you MUST confirm ALL attributes with the user BEFORE adding to cart.
	    - Look at the VARIANTS field to see what attributes are available (e.g., Color: Red/Blue/Green, Size: S/M/L/XL).
	    - If the user has specified SOME attributes but NOT ALL (e.g., they said \\\"blue\\\" but didn\\'t specify size):
	      - Set `add_cart = false`
	      - Set `rag_doc_required = false`
	      - Set `answer = [Ask the user to specify the missing attributes. List the available options for the missing attributes. Be helpful and specific. Example: \\\"I found the V-Neck T-Shirt in Blue! To add it to your cart, please select a size: S, M, L, or XL.\\\"]`
	- 4. **Confirm Quantity (MANDATORY)**:
	    - You MUST confirm the quantity with the user BEFORE adding to cart.
	    - If the user has NOT specified a quantity (e.g., they just said \\\"add to cart\\\" or \\\"add this to my cart\\\"):
	      - Set `add_cart = false`
	      - Set `rag_doc_required = false`
	      - Set `answer = [Ask the user how many units they would like to add to their cart. Example: \\\"I've found the product! How many would you like to add to your cart?\\\"]`
	- 5. **If ALL attributes AND quantity are specified**:
	    - Find the exact variation_id that matches ALL the user's selections (for variable products)
	    - Set `add_cart = true`
	    - Set `sku = [The found SKU]`
	    - Set `variation_id = [The specific variation_id from VARIATION_DATA/VARIANTS that matches ALL attributes]`
	    - Set `product_id = [The found Product ID]`
	    - Set `quantity = [The specified quantity]`
	    - **IMPORTANT**: Also include the specific attributes the user selected (e.g., \"size\": \"medium\", \"color\": \"blue\") in the root of the JSON object.
	    - Set `rag_doc_required = false`
	- 6. **If it's a Simple Product (no variations) AND quantity is specified**:
	    - Set `add_cart = true`
	    - Set `sku = [The found SKU]`
	    - Set `product_id = [The found Product ID]`
	    - Set `quantity = [The specified quantity]`
	    - Set `rag_doc_required = false`
	- 7. **If NO ID or SKU is in history**:
	    - Set `add_cart = false`
	    - Set `rag_doc_required = true`
	    - Set `doc_query = [The product name or description]`
	    - The system will find the product and its details for you.
	
	**IMPORTANT**: NEVER say \\\"sorry\\\" or apologize when attributes or quantity are missing. Instead, ALWAYS ask the user to confirm the missing information.

	---

	### REMOVE FROM CART INTENT:
	- If the user wants to remove a product from the cart:
	- 1. **Search History First**: Find the SKU, Variation ID, or Product ID of the product in the chat history.
	- 2. **Confirm Quantity (MANDATORY)**:
	    - You MUST confirm the quantity to remove with the user.
	    - If the user has NOT specified a quantity (e.g., they just said \"remove this\" or \"remove from cart\"):
	      - Set `remove_cart = false`
	      - Set `rag_doc_required = false`
	      - Set `answer = [Ask the user how many units they would like to remove from their cart. Example: \"I've found the product in your cart! How many would you like to remove?\"]`
	- 3. **If SKU or ID is found AND quantity is specified**:
	    - Set `remove_cart = true`
	    - Set `sku = [The found SKU]`
	    - Set `variation_id = [The found Variation ID if applicable]`
	    - Set `product_id = [The found Product ID]`
	    - Set `quantity = [The specified quantity]`
	    - Set `rag_doc_required = false`
	- 4. **If NO ID or SKU is in history**:
	    - Set `remove_cart = false`
	    - Set `rag_doc_required = true`
	    - Set `doc_query = [The product name]`

	---

	### VIEW CART INTENT:
	- If the user wants to see their cart, check what\'s in their cart, or ask \"what\'s in my cart\":
	- Set `view_cart = true`
	- Set `rag_doc_required = false`
	- Set get_categories = false
	- doc_query = empty string
	- chromadb_where = empty object
	- answer = empty string
	- coupon = false
	- quality_type = empty string

	---

	### CHECKOUT INTENT:
	- If the user wants to checkout, place an order, or provide billing/shipping details:
	- Set `checkout = true`
	- Set `rag_doc_required = false`
	- Set get_categories = false
	- doc_query = empty string
	- chromadb_where = empty object
	- answer = empty string
	- coupon = false
	- quality_type = empty string

	---

	### Answer Style:
	- Friendly, professional e-commerce shopping assistant
	- Clear, concise, and purchase-oriented
	- Never hallucinate products or data

	---

	### Output format (JSON only):
	{
	\"rag_doc_required\": \"true\" or \"false\",
	\"doc_query\": \"Refined or reused product query or empty string\",
	\"chromadb_where\": {},
	\"get_categories\": true or false,
	\"coupon\": true or false,
	\"apply_coupon\": true or false,
	\"coupon_code\": \"The coupon code to apply\",
	\"add_cart\": true or false,
	\"remove_cart\": true or false,
	\"view_cart\": true or false,
	\"checkout\": true or false,
	\"shipping\": true or false,
	\"quality_type\": \"min\" or \"max\" or empty string,
	\"sku\": \"Product SKU\",
	\"variation_id\": \"Variation ID (MANDATORY for variable products if available)\",
	\"product_id\": \"Product ID\",
	\"quantity\": \"Include if specified (e.g. 2)\",
	\"size\": \"Include if specified (e.g. medium)\",
	\"color\": \"Include if specified (e.g. blue)\",
	\"answer\": \"Answer only when rag_doc_required is false AND get_categories is false. Use a helpful, polite, and sales-friendly tone. IMPORTANT: Avoid generic closing phrases like 'Feel free to ask' or 'Let me know if you need help'. Instead, always suggest the next logical step (e.g., 'Would you like to add this to your cart?', 'Should we proceed to checkout?', etc.). Use " . $currency_symbol . " for currency symbol in your answer.\"
	}
	"
		),
		array(
			"role" => "user",
			"content" => $history_data
    )
);

	return $agent_msg;
}
	/**
	 * Get coupon agent prompt.
	 *
	 * @param array $history_content History content.
	 *
	 * @param string $coupon Coupon.
	 *
	 * @return array $agent_msg Agent message.
	 */

	public function wc_cptbot_get_coupon_prompt( $history_content, $coupon, $include_full_history = true) {


		$history_data = ! empty( $history_content ) ? Toon::encode( $history_content ) : '';

		$currency_code = $this->wc_cptbot_get_currency_type();
		$currency_symbol = wcaichatbot_get_currency_symbol( $currency_code );


	$agent_msg = array(
		array(
			"role" => "system",
			"content" => "
You are an intelligent assistant for a WooCommerce-powered e-commerce chatbot.

Your primary responsibilities:
- Inform users about available discount coupons when asked
- Explain coupon details clearly (discount, expiry, conditions)
- Help users apply valid coupons to their cart when requested
- Respond in a friendly, concise, and professional manner

Strict rules:
- ONLY use coupons explicitly provided to you by the system
- NEVER invent, guess, or modify coupon codes
- If no coupons are available, clearly inform the user
- If a coupon is expired or invalid, explain why it cannot be used

**IMPORTANT - Discount Calculation Rules:**
- **Percentage discounts** (e.g., \"20% off\"): Calculate as (percentage / 100) × cart total
  - Example: 20% off on ₹35.00 = (20/100) × 35 = ₹7.00 discount, final price = ₹28.00
- **Fixed discounts** (e.g., \"₹10 off\"): Subtract the fixed amount from cart total
  - Example: ₹10 off on ₹35.00 = ₹35.00 - ₹10.00 = ₹25.00 final price
- **Fixed product discounts**: Apply the fixed amount per product
  - Example: ₹10 off per product on 1 item = ₹10.00 discount, final price = ₹25.00

Behavior guidelines:
- If the user asks for discounts, show available coupons
- If the user asks how to apply a coupon, explain the steps clearly
- If the user mentions a coupon code, validate it before responding
- If the question is unclear, politely ask for clarification.
- **PIPELINE GUIDANCE**: After explaining coupons, suggest the next step like 'Would you like to apply one of these to your cart?' or 'Shall we proceed to checkout?'.
- **IMPORTANT**: Avoid generic closing phrases like 'Feel free to ask'.

You are helpful, accurate, and never misleading. Use " . $currency_symbol . " for currency symbol in your answer.\n\n" . $coupon

		),
		array(
			"role" => "user",
			"content" => $history_data
    )
);

	return $agent_msg;
}

	/**
	 * Get coupon apply prompt.
	 *
	 * @param array $history_content History content.
	 * @param string $coupon_result Coupon result.
	 * @param string $cart_summary Cart summary.
	 *
	 * @return array $agent_msg Agent message.
	 */
	public function wc_cptbot_get_coupon_apply_prompt( $history_content, $coupon_result, $cart_summary ) {
		$history_data = ! empty( $history_content ) ? Toon::encode( $history_content ) : '';

		$currency_code = $this->wc_cptbot_get_currency_type();
		$currency_symbol = wcaichatbot_get_currency_symbol( $currency_code );

		$agent_msg = array(
			array(
				"role" => "system",
				"content" => "
You are a helpful assistant for a WooCommerce store.
A coupon application has just been attempted.

Coupon Application Result: " . $coupon_result . "

Current Cart Summary: " . $cart_summary . "

Your task:
- If successful, clearly state the coupon code and the amount saved.
- Show the updated cart contents and totals (Subtotal, Discount, Total).
- Suggest the next step (e.g., \"Shall we proceed to checkout?\").
- Use " . $currency_symbol . " for currency symbol. Ensure the text is clean and professional. Do NOT include redundant numbers or weird words.
"
			),
			array(
				"role" => "user",
				"content" => $history_data
			)
		);

		return $agent_msg;
	}

	/**
	 * Get shipping agent prompt.
	 *
	 * @param array $history_content History content.
	 * @param array $shipping_details Shipping details.
	 *
	 * @return array $agent_msg Agent message.
	 */
	public function wc_cptbot_get_shipping_prompt( $history_content, $shipping_details ) {

		$history_data = ! empty( $history_content ) ? Toon::encode( $history_content ) : '';
		$shipping_info = json_encode($shipping_details);

		$currency_code = $this->wc_cptbot_get_currency_type();
		$currency_symbol = wcaichatbot_get_currency_symbol( $currency_code );

		$agent_msg = array(
			array(
				"role" => "system",
				"content" => "
You are a helpful shipping assistant for a WooCommerce store.

Your task is to inform the user about available shipping methods based on the provided information.

Shipping Information:
$shipping_info

Instructions:
- If shipping is available, list the available methods clearly.
- If shipping is not available, apologize and inform the user.
- Be polite and concise.
- **PIPELINE GUIDANCE**: If shipping is available, suggest proceeding to checkout. If not, suggest checking other items or contacting support.
- **IMPORTANT**: Avoid generic closing phrases like 'Feel free to ask'.
- Do not invent shipping methods not listed in the information.
- Use " . $currency_symbol . " for currency symbol in your answer.
"
			),
			array(
				"role" => "user",
				"content" => $history_data
			)
		);

		return $agent_msg;
	}

	/**
	 * Get add to cart agent prompt.
	 *
	 * @param array $history_content History content.
	 *
	 * @return array $agent_msg Agent message.
	 */
	public function wc_cptbot_get_add_to_cart_prompt( $history_content, $cart_response ) {
		$history_data = ! empty( $history_content ) ? Toon::encode( $history_content ) : '';

		$currency_code = $this->wc_cptbot_get_currency_type();
		$currency_symbol = wcaichatbot_get_currency_symbol( $currency_code );

		$agent_msg = array(
			array(
				"role" => "system",
				"content" => "
You are a helpful shopping assistant for a WooCommerce store.

Your task is to inform the user about the result of their 'add to cart' request.

Operation Result:
$cart_response

Instructions:
- If the product was added successfully, confirm it to the user and suggest the next step (e.g., 'Item added! Would you like to view your cart or continue shopping?').
- If there was an error (e.g., product not found, out of stock, variation required), explain the issue clearly and helpfully.
- Be polite, concise, and professional.
- **IMPORTANT**: Avoid generic closing phrases like 'Feel free to ask'. Always guide the user towards checkout or more shopping.
- Use " . $currency_symbol . " for currency symbol in your answer.
"
			),
			array(
				"role" => "user",
				"content" => $history_data
			)
		);

		return $agent_msg;
	}


	/**
	 * Get remove from cart agent prompt.
	 *
	 * @param array $history_content History content.
	 * @param string $cart_response Cart response.
	 *
	 * @return array $agent_msg Agent message.
	 */
	public function wc_cptbot_get_remove_cart_prompt( $history_content, $cart_response ) {
		$history_data = ! empty( $history_content ) ? Toon::encode( $history_content ) : '';

		$agent_msg = array(
			array(
				"role" => "system",
				"content" => "
You are a helpful shopping assistant for a WooCommerce store.

Your task is to inform the user about the result of their 'remove from cart' request.

Operation Result:
$cart_response

Instructions:
- If the product was removed successfully, confirm it to the user and suggest the next step (e.g., 'Item removed. Would you like to see your updated cart or continue shopping?').
- If there was an error, explain the issue clearly.
- Be polite, concise, and professional.
- **IMPORTANT**: Avoid generic closing phrases like 'Feel free to ask'.
"
			),
			array(
				"role" => "user",
				"content" => $history_data
			)
		);

		return $agent_msg;
	}

	/**
	 * Get checkout agent prompt.
	 *
	 * @param array $history_content History content.
	 * @param string $cart_response Cart response.
	 *
	 * @return array $agent_msg Agent message.
	 */
	public function wc_cptbot_get_checkout_prompt( $history_content, $cart_summary, $shipping_methods = array(), $payment_methods = array() ) {
		$history_data = ! empty( $history_content ) ? Toon::encode( $history_content ) : '';
		$currency_code = $this->wc_cptbot_get_currency_type();
		$currency_symbol = wcaichatbot_get_currency_symbol( $currency_code );

		$shipping_info = "";
		$has_shipping_methods = ! empty( $shipping_methods );
		if ( $has_shipping_methods ) {
			$shipping_info = "\n### Available Shipping Methods:\n" . json_encode( $shipping_methods, JSON_PRETTY_PRINT );
		}

		$payment_info = "";
		$has_payment_methods = ! empty( $payment_methods );
		if ( $has_payment_methods ) {
			$payment_info = "\n### Available Payment Methods:\n" . json_encode( $payment_methods, JSON_PRETTY_PRINT );
		}

		$agent_msg = array(
			array(
				"role" => "system",
				"content" => "
You are a checkout assistant for a WooCommerce store. Your goal is to collect billing and shipping information, and have the user select shipping and payment methods to place an order.

### Current Cart Summary:
$cart_summary
$shipping_info
$payment_info

### IMPORTANT: Check what information is already provided above
- **Shipping Methods**: " . ( $has_shipping_methods ? "YES (Present these to focus on selection)" : "NO (Need to collect address first)" ) . "
- **Payment Methods**: " . ( $has_payment_methods ? "YES (Present these to focus on selection)" : "NO (Trigger get_payment_methods after shipping is selected)" ) . "
- **Information in History**: If you see in the conversation history that the user has ALREADY selected a shipping method and a payment method, proceed immediately to **Final Confirmation** (Step 4).

### Your Workflow:
1. **Collect Billing & Shipping Details**: Confirm address.
2. **Shipping Selection**:
   - If shipping methods are NOT provided, output JSON to `get_shipping_methods`.
   - If shipping methods ARE provided, list them and ask the user to choose.
3. **Payment Selection**:
   - **IMPORTANT**: When the user selects a shipping method, you MUST NOT list payment methods yourself. Instead, you MUST output the following JSON to trigger the payment agent:
     ```json
     { \"get_payment_methods\": true }
     ```
   - Do NOT ask any more questions or provide any info in that turn. Just the JSON.
4. **Final Confirmation**:
   - Show summary: Cart Total + Shipping + Payment Method.
   - **Stripe Note**: If the user selected 'Stripe', explain that once they confirm, you will generate a secure payment link from Stripe to complete the order.
   - **STRICT RULE**: You MUST ask for final confirmation (e.g., \"Would you like me to place this order for you?\") before outputting the `place_order` JSON. NEVER skip this step.

### Output Rules:
- **Get Shipping Methods**:
  ```json
  {
    \"get_shipping_methods\": true,
    \"address\": {
        \"country\": \"...\",
        \"state\": \"...\",
        \"city\": \"...\",
        \"postcode\": \"...\"
    }
  }
  ```
- **Get Payment Methods**:
  ```json
  { \"get_payment_methods\": true }
  ```
- **Place Order**:
  ```json
  {
    \"place_order\": true,
    \"billing_details\": {
        \"first_name\": \"...\",
        \"last_name\": \"...\",
        \"address_1\": \"...\",
        \"address_2\": \"...\",
        \"city\": \"...\",
        \"state\": \"...\",
        \"postcode\": \"...\",
        \"country\": \"...\",
        \"phone\": \"...\"
    },
    \"shipping_details\": {
        \"first_name\": \"...\",
        \"last_name\": \"...\",
        \"address_1\": \"...\",
        \"address_2\": \"...\",
        \"city\": \"...\",
        \"state\": \"...\",
        \"postcode\": \"...\",
        \"country\": \"...\"
    },
    \"shipping_method_id\": \"...\",
    \"payment_method\": \"...\" (e.g., \"stripe_mcp\" or the exact Title provided)
  }
  ```

- **CRITICAL**: Do NOT include conversational text when outputting JSON for `place_order`, `get_shipping_methods`, or `get_payment_methods`.
- **STRICT RULE**: Always show the labels/titles of methods exactly as provided.

### Important:
- Be polite and professional.
- Use $currency_symbol for currency.
- **STRICT RULE**: Avoid generic closing phrases like 'Feel free to ask' or 'Let me know if you need anything else'.
- **PIPELINE GUIDANCE**: Always guide the user to the next step of the checkout. If you just collected billing, ask about shipping. If you have shipping methods, ask them to choose. If everything is ready, ask for final confirmation to place the order.
"
			),
			array(
				"role" => "user",
				"content" => $history_data
			)
		);

		return $agent_msg;
	}

	/**
	 * Get payment agent prompt.
	 *
	 * @param array $history_content History content.
	 * @param array $payment_methods Payment methods.
	 *
	 * @return array $agent_msg Agent message.
	 */
	public function wc_cptbot_get_payment_prompt( $history_content, $payment_methods ) {
		$history_data = ! empty( $history_content ) ? Toon::encode( $history_content ) : '';
		$currency_code = $this->wc_cptbot_get_currency_type();
		$currency_symbol = wcaichatbot_get_currency_symbol( $currency_code );

		$payment_info = "\n### Available Payment Methods:\n" . json_encode( $payment_methods, JSON_PRETTY_PRINT );

		$agent_msg = array(
			array(
				"role" => "system",
				"content" => "
You are a payment assistant for a WooCommerce store.
The user has successully selected their shipping method. Now you must help them select a payment method.

$payment_info

Your Instructions:
1. **List all available payment methods** provided above.
2. For each method, show its title and description.
3. Ask the user to choose their preferred method to proceed with the order.
4. Be polite and professional.
5. Use $currency_symbol for currency.
6. **Stripe MCP**: If 'stripe_mcp' is in the list, explain that it allows for a faster, more secure checkout process directly within this chat interface.
7. **STRICT RULE**: Only provide conversational text. Do NOT output any JSON in this response.
"
			),
			array(
				"role" => "user",
				"content" => $history_data
			)
		);

		return $agent_msg;
	}

	/**
	 * Get view cart agent prompt.
	 *
	 * @param array $history_content History content.
	 * @param string $cart_response Cart response.
	 *
	 * @return array $agent_msg Agent message.
	 */
	public function wc_cptbot_get_view_cart_prompt( $history_content, $cart_response ) {
		$history_data = ! empty( $history_content ) ? Toon::encode( $history_content ) : '';

		$agent_msg = array(
			array(
				"role" => "system",
				"content" => "
You are a helpful shopping assistant for a WooCommerce store.

Your task is to show the user what is currently in their shopping cart.

Current Cart Contents:
$cart_response

Instructions:
- List the items in the cart clearly with their quantities and prices.
- Show the total amount.
- If the cart is empty, inform the user politely and suggest some categories to browse.
- Be polite, concise, and professional.
- **PIPELINE GUIDANCE**: After showing the cart, always suggest the next step, such as 'Would you like to proceed to checkout?' or 'Do you have a coupon code to apply?'.
- **IMPORTANT**: Avoid generic closing phrases like 'Feel free to ask'.
"
			),
			array(
				"role" => "user",
				"content" => $history_data
			)
		);

		return $agent_msg;
	}

	/**
	 * Convert chat history to OpenAI-compatible messages.
		 *
		 * @param array $history_content Array of stdClass objects with 'from' and 'msg'.
		 * @param string $system_message Optional system message.
		 * @param int $max_length Optional max length of each message (default 0 = no limit).
		 * @return array OpenAI-compatible messages.
		 */
		public function convert_history_to_openai_messages($history_content, $system_message = "You are a helpful assistant.", $max_length = 1000, $max_messages = 5) {
			$messages = [];

			if (!empty($system_message)) {
				$messages[] = [
					"role"    => "system",
					"content" => $system_message
				];
			}

			if( ! empty( $history_content ) ) {
				unset($history_content[0]);

				foreach ($history_content as $item) {
					$role = strtolower($item->from) === "ai" ? "assistant" : "user";
					$msg  = $item->msg;

					// Limit message length if needed
					if ($max_length > 0 && mb_strlen($msg) > $max_length) {
						$msg = mb_substr($msg, 0, $max_length) . '...';
					}

					$messages[] = [
						"role"    => $role,
						"content" => $msg
					];
				}
			}



			return $messages;

		}

		/**
		 * Get category descriptions for a product.
		 *
		 * @param WC_Product $product The WooCommerce product object.
		 * @return string Comma-separated category descriptions.
		 */
		public function wc_cptbot_get_category_descriptions( $product ) {
			$terms = get_the_terms( (int) $product->get_id(), 'product_cat' );
			$descriptions = array();

			if ( ! empty( $terms ) ) {
				foreach ( $terms as $term ) {
					$description = get_term_meta( $term->term_id, 'description', true );
					if ( empty( $description ) ) {
						$description = $term->description;
					}
					if ( ! empty( $description ) ) {
						$descriptions[] = wp_strip_all_tags( $description );
					}
				}
			}

			return ! empty( $descriptions ) ? implode( ' | ', $descriptions ) : '';
		}

		/**
		 * Get category IDs for a product.
		 *
		 * @param WC_Product $product The WooCommerce product object.
		 * @return array Array of category IDs.
		 */
		public function wc_cptbot_get_category_ids( $product ) {
			$terms = get_the_terms( (int) $product->get_id(), 'product_cat' );
			$category_ids = array();

			if ( ! empty( $terms ) ) {
				foreach ( $terms as $term ) {
					$category_ids[] = (int) $term->term_id;
				}
			}

			return $category_ids;
		}

		/**
		 * Get product tags as comma-separated string.
		 *
		 * @param WC_Product $product The WooCommerce product object.
		 * @return string Comma-separated tags.
		 */
		public function wc_cptbot_get_product_tags( $product ) {
			$terms = get_the_terms( (int) $product->get_id(), 'product_tag' );
			$tags = array();

			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$tags[] = $term->name;
				}
			}

			return ! empty( $tags ) ? implode( ', ', $tags ) : '';
		}

		/**
		 * Get product short description (cleaned).
		 *
		 * @param WC_Product $product The WooCommerce product object.
		 * @param int        $max_length Maximum length of description.
		 * @return string Cleaned product short description.
		 */
		public function wc_cptbot_get_product_short_description( $product, $max_length = 300 ) {
			$description = ! empty( $product->get_short_description() ) ? $product->get_short_description() : '';
			$description = wp_strip_all_tags( $description );
			$description = trim( $description );

			if ( ! empty( $description ) && mb_strlen( $description ) > $max_length ) {
				$description = mb_substr( $description, 0, $max_length ) . '...';
			}

			return $description;
		}

		/**
		 * Get product full description (cleaned).
		 *
		 * @param WC_Product $product The WooCommerce product object.
		 * @param int        $max_length Maximum length of description.
		 * @return string Cleaned product full description.
		 */
		public function wc_cptbot_get_product_description( $product, $max_length = 500 ) {
			$description = ! empty( $product->get_description() ) ? $product->get_description() : '';
			$description = wp_strip_all_tags( $description );
			$description = trim( $description );

			if ( ! empty( $description ) && mb_strlen( $description ) > $max_length ) {
				$description = mb_substr( $description, 0, $max_length ) . '...';
			}

			return $description;
		}

		/**
		 * Allow pages to load the model.
		 *
		 * @return bool True if allowed, false otherwise.
		 */
		public function wc_chatgpt_allowed_pages_to_load_model() {
			$page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$tab  = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

			$pages = apply_filters( "wcaichatbot_modify_allowed_pages_to_load_model", array( 'chatgpt-bot' ) );
			$tabs  = apply_filters( "wcaichatbot_modify_allowed_tabs_to_load_model", array( 'training' ) );

			return in_array( $page, $pages, true ) && in_array( $tab, $tabs, true ) ? true : false;
		}

		/**
		 * Download model file.
		 *
		 * @return array Model download response.
		 */
		public function wc_cptbot_download_model() {
			$response = array(
				'success' => false,
				'message' => '',
				'data' => array(),
			);
			
			try {
				$model_url  = 'https://wpdemo.webkul.com/wk-extensions/ai/embedding/e5-small.onnx';
				$model_dir  = WC_CPTBOT_PLUGIN_FILE . 'assets/models/';    
				$model_path = $model_dir . 'e5-small.onnx';
				$chunk = 3 * 1024 * 1024; // 3mb.
				$offset = (int) get_option('wc_cptbot_download_offset', 0);
				$download_status = get_option('wc_cptbot_model_downloaded', '');
				$is_completed =  false;

				/**
				 * get total file size.
				 */
				$total_size = (int) get_option('wc_cptbot_model_total_size', 0);

				if (0 === $total_size) {

					$head_response = wp_remote_get(
						$model_url, 
						array(
							'timeout'     => 60,
							'redirection' => 0,
							'httpversion' => '1.1',
							'headers'     => array(
								'Range' => 'bytes=0-0',
							),
							'sslverify' => false,
						),
					);

					if (! is_wp_error($head_response)) {
						$range = wp_remote_retrieve_header($head_response, 'content-range');
		
						if ($range && preg_match('/\/(\d+)$/', $range, $m)) {
							$total_size = (int) $m[1];
							update_option('wc_cptbot_model_total_size', $total_size);
						}
					}
				}

				if (! file_exists($model_path) || 'downloaded' !== $download_status) {
					if(file_exists($model_path) && 0 >= $offset ) {
						wp_delete_file($model_path);
						$offset = 0;
					}

					if(! file_exists($model_path) ) {
						$offset = 0; 
					}
						
					wp_mkdir_p($model_dir);                    
					update_option('wc_cptbot_model_downloaded', 'processing');
						
					$args = array(
						'headers' => array(
							'Range' => 'bytes=' . $offset . '-' . ($offset + $chunk - 1),
						),    
						'timeout' => 60,
						'sslverify' => false
					);
								

					$res = wp_remote_get($model_url, $args);
		
					if (is_wp_error($res)) {
						update_option('wc_cptbot_model_downloaded', 'failed');
						throw new \Exception($res->get_error_message());
					}


					$body = wp_remote_retrieve_body($res);

					if (empty($body)) {
						update_option('wc_cptbot_model_downloaded', 'failed');
						throw new \Exception(esc_html__('Empty chunk received', 'autonomous-ai-chatbot'));
					}

					$current_size = file_exists($model_path) ? filesize($model_path) : 0;

					if($current_size > $offset) {
						$offset = $current_size;
						update_option('wc_cptbot_download_offset', $offset);

						$response['success'] = true;
						$response['message'] = esc_html__('Duplicate chunk skipped', 'autonomous-ai-chatbot');
						$response['data'] = array(
							'path' => $model_path,
							'offset' => $offset,
							'total' => $total_size,
							'is_completed' => false,
						);

						return wp_send_json($response);
					}
					file_put_contents($model_path, $body, FILE_APPEND);

					$downloaded = strlen($body);
					$offset += $downloaded;

					update_option('wc_cptbot_download_offset', $offset);

					// last chunk.
					if ($downloaded < $chunk ||  ( $total_size <= $offset ) ) {
						$is_completed = true;
						update_option('wc_cptbot_model_downloaded', 'downloaded');
						$response['message'] = esc_html__('Modal downloaded successfully', 'autonomous-ai-chatbot');
					}         
				}
				
				$response['success'] = true;
				$response['message'] = empty($response['message']) ? esc_html__('Modal chunk downloaded successfully', 'autonomous-ai-chatbot') : $response['message'];
				
				$response['data'] = array(
					'path' => $model_path,
					'offset' => $offset,
					'total' => $total_size,
					'is_completed' =>  $is_completed,
				);

			} catch (\Exception $ex) {
				$response['message'] = $ex->getMessage();
			}

			return $response;
		}

		/**
		 * Retrieve aggregated KPI metrics for the dashboard with period comparison.
		 *
		 * @param int $days Number of days in the period (e.g. 7 or 30).
		 * @return array Structured KPI card metrics.
		 */
		public function wc_cptbot_get_kpi_metrics( $days = 7 ) {
			$days = max( 1, (int) $days );
			$chat_table = $this->db->prefix . 'cptbot_chat_history';
			$schedule_table = $this->db->prefix . 'cptbot_scheduled_actions';

			// 1. Total Conversations (Current vs Previous period)
			$current_conversations = (int) $this->db->get_var(
				$this->db->prepare(
					"SELECT COUNT(DISTINCT user_id) FROM {$chat_table} WHERE created_at >= NOW() - INTERVAL %d DAY",
					$days
				)
			);
			$prev_conversations = (int) $this->db->get_var(
				$this->db->prepare(
					"SELECT COUNT(DISTINCT user_id) FROM {$chat_table} WHERE created_at >= NOW() - INTERVAL %d DAY AND created_at < NOW() - INTERVAL %d DAY",
					$days * 2,
					$days
				)
			);

			// 2. Total Messages (Current vs Previous period)
			$current_messages = (int) $this->db->get_var(
				$this->db->prepare(
					"SELECT COUNT(*) FROM {$chat_table} WHERE created_at >= NOW() - INTERVAL %d DAY",
					$days
				)
			);
			$prev_messages = (int) $this->db->get_var(
				$this->db->prepare(
					"SELECT COUNT(*) FROM {$chat_table} WHERE created_at >= NOW() - INTERVAL %d DAY AND created_at < NOW() - INTERVAL %d DAY",
					$days * 2,
					$days
				)
			);

			// 3. Active Users Today (Today vs Yesterday)
			$today_users = (int) $this->db->get_var(
				"SELECT COUNT(DISTINCT user_id) FROM {$chat_table} WHERE DATE(created_at) = CURDATE()"
			);
			$yesterday_users = (int) $this->db->get_var(
				"SELECT COUNT(DISTINCT user_id) FROM {$chat_table} WHERE DATE(created_at) = CURDATE() - INTERVAL 1 DAY"
			);

			// 4. Resolution Rate (% success status in period)
			$success_messages = (int) $this->db->get_var(
				$this->db->prepare(
					"SELECT COUNT(*) FROM {$chat_table} WHERE created_at >= NOW() - INTERVAL %d DAY AND chat_status = 1 AND message_status = 1",
					$days
				)
			);
			$resolution_rate = $current_messages > 0 ? round( ( $success_messages / $current_messages ) * 100, 1 ) : 100;

			// 5. Knowledge Base Synced (Vectors synced)
			$synced_actions = (int) $this->db->get_var(
				"SELECT COUNT(*) FROM {$schedule_table} WHERE action_status = 1"
			);
			$total_actions = (int) $this->db->get_var(
				"SELECT COUNT(*) FROM {$schedule_table}"
			);
			$trained_products = (int) get_option( 'wc_cptbot_scheduler_trained_count', 0 );
			if ( $trained_products > 0 && $synced_actions === 0 ) {
				$synced_actions = $trained_products;
			}

			// Helper closure for trend calculation
			$calc_trend = function( $current, $prev ) use ( $days ) {
				if ( 0 === $prev ) {
					if ( $current > 0 ) {
						return array(
							'trend'         => '+100%',
							'direction'     => 'up',
							/* translators: 1: previous period count, 2: number of days */
							'reference'     => sprintf( esc_html__( 'vs %1$d in prev %2$d days', 'autonomous-ai-chatbot' ), $prev, $days ),
						);
					}
					return array(
						'trend'         => null,
						'direction'     => 'neutral',
						'reference'     => esc_html__( 'No activity in either period', 'autonomous-ai-chatbot' ),
					);
				}
				$diff = ( ( $current - $prev ) / $prev ) * 100;
				$diff_rounded = round( $diff, 1 );
				$sign = $diff >= 0 ? '+' : '';
				return array(
					'trend'         => $sign . $diff_rounded . '%',
					'direction'     => $diff >= 0 ? 'up' : 'down',
					/* translators: 1: previous period count, 2: number of days */
					'reference'     => sprintf( esc_html__( 'from %1$d in prev %2$d days', 'autonomous-ai-chatbot' ), $prev, $days ),
				);
			};

			$conv_trend = $calc_trend( $current_conversations, $prev_conversations );
			$msg_trend = $calc_trend( $current_messages, $prev_messages );
			$user_trend = $calc_trend( $today_users, $yesterday_users );
			/* translators: %d: number of active users yesterday */
			$user_trend['reference'] = ( 0 === $yesterday_users && 0 === $today_users ) ? esc_html__( 'No users active today', 'autonomous-ai-chatbot' ) : sprintf( esc_html__( 'vs %d yesterday', 'autonomous-ai-chatbot' ), $yesterday_users );

			$is_pro_active = function_exists( 'wcaichatbot_is_pro_active' ) && wcaichatbot_is_pro_active();

			// Base KPI card structures
			$kpis = array(
				'total_conversations' => array(
					'id'             => 'total_conversations',
					'title'          => esc_html__( 'Total Conversations', 'autonomous-ai-chatbot' ),
					'value'          => number_format_i18n( $current_conversations ),
					'trend'          => $conv_trend['trend'],
					'trendDirection' => $conv_trend['direction'],
					'subtitle'       => $conv_trend['reference'],
					'icon'           => 'conversations',
				),
				'total_messages' => array(
					'id'             => 'total_messages',
					'title'          => esc_html__( 'Total Messages', 'autonomous-ai-chatbot' ),
					'value'          => number_format_i18n( $current_messages ),
					'trend'          => $msg_trend['trend'],
					'trendDirection' => $msg_trend['direction'],
					'subtitle'       => $msg_trend['reference'],
					'icon'           => 'messages',
				),
				'active_users_today' => array(
					'id'             => 'active_users_today',
					'title'          => esc_html__( 'Active Users Today', 'autonomous-ai-chatbot' ),
					'value'          => number_format_i18n( $today_users ),
					'trend'          => $user_trend['trend'],
					'trendDirection' => $user_trend['direction'],
					'subtitle'       => $user_trend['reference'],
					'icon'           => 'users',
				),
				'resolution_rate' => array(
					'id'             => 'resolution_rate',
					'title'          => esc_html__( 'AI Resolution Rate', 'autonomous-ai-chatbot' ),
					'value'          => $current_messages > 0 ? $resolution_rate . '%' : '100%',
					'trend'          => null,
					'trendDirection' => 'up',
					'subtitle'       => $current_messages > 0 ? esc_html__( 'Queries resolved successfully', 'autonomous-ai-chatbot' ) : esc_html__( 'Awaiting queries in period', 'autonomous-ai-chatbot' ),
					'icon'           => 'resolution',
				),
				'knowledge_synced' => array(
					'id'             => 'knowledge_synced',
					'title'          => esc_html__( 'Knowledge Synced', 'autonomous-ai-chatbot' ),
					'value'          => $is_pro_active ? number_format_i18n( $synced_actions ) : '—',
					'trend'          => null,
					'trendDirection' => 'neutral',
					/* translators: %d: total number of indexed items */
					'subtitle'       => $is_pro_active ? sprintf( esc_html__( '%d total items indexed', 'autonomous-ai-chatbot' ), $total_actions > 0 ? $total_actions : $synced_actions ) : esc_html__( 'Available with PRO Addon', 'autonomous-ai-chatbot' ),
					'icon'           => 'knowledge',
					'is_pro'         => ! $is_pro_active,
				),
				// Default Commerce Cards (Lite fallback or filtered by PRO)
				'bot_cart_additions' => array(
					'id'             => 'bot_cart_additions',
					'title'          => esc_html__( 'Bot Cart Additions', 'autonomous-ai-chatbot' ),
					'value'          => '—',
					'trend'          => null,
					'trendDirection' => 'neutral',
					'subtitle'       => esc_html__( 'Available with PRO Addon', 'autonomous-ai-chatbot' ),
					'icon'           => 'cart',
					'is_pro'         => ! $is_pro_active,
				),
				'bot_revenue' => array(
					'id'             => 'bot_revenue',
					'title'          => esc_html__( 'Bot-Driven Revenue', 'autonomous-ai-chatbot' ),
					'value'          => ( function_exists( 'get_woocommerce_currency_symbol' ) ? html_entity_decode( wcaichatbot_get_currency_symbol(), ENT_QUOTES, 'UTF-8' ) : '$' ) . '0.00',
					'trend'          => null,
					'trendDirection' => 'neutral',
					'subtitle'       => esc_html__( 'Available with PRO Addon', 'autonomous-ai-chatbot' ),
					'icon'           => 'revenue',
					'is_pro'         => ! $is_pro_active,
				),
				'top_product' => array(
					'id'             => 'top_product',
					'title'          => esc_html__( 'Top Queried Product', 'autonomous-ai-chatbot' ),
					'value'          => '—',
					'trend'          => null,
					'trendDirection' => 'neutral',
					'subtitle'       => esc_html__( 'Available with PRO Addon', 'autonomous-ai-chatbot' ),
					'icon'           => 'top_product',
					'is_pro'         => ! $is_pro_active,
				),
				'bot_coupons' => array(
					'id'             => 'bot_coupons',
					'title'          => esc_html__( 'Bot Coupons Applied', 'autonomous-ai-chatbot' ),
					'value'          => '0',
					'trend'          => null,
					'trendDirection' => 'neutral',
					'subtitle'       => esc_html__( 'Available with PRO Addon', 'autonomous-ai-chatbot' ),
					'icon'           => 'coupons',
					'is_pro'         => ! $is_pro_active,
				),
				'avg_latency' => $this->wc_cptbot_get_avg_ai_latency(),
			);

			/**
			 * Filter dashboard KPI data so addons (like WooCommerce PRO) can inject live commerce metrics.
			 *
			 * @param array $kpis Array of KPI definitions.
			 * @param int   $days Number of days in the period.
			 */
			return apply_filters( 'wcaichatbot_dashboard_kpis', $kpis, $days );
		}

		/**
		 * Records an AI latency sample in seconds.
		 *
		 * Stores the 20 most recent latency measurements in a rolling list.
		 *
		 * @param float $latency_seconds The latency in seconds.
		 */
		public function wc_cptbot_record_ai_latency( $latency_seconds ) {
			$latency = (float) $latency_seconds;
			// Ignore invalid values or unreasonable timeouts.
			if ( $latency <= 0.05 || $latency > 60.0 ) {
				return;
			}

			$samples = get_option( 'wcaichatbot_recent_latencies', array() );
			if ( ! is_array( $samples ) ) {
				$samples = array();
			}

			$samples[] = round( $latency, 2 );

			// Keep the last 20 samples.
			if ( count( $samples ) > 20 ) {
				$samples = array_slice( $samples, -20 );
			}

			update_option( 'wcaichatbot_recent_latencies', $samples, false );

			// Invalidate KPI caches so dashboard reflects fresh latency immediately.
			delete_transient( 'wcaichatbot_kpis_v4_7_lite' );
			delete_transient( 'wcaichatbot_kpis_v4_7_pro' );
			delete_transient( 'wcaichatbot_kpis_v4_30_lite' );
			delete_transient( 'wcaichatbot_kpis_v4_30_pro' );
		}

		/**
		 * Computes the dynamic average AI latency for dashboard display.
		 *
		 * @return array
		 */
		public function wc_cptbot_get_avg_ai_latency() {
			$samples = get_option( 'wcaichatbot_recent_latencies', array() );

			if ( ! empty( $samples ) && is_array( $samples ) ) {
				$avg       = array_sum( $samples ) / count( $samples );
				$formatted = number_format( $avg, 1 ) . 's';

				$trend          = null;
				$trendDirection = 'neutral';
				$subtitle       = esc_html__( 'Average response time', 'autonomous-ai-chatbot' );

				if ( count( $samples ) >= 6 ) {
					$half       = (int) floor( count( $samples ) / 2 );
					$recent     = array_slice( $samples, -$half );
					$older      = array_slice( $samples, 0, $half );
					$recent_avg = array_sum( $recent ) / count( $recent );
					$older_avg  = array_sum( $older ) / count( $older );

					if ( $older_avg > 0 ) {
						$diff = ( ( $recent_avg - $older_avg ) / $older_avg ) * 100;
						if ( abs( $diff ) >= 5 ) {
							$trend          = ( $diff > 0 ? '+' : '' ) . round( $diff ) . '%';
							$trendDirection = $diff < 0 ? 'up' : 'down';
							$subtitle       = $diff < 0 ? esc_html__( 'Faster than previous', 'autonomous-ai-chatbot' ) : esc_html__( 'Slower than previous', 'autonomous-ai-chatbot' );
						}
					}
				}

				return array(
					'id'             => 'avg_latency',
					'title'          => esc_html__( 'Avg AI Latency', 'autonomous-ai-chatbot' ),
					'value'          => $formatted,
					'trend'          => $trend,
					'trendDirection' => $trendDirection,
					'subtitle'       => $subtitle,
					'icon'           => 'latency',
				);
			}

			// If no samples recorded yet, check if any chat exists.
			$chat_table   = $this->db->prefix . 'cptbot_chat_history';
			$has_messages = (int) $this->db->get_var( "SELECT COUNT(*) FROM {$chat_table}" ) > 0;

			return array(
				'id'             => 'avg_latency',
				'title'          => esc_html__( 'Avg AI Latency', 'autonomous-ai-chatbot' ),
				'value'          => $has_messages ? '1.2s' : '—',
				'trend'          => null,
				'trendDirection' => 'neutral',
				'subtitle'       => $has_messages ? esc_html__( 'Average response time', 'autonomous-ai-chatbot' ) : esc_html__( 'Awaiting queries in period', 'autonomous-ai-chatbot' ),
				'icon'           => 'latency',
			);
		}

		/**
		 * Retrieve published WordPress posts and pages for training.
		 *
		 * @param int          $limit      Number of items to fetch.
		 * @param int          $offset     Offset for pagination.
		 * @param array|string $post_types Post types to fetch ('post', 'page', or both).
		 * @param string       $status     'all', 'synced', or 'unsynced'.
		 * @return array Array of post records with sync status.
		 */
		public function wc_cptbot_get_all_posts_pages( $limit = 10, $offset = 0, $post_types = array( 'post', 'page' ), $status = 'all' ) {
			if ( is_string( $post_types ) ) {
				$post_types = array( $post_types );
			}

			$sanitized_types = array();
			foreach ( $post_types as $type ) {
				if ( in_array( $type, array( 'post', 'page' ), true ) ) {
					$sanitized_types[] = $type;
				}
			}
			if ( empty( $sanitized_types ) ) {
				$sanitized_types = array( 'post', 'page' );
			}

			$types_placeholders = implode( ',', array_fill( 0, count( $sanitized_types ), '%s' ) );

			$query_args = array_merge( $sanitized_types, array( 'publish' ) );

			if ( 'synced' === $status ) {
				$sql = "SELECT p.ID, p.post_title, p.post_type, p.post_modified, p.post_date
					FROM `{$this->db->prefix}posts` p
					INNER JOIN `{$this->db->prefix}postmeta` pm ON p.ID = pm.post_id AND pm.meta_key = 'cptbot_post_data'
					WHERE p.post_type IN ($types_placeholders)
					AND p.post_status = %s
					AND pm.meta_value IS NOT NULL AND pm.meta_value != ''
					ORDER BY p.post_modified DESC
					LIMIT %d OFFSET %d";
				$query_args[] = (int) $limit;
				$query_args[] = (int) $offset;
			} elseif ( 'unsynced' === $status ) {
				$sql = "SELECT p.ID, p.post_title, p.post_type, p.post_modified, p.post_date
					FROM `{$this->db->prefix}posts` p
					LEFT JOIN `{$this->db->prefix}postmeta` pm ON p.ID = pm.post_id AND pm.meta_key = 'cptbot_post_data'
					WHERE p.post_type IN ($types_placeholders)
					AND p.post_status = %s
					AND (pm.meta_value IS NULL OR pm.meta_value = '')
					ORDER BY p.post_modified DESC
					LIMIT %d OFFSET %d";
				$query_args[] = (int) $limit;
				$query_args[] = (int) $offset;
			} else {
				$sql = "SELECT p.ID, p.post_title, p.post_type, p.post_modified, p.post_date
					FROM `{$this->db->prefix}posts` p
					WHERE p.post_type IN ($types_placeholders)
					AND p.post_status = %s
					ORDER BY p.post_modified DESC
					LIMIT %d OFFSET %d";
				$query_args[] = (int) $limit;
				$query_args[] = (int) $offset;
			}

			$rows = $this->db->get_results( $this->db->prepare( $sql, $query_args ), ARRAY_A );

			$items = array();
			if ( ! empty( $rows ) ) {
				foreach ( $rows as $row ) {
					$post_id     = (int) $row['ID'];
					$post_meta   = get_post_meta( $post_id, 'cptbot_post_data', true );
					$is_synced   = ! empty( $post_meta );
					$meta_arr = $is_synced ? maybe_unserialize( $post_meta ) : array();
					if ( ! is_array( $meta_arr ) && is_string( $post_meta ) ) {
						$fixed = preg_replace_callback( '/s:(\d+):"(.*?)";/s', function( $m ) {
							return 's:' . strlen( $m[2] ) . ':"' . $m[2] . '";';
						}, $post_meta );
						$meta_arr = @unserialize( $fixed );
					}
					$last_synced   = is_array( $meta_arr ) && ! empty( $meta_arr['trained_at'] ) ? $meta_arr['trained_at'] : '';
					$trained_db    = is_array( $meta_arr ) && ! empty( $meta_arr['trained_db'] ) ? $meta_arr['trained_db'] : '';
					$trained_model = is_array( $meta_arr ) && ! empty( $meta_arr['model_type'] ) ? $meta_arr['model_type'] : '';

					if ( empty( $trained_model ) && is_string( $post_meta ) && preg_match( '/"model_type"[;:s0-9]+"([^"]+)"/', $post_meta, $mm ) ) {
						$trained_model = $mm[1];
					}
					if ( empty( $trained_db ) && is_string( $post_meta ) && preg_match( '/"trained_db"[;:s0-9]+"([^"]+)"/', $post_meta, $md ) ) {
						$trained_db = $md[1];
					}

					$current_model = get_option( 'cpt_bot_model_type', 'onnx' );
					$current_db    = get_option( 'wc_cptbot_vector_db', 'pinecone' );

					$model_mismatch = ( ! empty( $trained_model ) && $trained_model !== $current_model );
					$db_mismatch    = ( ! empty( $trained_db ) && $trained_db !== $current_db );

					// If model or vector DB changed, it requires re-sync.
					if ( $model_mismatch || $db_mismatch ) {
						$is_synced = false;
					}

					// Check if modified after sync
					$is_outdated = false;
					if ( $is_synced && ! empty( $last_synced ) ) {
						$mod_time  = strtotime( $row['post_modified'] );
						$sync_time = strtotime( $last_synced );
						if ( $mod_time > ( $sync_time + 60 ) ) {
							$is_outdated = true;
						}
					}

					$items[] = array(
						'ID'             => $post_id,
						'title'          => ! empty( $row['post_title'] ) ? $row['post_title'] : esc_html__( '(no title)', 'autonomous-ai-chatbot' ),
						'post_type'      => $row['post_type'],
						'url'            => get_permalink( $post_id ),
						'modified'       => $row['post_modified'],
						'date'           => $row['post_date'],
						'is_synced'      => $is_synced,
						'is_outdated'    => $is_outdated,
						'model_mismatch' => $model_mismatch,
						'db_mismatch'    => $db_mismatch,
						'trained_model'  => $trained_model,
						'last_synced'    => $last_synced,
						'trained_db'     => $trained_db,
					);
				}
			}

			return array(
				'items' => $items,
			);
		}

		/**
		 * Retrieve count metrics for published posts and pages.
		 *
		 * @param array|string $post_types Post types to count ('post', 'page', or both).
		 * @return array
		 */
		public function wc_cptbot_get_posts_pages_count( $post_types = array( 'post', 'page' ) ) {
			if ( is_string( $post_types ) ) {
				$post_types = array( $post_types );
			}

			$sanitized_types = array();
			foreach ( $post_types as $type ) {
				if ( in_array( $type, array( 'post', 'page' ), true ) ) {
					$sanitized_types[] = $type;
				}
			}
			if ( empty( $sanitized_types ) ) {
				$sanitized_types = array( 'post', 'page' );
			}

			$types_placeholders = implode( ',', array_fill( 0, count( $sanitized_types ), '%s' ) );

			// Total count
			$query_args = array_merge( $sanitized_types, array( 'publish' ) );
			$total_sql  = "SELECT COUNT(ID) FROM `{$this->db->prefix}posts` WHERE post_type IN ($types_placeholders) AND post_status = %s";
			$total      = (int) $this->db->get_var( $this->db->prepare( $total_sql, $query_args ) );

			// Post count
			$posts_count = (int) $this->db->get_var(
				$this->db->prepare(
					"SELECT COUNT(ID) FROM `{$this->db->prefix}posts` WHERE post_type = 'post' AND post_status = %s",
					'publish'
				)
			);

			// Page count
			$pages_count = (int) $this->db->get_var(
				$this->db->prepare(
					"SELECT COUNT(ID) FROM `{$this->db->prefix}posts` WHERE post_type = 'page' AND post_status = %s",
					'publish'
				)
			);

			// Synced count (verifying active model and vector DB match)
			$current_model = get_option( 'cpt_bot_model_type', 'onnx' );
			$current_db    = get_option( 'wc_cptbot_vector_db', 'pinecone' );

			$meta_rows = $this->db->get_results(
				$this->db->prepare(
					"SELECT pm.meta_value
					FROM `{$this->db->prefix}posts` p
					INNER JOIN `{$this->db->prefix}postmeta` pm ON p.ID = pm.post_id AND pm.meta_key = 'cptbot_post_data'
					WHERE p.post_type IN ($types_placeholders)
					AND p.post_status = %s
					AND pm.meta_value IS NOT NULL AND pm.meta_value != ''",
					$query_args
				),
				ARRAY_A
			);

			$synced = 0;
			if ( ! empty( $meta_rows ) ) {
				foreach ( $meta_rows as $meta_row ) {
					$raw_val = $meta_row['meta_value'];
					$meta    = maybe_unserialize( $raw_val );
					if ( ! is_array( $meta ) && is_string( $raw_val ) ) {
						$fixed = preg_replace_callback( '/s:(\d+):"(.*?)";/s', function( $m ) {
							return 's:' . strlen( $m[2] ) . ':"' . $m[2] . '";';
						}, $raw_val );
						$meta = @unserialize( $fixed );
					}
					$t_model = is_array( $meta ) && ! empty( $meta['model_type'] ) ? $meta['model_type'] : '';
					$t_db    = is_array( $meta ) && ! empty( $meta['trained_db'] ) ? $meta['trained_db'] : '';
					if ( empty( $t_model ) && is_string( $raw_val ) && preg_match( '/"model_type"[;:s0-9]+"([^"]+)"/', $raw_val, $mm ) ) {
						$t_model = $mm[1];
					}
					if ( empty( $t_db ) && is_string( $raw_val ) && preg_match( '/"trained_db"[;:s0-9]+"([^"]+)"/', $raw_val, $md ) ) {
						$t_db = $md[1];
					}

					if ( ( empty( $t_model ) || $t_model === $current_model ) && ( empty( $t_db ) || $t_db === $current_db ) ) {
						$synced++;
					}
				}
			}

			$unsynced = max( 0, $total - $synced );

			return array(
				'total'    => $total,
				'posts'    => $posts_count,
				'pages'    => $pages_count,
				'synced'   => $synced,
				'unsynced' => $unsynced,
				'percent'  => $total > 0 ? round( ( $synced / $total ) * 100, 1 ) : 0,
			);
		}

		/**
		 * Prepare clean text for embedding from a post or page.
		 *
		 * @param int|WP_Post $post Post ID or WP_Post instance.
		 * @return string Formatted plain text string for vectorization.
		 */
		public function wc_cptbot_get_post_page_data_for_training( $post ) {
			if ( is_numeric( $post ) ) {
				$post = get_post( (int) $post );
			}
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}

			$title   = sanitize_text_field( $post->post_title );
			$type    = ucfirst( $post->post_type );
			$url     = get_permalink( $post->ID );
			$excerpt = ! empty( $post->post_excerpt ) ? sanitize_text_field( $post->post_excerpt ) : '';

			// Strip Gutenberg block markers, shortcodes, and HTML tags
			$content = $post->post_content;
			$content = preg_replace( '/<!--\s*\/?wp:[^\>]*-->/s', '', $content );
			$content = strip_shortcodes( $content );
			$content = wp_strip_all_tags( $content );
			$content = preg_replace( '/\s+/', ' ', $content );
			$content = trim( $content );

			$text  = "Title: {$title}\n";
			$text .= "Type: {$type}\n";
			$text .= "URL: {$url}\n";

			if ( 'post' === $post->post_type ) {
				$cats = wp_get_post_categories( $post->ID, array( 'fields' => 'names' ) );
				if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) {
					$text .= 'Categories: ' . implode( ', ', $cats ) . "\n";
				}
				$tags = wp_get_post_tags( $post->ID, array( 'fields' => 'names' ) );
				if ( ! empty( $tags ) && ! is_wp_error( $tags ) ) {
					$text .= 'Tags: ' . implode( ', ', $tags ) . "\n";
				}
			}

			if ( ! empty( $excerpt ) ) {
				$text .= "Summary: {$excerpt}\n";
			}

			$text .= "Content:\n{$content}";

			return apply_filters( 'wcaichatbot_post_page_training_text', $text, $post );
		}

		/**
		 * Prepare metadata array for a post or page to store with its vector.
		 *
		 * @param int|WP_Post $post Post ID or WP_Post object.
		 * @return array
		 */
		public function wc_cptbot_get_post_page_meta_for_bot( $post ) {
			if ( is_numeric( $post ) ) {
				$post = get_post( (int) $post );
			}
			if ( ! $post instanceof \WP_Post ) {
				return array();
			}

			$vector_db  = get_option( 'wc_cptbot_vector_db', 'pinecone' );
			$model_type = get_option( 'cpt_bot_model_type', 'onnx' );

			$content = $post->post_content;
			$content = preg_replace( '/<!--\s*\/?wp:[^\>]*-->/s', '', $content );
			$content = strip_shortcodes( $content );
			$content = wp_strip_all_tags( $content );
			$content = preg_replace( '/\s+/', ' ', $content );
			$content = trim( $content );

			// Limit stored snippet length in metadata to 1000 chars to avoid vector DB metadata size limits
			$snippet = mb_substr( $content, 0, 1000 );

			$metadata = array(
				'ID'         => (int) $post->ID,
				'post_id'    => (int) $post->ID,
				'post_type'  => (string) $post->post_type,
				'Name'       => (string) $post->post_title,
				'title'      => (string) $post->post_title,
				'url'        => (string) get_permalink( $post->ID ),
				'excerpt'    => ! empty( $post->post_excerpt ) ? (string) wp_strip_all_tags( $post->post_excerpt ) : '',
				'content'    => $snippet,
				'modified'   => (string) $post->post_modified,
				'trained_db' => (string) $vector_db,
				'model_type' => (string) $model_type,
			);

			if ( 'post' === $post->post_type ) {
				$cats = wp_get_post_categories( $post->ID, array( 'fields' => 'names' ) );
				if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) {
					$metadata['categories'] = implode( ', ', $cats );
				}
			}

			return apply_filters( 'wcaichatbot_post_page_meta_for_bot', $metadata, $post );
		}
	}
}
