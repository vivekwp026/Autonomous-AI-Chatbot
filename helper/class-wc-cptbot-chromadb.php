<?php

/**
 * ChromaDB Api Handler (Refactored for ChromaDB v0.5+/v0.6+ compatibility).
 *
 * @package Chatgpt bot for WooCommerce
 * @version 1.1.0
 */

namespace WCAICHATBOT\Helper;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class Wc_Cptbot_Chromadb
 */
class Wc_Cptbot_Chromadb
{

    protected $baseurl = '';
    protected $collection = '';
    protected $collection_uuid = '';
    protected $apikey = '';

    public function __construct()
    {

        $base_url = get_option('wc_cptbot_chroma_base_url');

        if (! empty($base_url)) {
            $base_url = rtrim($base_url, '/');
            if (strpos($base_url, '/api/v1') === false) {
                $base_url = $base_url . '/api/v1';
            }
            $this->baseurl = $base_url . '/';
        } else {
            $this->baseurl = '';
        }

        $this->collection = ! empty(get_option('wc_cptbot_chroma_collection')) ? get_option('wc_cptbot_chroma_collection') : '';
        $this->apikey = ! empty(get_option('wc_cptbot_chroma_api_key')) ? get_option('wc_cptbot_chroma_api_key') : '';

        if (! empty($this->baseurl) && ! empty($this->collection)) {
            $this->ensure_collection_exists();
        }
    }

    /**
     * Ensure collection exists: try find by name, verify cached UUID, create if needed.
     * @return bool
     */
    public function ensure_collection_exists()
    {
        if (empty($this->baseurl) || empty($this->collection)) {
            return false;
        }

        $uuid = $this->get_collection_uuid_by_name($this->collection);
        if ($uuid) {
            update_option('wc_cptbot_chroma_collection_uuid', $uuid);
            $this->collection_uuid = $uuid;
            return true;
        }

        $cached = get_option('wc_cptbot_chroma_collection_uuid');
        if (! empty($cached)) {
            if ($this->verify_collection_exists_by_uuid($cached)) {
                $this->collection_uuid = $cached;
                return true;
            }

            delete_option('wc_cptbot_chroma_collection_uuid');
        }

        $created = $this->create_collection();
        if ($created && ! empty($this->collection_uuid)) {
            return true;
        }
        return false;
    }

