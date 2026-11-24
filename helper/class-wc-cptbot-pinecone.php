<?php
/**
 * PineCone Api Handler.
 *
 * @package Chatgpt bot for WooCommerce
 * @version 1.0.0
 */

namespace WCAICHATBOT\Helper;

/**
 * Class Wc_Cptbot_Pinecone.
 * Handles interactions with the Pinecone service for vector indexing and querying.
 */
class Wc_Cptbot_Pinecone {



	/**
	 * The secret key used for authentication with the Pinecone API.
	 *
	 * @var string
	 */
	protected $secretkey;

	/**
	 * The base URL for making requests to the Pinecone API.
	 *
	 * @var string
	 */
	protected $baseurl;

	/**
	 * The environment where the Pinecone API is hosted.
	 *
	 * @var string
	 */
	protected $environment;

	/**
	 * The name of the index in the Pinecone database.
	 *
	 * @var string
	 */
	protected $indexname;

	/**
	 * Constructs a new instance of the class.
	 */
	public function __construct() {
		$vectordbindex     = get_option( 'wc_cptbot_pinecone_index_host' );
		if ( empty( $vectordbindex ) ) {
			$vectordbindex = get_option( 'wc_cptbot_pinecone_host' );
		}
		$this->environment = ! empty( get_option( 'wc_cptbot_pinecone_env' ) ) ? get_option( 'wc_cptbot_pinecone_env' ) : 'us-west4-gcp-free';
		$this->secretkey   = ! empty( get_option( 'wc_cptbot_pinecone_key' ) ) ? get_option( 'wc_cptbot_pinecone_key' ) : '';

		// Ensure baseurl doesn't have double https:// and ends with a slash
		if ( ! empty( $vectordbindex ) ) {
			$vectordbindex = str_replace( array( 'https://', 'http://' ), '', $vectordbindex );
			$this->baseurl = 'https://' . rtrim( $vectordbindex, '/' ) . '/';
		} else {
			$this->baseurl = '';
		}

		$this->indexname = ! empty( get_option('wc_cptbot_pinecone_indexname') ) ? get_option('wc_cptbot_pinecone_indexname') : 'chatgpt';
	}

