<?php

namespace WCAICHATBOT\Helper;

class Wc_Cptbot_Openai
{

	protected $secretkey;
	protected $baseurl;
	protected $defaultengine;

	public function __construct()
	{
		$this->secretkey     = get_option('wc_cptbot_api_key', '');
		$this->baseurl       = get_option('wc_cptbot_api_base_url', '');
		$this->defaultengine = get_option('wc_cptbot_model_name', '');
	}

	public function set_default_engine(string $defaultengine)
	{
		// Deprecated – kept for backward compatibility
	}

	/**
	 * Wrapper for wp_remote_request with retry logic for 429 errors.
	 *
	 * @param string $url The URL to request.
	 * @param array $args The request arguments.
	 * @return array|\WP_Error The response or WP_Error on failure.
	 */
	private function _remote_request_with_retry($url, $args) {
		$max_retries = 3;
		$retry_delay = 2; // Start with 2 seconds

		// Initialize response to a safe default in case loop doesn't run (unlikely)
		$response = new \WP_Error('unknown_error', 'Request failed to start');

		for ($i = 0; $i <= $max_retries; $i++) {
			$response = wp_remote_request($url, $args);

			if (is_wp_error($response)) {
				// Return immediately on network errors (DNS, etc) usually not worth retrying immediately
				// unless we want to handle timeouts.
				return $response; 
			}

			$response_code = wp_remote_retrieve_response_code($response);

			if (429 === $response_code) {
				// Hit rate limit. Wait and retry.
				if ($i < $max_retries) {
					sleep($retry_delay * pow(2, $i)); // Exponential backoff: 2, 4, 8 seconds
					continue;
				}
			}

			// If not 429, or we ran out of retries, return the response.
			return $response;
		}
		
		return $response;
	}

	private function _curl(string $url, string $type = 'POST', string $postfields = '')
	{
		$request_args = array(
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $this->secretkey,
			),
			'timeout'     => 60,
			'redirection' => 10,
			'httpversion' => '1.1',
			'blocking'    => true,
		);

		if ('POST' === $type) {
			$request_args['method'] = 'POST';
			$request_args['body']   = $postfields;
		} else {
			$request_args['method'] = 'GET';
		}
		
		// Use the retry logic
		$response = $this->_remote_request_with_retry($url, $request_args);

		return $response;
	}

	public function complete($parameters)
	{
		return '';
	}

	public function chat($parameters)
	{

		if (empty($this->secretkey)) {
			return 'API key is not configured.';
		}
		if (empty($this->baseurl)) {
			return 'API Base URL is not configured.';
		}

		if (empty($parameters['model'])) {
			$parameters['model'] = $this->defaultengine;
		}

		if (empty($parameters['model'])) {
			return 'Model name is not configured.';
		}

		$postfields = wp_json_encode($parameters);
		$url        = rtrim($this->baseurl, '/') . '/chat/completions';

		$response = $this->_curl($url, 'POST', $postfields);

		if (is_wp_error($response)) {
			return 'Error #: ' . $response->get_error_message();
		}

		$response_code = wp_remote_retrieve_response_code($response);
		$response_body = wp_remote_retrieve_body($response);

		if (200 === $response_code) {
			$result = json_decode($response_body, true);

			if (json_last_error() !== JSON_ERROR_NONE) {
				return 'Invalid JSON response: ' . $json_last_error_msg();
			}
		} else {
			return 'HTTP Error ' . $response_code . ': ' . $response_body;
		}

		if (isset($result['choices'][0]['message']['content'])) {
			return $result['choices'][0]['message']['content'];
		}

		return $result['error']['message'] ?? 'No response';
	}

	public function wc_cptbot_create_embedding($prompt, $dimensions = null)
	{
		$embedding_api_key = get_option('wc_cptbot_openai_embedding_api_key');
		$embedding_model   = get_option('wc_cptbot_openai_embedding_model');
		$model_type        = get_option('cpt_bot_model_type');

		// If OpenAI embedding model is selected and specific key is provided, use it.
		// Otherwise fallback to main key if model type is openai (or if not specified but we are here).

		$api_key = $this->secretkey;
		$model   = 'text-embedding-3-small';

		if ( 'openai' === $model_type && ! empty( $embedding_api_key ) ) {
			$api_key = $embedding_api_key;
		}

		if ( 'openai' === $model_type && ! empty( $embedding_model ) ) {
			$model = $embedding_model;
		}

		if (empty($api_key) || empty($this->baseurl)) {
			return array('data' => array());
		}

		$parameters = array(
			'model' => $model,
			'input' => $prompt,
		);

		if ( ! empty( $dimensions ) ) {
			$parameters['dimensions'] = (int) $dimensions;
		}

		$postfields = wp_json_encode($parameters);
		// phpcs:ignore PluginCheck.CodeAnalysis.AIProvider.DirectIntegration
		$openai_url = "https://api.openai.com/v1/embeddings";
		$url        = rtrim($this->baseurl, '/') . '/embeddings';

		// We need to use a custom curl call here if the API key is different,
		// because _curl uses $this->secretkey.
		// So let's temporarily swap the key or pass it to _curl if we modify _curl.
		// Easier to just duplicate the curl logic or modify _curl to accept a key.

		// Let's modify _curl to accept headers or key?
		// Or just manually do it here.

		$request_args = array(
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $api_key,
			),
			'timeout'     => 60,
			'redirection' => 10,
			'httpversion' => '1.1',
			'blocking'    => true,
			'method'      => 'POST',
			'body'        => $postfields,
		);

		$response = $this->_remote_request_with_retry($openai_url, $request_args);

		if (is_wp_error($response)) {
			return array('data' => array());
		}

		$response_code = wp_remote_retrieve_response_code($response);
		$response_body = wp_remote_retrieve_body($response);

		if (200 === $response_code) {
			return json_decode($response_body, true);
		} else {
			return array('data' => array());
		}
	}
}