    /**
     * Verify a collection UUID exists by listing collections and checking ids
     * @param string $uuid
     * @return bool
     */
    protected function verify_collection_exists_by_uuid($uuid)
    {
        if (empty($this->baseurl) || empty($uuid)) {
            return false;
        }

        $url = $this->baseurl . 'collections?tenant=default_tenant&database=default_database';
        $response = $this->wc_cptbot_request($url, 'GET');
        if (is_wp_error($response)) {
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);
        if (200 !== $code) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $collections = json_decode($body, true);
        if (isset($collections['collections']) && is_array($collections['collections'])) {
            $collections = $collections['collections'];
        }

        if (! is_array($collections)) {
            return false;
        }

        foreach ($collections as $col) {
            if (! empty($col['id']) && $col['id'] === $uuid) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get collection UUID by name using list endpoint
     * @param string $name
     * @return string|false
     */
    protected function get_collection_uuid_by_name($name)
    {
        if (empty($this->baseurl) || empty($name)) {
            return false;
        }

        $url = $this->baseurl . 'collections?tenant=default_tenant&database=default_database';
        $response = $this->wc_cptbot_request($url, 'GET');
        if (is_wp_error($response)) {
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);
        if (200 !== $code) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $collections = json_decode($body, true);

        if (isset($collections['collections']) && is_array($collections['collections'])) {
            $collections = $collections['collections'];
        }

        if (! is_array($collections)) {
            return false;
        }

        foreach ($collections as $collection) {
            if (! empty($collection['name']) && $collection['name'] === $name) {
                return $collection['id'];
            }
        }

        return false;
    }

    /**
     * Create a collection (without `get_or_create`) and handle possible conflict.
     * @return bool
     */
    protected function create_collection()
    {
        if (empty($this->baseurl) || empty($this->collection)) {
            return false;
        }

        $url = $this->baseurl . 'collections?tenant=default_tenant&database=default_database';

        $payload = array(
            'name' => $this->collection,
            'metadata' => array('hnsw:space' => 'cosine'),
        );

        $json_payload = wp_json_encode($payload);
        $response = $this->wc_cptbot_request($url, 'POST', $json_payload);

        if (is_wp_error($response)) {
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if (in_array($code, array(200, 201), true)) {
            $response_data = json_decode($body, true);

            if (is_array($response_data) && ! empty($response_data['id'])) {
                $uuid = $response_data['id'];
                update_option('wc_cptbot_chroma_collection_uuid', $uuid);
                $this->collection_uuid = $uuid;
                return true;
            }

            if (isset($response_data['collections']) && is_array($response_data['collections']) && ! empty($response_data['collections'][0]['id'])) {
                $uuid = $response_data['collections'][0]['id'];
                update_option('wc_cptbot_chroma_collection_uuid', $uuid);
                $this->collection_uuid = $uuid;
                return true;
            }

            return false;
        }

        // Conflict (already exists) — re-fetch by name
        if (409 === $code) {
            $uuid = $this->get_collection_uuid_by_name($this->collection);
            if ($uuid) {
                update_option('wc_cptbot_chroma_collection_uuid', $uuid);
                $this->collection_uuid = $uuid;
                return true;
            }
            return false;
        }

        return false;
    }

    /**
     * Internal HTTP request helper.
     * Only sets Content-Type for POST. Uses Authorization header when set.
     */
    protected function wc_cptbot_request(string $url, string $method = 'POST', string $body = '')
    {
        $headers = array();
        if ('POST' === strtoupper($method)) {
            $headers['Content-Type'] = 'application/json';
        }
        if (! empty($this->apikey)) {
            $headers['Authorization'] = 'Bearer ' . $this->apikey;
        }

        $request_args = array(
            'method'      => strtoupper($method),
            'headers'     => $headers,
            'timeout'     => 30,
            'redirection' => 5,
            'httpversion' => '1.1',
            'blocking'    => true,
            'sslverify'   => false,
        );

        if ('POST' === strtoupper($method) && '' !== $body) {
            $request_args['body'] = $body;
        }

        $response = wp_remote_request($url, $request_args);

        return $response;
    }

    /**
     * Upsert a single vector (keeps original semantics). Returns true on success.
     */
    public function upsert($vectorsdata, $index, $product_id, $promptdata = '', $force = false)
    {
        if (empty($this->baseurl) || empty($this->collection)) {
            return false;
        }

        if (empty($this->collection_uuid)) {
            $this->collection_uuid = get_option('wc_cptbot_chroma_collection_uuid');
            if (empty($this->collection_uuid)) {
                $this->collection_uuid = $this->get_collection_uuid_by_name($this->collection);
            }
        }

        if (empty($this->collection_uuid)) {
            return false;
        }

        $url = $this->baseurl . 'collections/' . rawurlencode($this->collection_uuid) . '/upsert?tenant=default_tenant&database=default_database';

        if (is_string($promptdata)) {
            $metadata = json_decode($promptdata, true);
            if (! is_array($metadata)) {
                $metadata = array('data' => $promptdata);
            }
        } else {
            $metadata = is_array($promptdata) ? $promptdata : array('data' => $promptdata);
        }

        $sanitized_metadata = array();
        foreach ($metadata as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $sanitized_metadata[$key] = wp_json_encode($value);
            } elseif (is_bool($value)) {
                $sanitized_metadata[$key] = $value ? 'true' : 'false';
            } elseif ($key === 'price_numeric' && is_numeric($value)) {
                $sanitized_metadata[$key] = floatval($value);
            } else {
                $sanitized_metadata[$key] = (string) $value;
            }
        }

        if (! is_array($vectorsdata)) {
            return false;
        }

        $vector_array = array_map('floatval', $vectorsdata);
        $embedding_count = count($vector_array);


        $vector_id = (string) ($index . '-' . $product_id);

        if ($force) {
            try {
                $this->delete(array($vector_id));
            } catch (\Exception $e) {
                // Continue even if delete fails
            }
        }

        $payload = array(
            'ids'        => array($vector_id),
            'metadatas'  => array($sanitized_metadata),
            'embeddings' => array($vector_array),
        );

        $json_payload = wp_json_encode($payload);
        $response = $this->wc_cptbot_request($url, 'POST', $json_payload);

        if (is_wp_error($response)) {
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);
        if (in_array($code, array(200, 201), true)) {
            return true;
        }

        return false;
    }

    /**
     * Query for nearest vectors with optional price filtering
     *
     * @param array $query_vectors_data Vector embeddings for similarity search
     * @param int $n_results Number of results to return
     * @param string|null $query_text Optional text query for hybrid search
     * @param array|null $price_filter Optional price filter array with 'min_price' and/or 'max_price'
     */
    public function query($query_vectors_data, $n_results = 10, $query_text = null, $price_filter = null, $chroma_where_filter = null)
    {
        $result = array();

        $n_results = (int) ($n_results ?: 10);
        if ($n_results <= 0) {
            $n_results = 10;
        }

        if (empty($this->baseurl) || empty($this->collection)) {
            $result['data'] = esc_html__('ChromaDB not configured', 'autonomous-ai-chatbot');
            return $result;
        }

        if (empty($this->collection_uuid)) {
            $this->collection_uuid = get_option('wc_cptbot_chroma_collection_uuid');
            if (empty($this->collection_uuid)) {
                $this->collection_uuid = $this->get_collection_uuid_by_name($this->collection);
            }
        }

        if (empty($this->collection_uuid)) {
            $result['data'] = esc_html__('ChromaDB collection not found', 'autonomous-ai-chatbot');
            return $result;
        }

        if (! is_array($query_vectors_data) || empty($query_vectors_data)) {
            $result['data'] = esc_html__('Invalid query data', 'autonomous-ai-chatbot');
            return $result;
        }

        $url = $this->baseurl . 'collections/' . rawurlencode($this->collection_uuid) . '/query?tenant=default_tenant&database=default_database';

        $where = ! empty($chroma_where_filter) ? $chroma_where_filter : $price_filter;

        if (is_string($where)) {
            $where = json_decode($where, true);
        }



        $payload = array(
            'query_embeddings' => array($query_vectors_data),
            'n_results'        => $n_results,
            'include'          => array('metadatas', 'distances', 'documents'),
        );

        if (! empty($where) && is_array($where)) {

            // Normalize stdClass to array (important)
            $where = json_decode(wp_json_encode($where), true);

            // Handle $and correctly
            if (
                isset($where['$and']) &&
                is_array($where['$and']) &&
                count($where['$and']) < 2
            ) {
                $payload['where'] = $where['$and'][0] ?? array();
            } else {
                $payload['where'] = $where;
            }
        }

        $json_payload = wp_json_encode($payload);

        $response = $this->wc_cptbot_request($url, 'POST', $json_payload);
        if (is_wp_error($response)) {
            $result['data'] = array('error' => $response->get_error_message());
            return $result;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        if (200 !== $code) {
            $result['data'] = array('error' => 'ChromaDB Error ' . $code . ': ' . $body);
            return $result;
        }

        $data = json_decode($body, true);
        if (isset($data['ids']) && isset($data['ids'][0]) && is_array($data['ids'][0])) {
            $ids   = $data['ids'][0];
            $dists = $data['distances'][0] ?? array();
            $metas = $data['metadatas'][0] ?? array();

            $matches = array();
            foreach ($ids as $i => $id) {
                $meta = $metas[$i] ?? array();
                if (is_string($meta)) {
                    $decoded = json_decode($meta, true);
                    $meta = is_array($decoded) ? $decoded : array('data' => $meta);
                }

                $distance = $dists[$i] ?? 0;
                $enriched_meta = $this->enrich_with_variants($meta);

                $matches[] = array(
                    'id'       => $id,
                    'score'    => $distance,
                    'metadata' => $enriched_meta,
                );
            }

            $result['data'] = $matches;
            $result['status'] = 'success';
            return $result;
        }

        $result['data'] = esc_html__('No matches found', 'autonomous-ai-chatbot');
        return $result;
    }

    /**
     * Enrich metadata with variant information for variable products
     *
     * @param array $metadata Product metadata from ChromaDB
     * @return array Enriched metadata with variant information
     */
    protected function enrich_with_variants($metadata)
    {

        foreach ($metadata as $key => $value) {
            if (strpos($key, 'variants_') === 0 && is_string($value)) {
                $decoded = json_decode($value, true);
                if (is_array($decoded)) {
                    $metadata[$key] = $decoded;
                }
            }
        }

        // Check if we already have decoded variant data
        $has_variant_data = false;
        foreach ($metadata as $key => $value) {
            if (strpos($key, 'variants_') === 0 && is_array($value) && ! empty($value)) {
                $has_variant_data = true;
                break;
            }
        }

        // If we already have variant data, return early
        if ($has_variant_data) {
            return $metadata;
        }

        // Otherwise, try to fetch variant data from the product
        $product_id = ! empty($metadata['ID']) ? (int) $metadata['ID'] : null;

        if (! $product_id || ! function_exists('wc_get_product')) {
            return $metadata;
        }

        $product = wc_get_product($product_id);
        if (! $product) {
            return $metadata;
        }

        if (empty($metadata['product_type'])) {
            $metadata['product_type'] = $product->get_type();
        }

        if ('variable' === $product->get_type()) {
            $variants = $product->get_available_variations();

            if (! empty($variants)) {
                $variation_attributes = array();

                foreach ($variants as $idx => $variation) {
                    if (isset($variation['attributes']) && is_array($variation['attributes'])) {
                        $atts = $variation['attributes'];

                        foreach ($atts as $attr_key => $attr_val) {
                            // Clean up the attribute key
                            $clean_key = str_replace('attribute_', '', $attr_key);
                            $clean_key = str_replace('pa_', '', $clean_key);
                            $clean_key = ucfirst(str_replace(array('-', '_'), ' ', $clean_key));

                            if (! isset($variation_attributes[$clean_key])) {
                                $variation_attributes[$clean_key] = array();
                            }

                            if (! empty($attr_val) && ! in_array($attr_val, $variation_attributes[$clean_key], true)) {
                                $variation_attributes[$clean_key][] = $attr_val;
                            }
                        }
                    }

                    if (isset($variation['variation_id'])) {
                        $variant_obj = wc_get_product($variation['variation_id']);

                        if ($variant_obj) {
                            $var_attributes = $variant_obj->get_attributes();

                            foreach ($var_attributes as $attr_name => $attr_value) {
                                $clean_key = str_replace('attribute_', '', $attr_name);
                                $clean_key = str_replace('pa_', '', $clean_key);
                                $clean_key = ucfirst(str_replace(array('-', '_'), ' ', $clean_key));

                                if (! isset($variation_attributes[$clean_key])) {
                                    $variation_attributes[$clean_key] = array();
                                }

                                if (! empty($attr_value) && ! in_array($attr_value, $variation_attributes[$clean_key], true)) {
                                    $variation_attributes[$clean_key][] = $attr_value;
                                }
                            }
                        }
                    }
                }

                // Add all collected variation attributes to metadata
                if (! empty($variation_attributes)) {
                    foreach ($variation_attributes as $attr_name => $attr_values) {
                        $metadata_key = 'variants_' . strtolower(str_replace(' ', '_', $attr_name));
                        $metadata[$metadata_key] = $attr_values;
                    }
                }
            }
        }

        return $metadata;
    }

    /**
     * Delete vectors by ids
     */
    public function delete($ids)
    {
        if (empty($this->baseurl) || empty($this->collection)) {
            return false;
        }

        if (empty($this->collection_uuid)) {
            $this->collection_uuid = get_option('wc_cptbot_chroma_collection_uuid');
            if (empty($this->collection_uuid)) {
                $this->collection_uuid = $this->get_collection_uuid_by_name($this->collection);
            }
        }

        if (empty($this->collection_uuid)) {
            return false;
        }

        $url = $this->baseurl . 'collections/' . rawurlencode($this->collection_uuid) . '/delete?tenant=default_tenant&database=default_database';
        $payload = array('ids' => is_array($ids) ? $ids : array($ids));
        $response = $this->wc_cptbot_request($url, 'POST', wp_json_encode($payload));

        if (is_wp_error($response)) {
            return 'Error: ' . $response->get_error_message();
        }

        $code = wp_remote_retrieve_response_code($response);
        if (200 !== $code) {
            return 'Error: HTTP ' . $code . ' - ' . wp_remote_retrieve_body($response);
        }

        return wp_remote_retrieve_body($response);
    }

    /**
     * Detect possible data loss (cached UUID exists but collection missing)
     * @return bool
     */
    public function detect_data_loss()
    {
        if (empty($this->baseurl) || empty($this->collection)) {
            return false;
        }

        $cached_uuid = get_option('wc_cptbot_chroma_collection_uuid');
        $trained_products = get_posts(array(
            'post_type' => 'product',
            'meta_key' => 'cptbot_product_data', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
            'posts_per_page' => 1,
        ));

        if (empty($cached_uuid) || empty($trained_products)) {
            return false;
        }

        $uuid_exists = $this->verify_collection_exists_by_uuid($cached_uuid);
        if (! $uuid_exists) {
            return true;
        }

        return false;
    }

    /**
     * Recover from data loss: clear cached UUID and re-create/find collection
     */
    public function recover_from_data_loss()
    {
        delete_option('wc_cptbot_chroma_collection_uuid');
        $this->collection_uuid = '';
        $this->ensure_collection_exists();

        if (empty($this->collection_uuid)) {
            return array('success' => false, 'message' => 'Failed to establish collection');
        }

        return array('success' => true, 'message' => 'Collection recovered. Products must be re-synchronized.', 'collection_uuid' => $this->collection_uuid);
    }

    /**
     * Extract and enhance metadata with category IDs and tags for better filtering.
     *
     * @param array $metadata Original metadata array from product embedding.
     * @param int   $product_id WooCommerce product ID.
     * @return array Enhanced metadata with extracted category IDs and tags.
     */
    public function enhance_metadata_with_categories_and_tags($metadata, $product_id)
    {
        if (! is_array($metadata) || ! function_exists('wc_get_product')) {
            return $metadata;
        }

        $product = wc_get_product($product_id);
        if (! $product) {
            return $metadata;
        }

        $categories_term = get_the_terms($product_id, 'product_cat');
        $category_ids = array();
        $category_names = array();

        if (! empty($categories_term) && ! is_wp_error($categories_term)) {
            foreach ($categories_term as $term) {
                $category_ids[] = (int) $term->term_id;
                $category_names[] = $term->name;
            }
        }

        $tags_term = get_the_terms($product_id, 'product_tag');
        $product_tags = array();

        if (! empty($tags_term) && ! is_wp_error($tags_term)) {
            foreach ($tags_term as $term) {
                $product_tags[] = $term->name;
            }
        }

        if (! empty($category_ids)) {
            $metadata['category_ids'] = wp_json_encode($category_ids);
            $metadata['category_names'] = implode('|', $category_names);
        }

        if (! empty($product_tags)) {
            $metadata['product_tags'] = implode('|', $product_tags);
        }

        $metadata['product_type'] = $product->get_type();

        // Add variant editions and formats for variable products
        if ('variable' === $product->get_type()) {
            $variants = $product->get_available_variations();

            if (! empty($variants)) {

                // Log complete product attributes
                $product_attributes = $product->get_attributes();

                // Collect ALL variation attributes dynamically (not hardcoded)
                $variation_attributes = array(); // Format: ['attribute_name' => ['value1', 'value2', ...]]

                foreach ($variants as $variation) {
                    $has_non_empty_attrs = false;

                    // Collect attributes from variation array
                    if (isset($variation['attributes']) && is_array($variation['attributes'])) {
                        $atts = $variation['attributes'];

                        foreach ($atts as $attr_key => $attr_val) {
                            // Clean up the attribute key (remove 'attribute_' prefix if present)
                            $clean_key = str_replace('attribute_', '', $attr_key);
                            $clean_key = str_replace('pa_', '', $clean_key); // Remove taxonomy prefix
                            $clean_key = ucfirst(str_replace(array('-', '_'), ' ', $clean_key)); // Make it readable

                            // Initialize array for this attribute if not exists
                            if (! isset($variation_attributes[$clean_key])) {
                                $variation_attributes[$clean_key] = array();
                            }

                            // Add value if not empty and not already in array
                            if (! empty($attr_val) && ! in_array($attr_val, $variation_attributes[$clean_key], true)) {
                                $variation_attributes[$clean_key][] = $attr_val;
                                $has_non_empty_attrs = true;
                            }
                        }
                    }

                    // Also try to get from variation object for completeness
                    if (isset($variation['variation_id'])) {
                        $variant_obj = wc_get_product($variation['variation_id']);

                        if ($variant_obj) {
                            $var_attributes = $variant_obj->get_attributes();

                            foreach ($var_attributes as $attr_name => $attr_value) {
                                // Clean up the attribute name
                                $clean_key = str_replace('attribute_', '', $attr_name);
                                $clean_key = str_replace('pa_', '', $clean_key);
                                $clean_key = ucfirst(str_replace(array('-', '_'), ' ', $clean_key));

                                // Initialize array for this attribute if not exists
                                if (! isset($variation_attributes[$clean_key])) {
                                    $variation_attributes[$clean_key] = array();
                                }

                                // Add value if not empty and not already in array
                                if (! empty($attr_value) && ! in_array($attr_value, $variation_attributes[$clean_key], true)) {
                                    $variation_attributes[$clean_key][] = $attr_value;
                                    $has_non_empty_attrs = true;
                                }
                            }
                        }
                    }

                    // FALLBACK: If variation attributes are empty, collect from parent product attribute options
                    if (! $has_non_empty_attrs && isset($variation['attributes']) && is_array($variation['attributes'])) {

                        foreach ($product_attributes as $attr_name => $attr_obj) {
                            if (is_object($attr_obj) && method_exists($attr_obj, 'get_options')) {
                                $clean_key = ucfirst(str_replace(array('-', '_'), ' ', str_replace('pa_', '', $attr_name)));
                                $options = $attr_obj->get_options();

                                // Initialize array for this attribute if not exists
                                if (! isset($variation_attributes[$clean_key])) {
                                    $variation_attributes[$clean_key] = array();
                                }

                                // Add all parent product attribute options as possible variations
                                if (! empty($options) && is_array($options)) {
                                    foreach ($options as $option) {
                                        if (! empty($option) && ! in_array($option, $variation_attributes[$clean_key], true)) {
                                            $variation_attributes[$clean_key][] = $option;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                // Add all collected variation attributes to metadata
                if (! empty($variation_attributes)) {
                    foreach ($variation_attributes as $attr_name => $attr_values) {
                        $metadata_key = 'variants_' . strtolower(str_replace(' ', '_', $attr_name));
                        $metadata[$metadata_key] = wp_json_encode($attr_values);
                    }
                }
            }
        }

        return $metadata;
    }
}