	/**
	 * Performs a cURL request.
	 *
	 * @param  string $url        The URL to send the request to.
	 * @param  string $type       The HTTP request type (default: 'POST').
	 * @param  string $postfields The data to send with the request (default: empty string).
	 * @return array|mixed       The response from the cURL request.
	 */
	public function wc_cptbot_request( string $url, string $type = 'POST', string $postfields = '' ) {
		$request_args = array(
			'headers'     => array(
				'Content-Type' => 'application/json',
				'Api-Key'      => $this->secretkey,
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
			$response               = wp_remote_post( $url, $request_args ); // Make the POST request using wp_remote_post.
		} else {
			$request_args['method'] = 'GET';
			$response               = wp_remote_request( $url, $request_args ); // Make the POST request using wp_remote_request.
		}
		return $response;
	}

	public function wc_cptbot_request_updated( string $url, array $vectors, string $namespace = '' ) {

		$body = array(
			'vectors'   => $vectors,
		);

		if ( ! empty( $namespace ) ) {
			$body['namespace'] = $namespace;
		}

		$request_args = array(
			'headers' => array(
				'Content-Type'             => 'application/json',
				'Api-Key'                  => $this->secretkey,
			),
			'timeout'     => 30,
			'redirection' => 10,
			'httpversion' => '1.1',
			'blocking'    => true,
			'method'      => 'POST',
			'sslverify'   => false,
			'body'        => wp_json_encode( $body ), // Encode body to JSON
		);

		$response = wp_remote_post( $url, $request_args );

		return $response;
	}



	/**
	 * Upserts vectors data into the specified index.
	 *
	 * @param  array  $vectorsdata   The vectors data to upsert.
	 * @param  string $index         The index name.
	 * @param  string $product_id    The product ID.
	 * @param  string $promptdata    The product meta data.
	 * @return int|false             The number of upserted vectors or false if unsuccessful.
	 */
	public function upsert( $vectorsdata, $index, $product_id, $promptdata = '' ) {
		$url = $this->baseurl . 'vectors/upsert';

		$response = $this->wc_cptbot_request_updated( $url,
			array(
				array(
					'id'       => $index . '-' . $product_id,
					'metadata' => $promptdata,
					'values'   => $vectorsdata,
				)
			)
		);

		if ( is_wp_error( $response ) ) {
			$error_msg = $response->get_error_message();
			$result = array( 'error' => esc_html__( 'Error', 'autonomous-ai-chatbot' ) . ' #: ' . $error_msg );
		} else {
			$response_code = wp_remote_retrieve_response_code( $response );
			$response_body = wp_remote_retrieve_body( $response );
			if ( 200 === $response_code ) {
				$result = json_decode( $response_body, true );
			} else {
				$result = array( 'error' => esc_html__( 'Error', 'autonomous-ai-chatbot' ) . ' #: ' . $response_code );
			}
		}
		return $result['upsertedCount'] ?? false; // Return the number of upserted vectors or false if unsuccessful.
	}


	public function to_array_recursive( $data ) {
    if ( is_object( $data ) ) {
        $data = (array) $data;
    }

    if ( is_array( $data ) ) {
        foreach ( $data as $k => $v ) {
            $data[$k] = $this->to_array_recursive( $v );
        }
    }

    return $data;
}





	/**
	 * Normalize filter for Pinecone compatibility (supports $and/$or + single rules)
	 */
	public function normalize_filter_for_pinecone( $filter ) {

    // Convert stdClass → array if needed
    $filter = $this->to_array_recursive( $filter );

    // Unwrap single-item $and / $or
    if ( isset( $filter['$and'] ) && is_array( $filter['$and'] ) && count( $filter['$and'] ) === 1 ) {
        $filter = $filter['$and'][0];
    }

    if ( isset( $filter['$or'] ) && is_array( $filter['$or'] ) && count( $filter['$or'] ) === 1 ) {
        $filter = $filter['$or'][0];
    }

    $normalized = array();

    foreach ( $filter as $key => $value ) {

        // Logical operators — recurse
        if ( $key === '$and' || $key === '$or' ) {
            $normalized[$key] = array_map(
                array( $this, 'normalize_filter_for_pinecone' ),
                $value
            );
            continue;
        }

        // Already operator object → keep
        if ( is_array( $value ) ) {
            $normalized[$key] = $value;
        }
        // Implicit equality → convert
        else {
            $normalized[$key] = array( '$eq' => $value );
        }
    }

    return $normalized;
}





	/**
	 * Executes a query using the specified query vectors data.
	 *
	 * @param  array $query_vectors_data The query vectors data.
	 *
	 * @return array The query result, including the matches and status.
	 */
	public function query( $query_vectors_data, $top_k = 10,$filter = null ) {
		$top_k = ! empty( $top_k ) ? $top_k : 10;

		$requestbody = array(
			'vector'          => $query_vectors_data,
			'topK'            => $top_k,
			'includeMetadata' => true,
		);



		if ( ! empty( $filter ) ) {
			// Normalize filter for Pinecone compatibility
			$requestbody['filter'] = $this->normalize_filter_for_pinecone( $filter );
		}

		$postfields = wp_json_encode( $requestbody ); // Convert the request body to JSON.

		$url = $this->baseurl . 'query';

		$response = $this->wc_cptbot_request( $url, 'POST', $postfields ); // Perform the cURL request to execute the query.
		if ( is_wp_error( $response ) ) {
			$body = array( 'error' => esc_html__( 'Error', 'autonomous-ai-chatbot' ) . ' #: ' . $response->get_error_message() );
		} else {
			$response_code = wp_remote_retrieve_response_code( $response );
			if ( 200 === $response_code ) {
				$response_body = wp_remote_retrieve_body( $response );
				$body          = json_decode( $response_body, true );
			} else {
				$body = array( 'error' => esc_html__( 'Error', 'autonomous-ai-chatbot' ) . ' #: ' . wp_remote_retrieve_response_message( $response ) );
			}
		}

		$result = array();
		if ( $body ) {
			if ( isset( $body['matches'] ) && is_array( $body['matches'] ) && count( $body['matches'] ) ) {
				$result['data']   = $body['matches'];
				$result['status'] = 'success';
			} else {
				if ( isset( $body['error'] ) ) {
					$result['data'] = $body['error'];
				}
			}
		} else {
			$result['data'] = esc_html__( 'Pinecone return empty', 'autonomous-ai-chatbot' );
		}
		return $result; // Return the query result, including the matches and status.
	}

	/**
	 * Creates a new index in Pinecone.
	 *
	 * @return string  The result message indicating the success or failure of index creation.
	 */
	public function create_index() {
		$index_name  = $this->indexname;
		$model_type  = get_option( 'cpt_bot_model_type', 'onnx' );

		$dimension = 384;

		$url = esc_url( 'https://api.pinecone.io/indexes' );

		$data = array(
			'name'      => $index_name,
			'dimension' => $dimension,
			'metric'    => 'cosine',
			'spec'      => array(
				'serverless' => array(
					'cloud'  => 'aws',
					'region' => 'us-east-1',
				),
			),
		);

		$postfields = wp_json_encode( $data ); // Convert the request body to JSON.

		$response = $this->wc_cptbot_request( $url, 'POST', $postfields ); // Make the request using wp_remote_request.

		if ( is_wp_error( $response ) ) {
			return esc_html__( 'Error creating index. WP Error: ', 'autonomous-ai-chatbot' ) . $response->get_error_message();
		} else {
			$response_code = wp_remote_retrieve_response_code( $response );
			$response_body = wp_remote_retrieve_body( $response );

			if ( 201 === $response_code ) {
				return esc_html__( 'Index created successfully.', 'autonomous-ai-chatbot' );
			} else {
				return esc_html__( 'Error creating index. HTTP code: ', 'autonomous-ai-chatbot' ) . $response_code . esc_html__( ' Response: ', 'autonomous-ai-chatbot' ) . $response_body;
			}
		}
	}


	/**
	 * Retrieves the list of available indexes from Pinecone.
	 *
	 * @return mixed|string  The response containing the list of indexes or an error message.
	 */
	public function indexlisting() {
		$environment = $this->environment;

		// $url = esc_url( "https://controller.$environment.pinecone.io/databases" ); // The URL for retrieving the list of indexes.
		$url = esc_url( 'https://api.pinecone.io/indexes' ); // The URL for retrieving the list of indexes.

		$response      = $this->wc_cptbot_request( $url, 'GET' ); // Make the request using wp_remote_request.
		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		return array(
			'code' => $response_code,
			'data' => $response_body,
		);
	}

	/**
	 * Retrieves the details of a specific index from Pinecone.
	 *
	 * @return mixed|string  The response containing the index details or an error message.
	 */
	public function describe_listing() {

		$environment = $this->environment;
		$index_name  = $this->indexname;

		// $url      = esc_url( "https://controller.$environment.pinecone.io/databases/$index_name" ); // The URL for retrieving the details of a specific index.
		$url      = esc_url( "https://api.pinecone.io/indexes/$index_name" ); // The URL for retrieving the details of a specific index.
		$response = $this->wc_cptbot_request( $url, 'GET' ); // Make the request using wp_remote_request.
		if ( is_wp_error( $response ) ) {
			return array(
				'code'     => 500,
				'message'  => esc_html__( 'Error making the request. WP Error: ', 'autonomous-ai-chatbot' ) . $response->get_error_message(),
				'response' => '',
			);
		} else {
			$response_code = wp_remote_retrieve_response_code( $response );
			$response_body = wp_remote_retrieve_body( $response );

			if ( 200 === $response_code ) {
				return array(
					'code'     => $response_code,
					'message'  => esc_html__( 'Success', 'autonomous-ai-chatbot' ),
					'response' => $response_body,
				);
			} else {
				return array(
					'code'     => $response_code,
					'message'  => esc_html__( 'Error making the request. HTTP code: ', 'autonomous-ai-chatbot' ),
					'response' => $response_body,
				);
			}
		}
	}

	public function delete( $ids ) {
		$base_url = $this->baseurl . 'vectors/delete';

		$data = array(
			'deleteAll' => false,
			'ids'       => is_array( $ids ) ? $ids : array( $ids ),
		);

		$response = $this->wc_cptbot_request( $base_url, 'POST', json_encode( $data ) ); // Make the request using wp_remote_request.

		if ( is_wp_error( $response ) ) {
			return 'Error: ' . $response->get_error_message();
		}

		return wp_remote_retrieve_body( $response );
	}

	/**
	 * Deletes all vectors from the index.
	 *
	 * @return string|array The response from the Pinecone API.
	 */
	public function delete_all_vectors() {
		$base_url = $this->baseurl . 'vectors/delete';

		$data = array(
			'deleteAll' => true,
		);

		$response = $this->wc_cptbot_request( $base_url, 'POST', json_encode( $data ) );

		if ( is_wp_error( $response ) ) {
			return 'Error: ' . $response->get_error_message();
		}

		return wp_remote_retrieve_body( $response );
	}

}
