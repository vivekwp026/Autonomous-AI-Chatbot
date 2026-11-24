<?php

namespace WCAICHATBOT\Helper;

/**
 * Class Wc_Cptbot_Gemini.
 */
class Wc_Cptbot_Gemini {

	protected $api_key;
	protected $embedding_model;

	public function __construct() {
		$this->api_key         = get_option( 'wc_cptbot_gemini_api_key', '' );
		$this->embedding_model = get_option( 'wc_cptbot_gemini_embedding_model', 'models/text-embedding-004' );
	}

	private function _curl( string $url, string $type = 'POST', string $postfields = '' ) {
		$request_args = array(
			'headers'     => array(
				'Content-Type' => 'application/json',
			),
			'timeout'     => 30,
			'redirection' => 10,
			'httpversion' => '1.1',
			'blocking'    => true,
			'sslverify'   => false,
		);

		if ( 'POST' === $type ) {
			$request_args['method'] = 'POST';
			$request_args['body']   = $postfields;
			return wp_remote_post( $url, $request_args );
		}

		$request_args['method'] = 'GET';
		return wp_remote_request( $url, $request_args );
	}

	public function wc_cptbot_create_embedding( $prompt ) {
		if ( empty( $this->api_key ) ) {
			return array( 'data' => array() );
		}

		$model = ! empty( $this->embedding_model ) ? $this->embedding_model : 'models/text-embedding-004';

		// Gemini API expects "models/" prefix usually, but let's handle if user just types "text-embedding-004"
		if ( strpos( $model, 'models/' ) === false ) {
			$model = 'models/' . $model;
		}

		// phpcs:ignore PluginCheck.CodeAnalysis.AIProvider.DirectIntegration
		$url = "https://generativelanguage.googleapis.com/v1beta/{$model}:embedContent?key={$this->api_key}";

		$payload = array(
			'content' => array(
				'parts' => array(
					array(
						'text' => $prompt,
					),
				),
			),
			'outputDimensionality' => 384,
		);

		$postfields = wp_json_encode( $payload );

		$response = $this->_curl( $url, 'POST', $postfields );

		if ( is_wp_error( $response ) ) {
			return array( 'data' => array() );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( 200 === $response_code ) {
			$result = json_decode( $response_body, true );
			if ( isset( $result['embedding']['values'] ) ) {
				// Normalize to match OpenAI/ONNX structure: array('data' => array( array('embedding' => ... ) ) )
				// Wait, OpenAI returns { data: [ { embedding: [...] } ] }
				// The calling code expects $result['data'][0]['embedding']
				return array(
					'data' => array(
						array(
							'embedding' => $result['embedding']['values'],
						),
					),
				);
			}
		}

		return array( 'data' => array() );
	}

	public function chat( $parameters ) {
		if ( empty( $this->api_key ) ) {
			return 'Gemini API key is not configured.';
		}

		$model = get_option( 'wc_cptbot_gemini_chat_model', 'gemini-1.5-flash' );

		// Handle models/ prefix
		if ( strpos( $model, 'models/' ) === false ) {
			$model = 'models/' . $model;
		}

		// phpcs:ignore PluginCheck.CodeAnalysis.AIProvider.DirectIntegration
		$url = "https://generativelanguage.googleapis.com/v1beta/{$model}:generateContent?key={$this->api_key}";

		$contents = array();
		$system_instruction = '';

		if ( ! empty( $parameters['messages'] ) ) {
			foreach ( $parameters['messages'] as $message ) {
				if ( 'system' === $message['role'] ) {
					$system_instruction = $message['content'];
				} else {
					$role = ( 'assistant' === $message['role'] ) ? 'model' : 'user';
					$contents[] = array(
						'role'  => $role,
						'parts' => array(
							array( 'text' => $message['content'] ),
						),
					);
				}
			}
		}

		$payload = array(
			'contents' => $contents,
		);

		if ( ! empty( $system_instruction ) ) {
			$payload['system_instruction'] = array(
				'parts' => array(
					array( 'text' => $system_instruction ),
				),
			);
		}

		// Add generation config if needed
		$payload['generationConfig'] = array(
			'temperature' => $parameters['temperature'] ?? 0.7,
			'maxOutputTokens' => $parameters['max_tokens'] ?? 1000,
		);

		$postfields = wp_json_encode( $payload );
		$response   = $this->_curl( $url, 'POST', $postfields );

		if ( is_wp_error( $response ) ) {
			return 'Error #: ' . $response->get_error_message();
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( 200 === $response_code ) {
			$result = json_decode( $response_body, true );
			if ( isset( $result['candidates'][0]['content']['parts'][0]['text'] ) ) {
				return $result['candidates'][0]['content']['parts'][0]['text'];
			}
			return 'No response from Gemini.';
		} else {
			return 'HTTP Error ' . $response_code . ': ' . $response_body;
		}
	}
}
