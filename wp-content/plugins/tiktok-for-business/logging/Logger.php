<?php
/**
 * Copyright (c) Bytedance, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package TikTok
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Class responsible for logging
 */
class Logger {

	/**
	 * The log_file_name.
	 *
	 * @var string
	 */
	protected $log_file_name;

	/**
	 * The wc_get_logger interface.
	 *
	 * @var WC_Logger_Interface
	 */
	protected $logger;

	/**
	 * The constructor
	 *
	 * @param WC_Logger_Interface $logger The wc_get_logger interface.
	 */
	public function __construct( WC_Logger_Interface $logger ) {
		$this->logger        = $logger;
		$this->log_file_name = 'tiktok_for_woocommerce';
	}

	/**
	 * Log errors and debugging messages
	 *
	 * @param string $method  The class/function where the message occurred.
	 * @param string $message The message to be logged.
	 * @param string $level   The level/context of the message.
	 *
	 * @return void
	 */
	public function log( $method, $message, $level = 'debug' ) {
		$handler = [ 'source' => $this->log_file_name ];
		$info    = "{$method}: {$message}";
		$this->logger->log( $level, $info, $handler );
	}

	/**
	 * Helper for Logging API requests.
	 *
	 * @param string $url   The URL of the request.
	 * @param array  $args  The Arguments of the request.
	 * @param string $level The default level/context of the message to be logged.
	 *
	 * @return void
	 */
	public function log_request( $url, $args, $level = 'debug' ) {
		$method = isset( $args['method'] ) ? $args['method'] : 'POST';
		$data   = isset( $args['body'] ) ? $args['body'] : '--- EMPTY STRING ---';
		self::log( $method, "{$url}\n\n{$data}\n", $level );

		if ( 'POST' === $method && false === is_admin() ) {
			// Only want to console log pixel fire
			?>
			<script>
				console.log("TikTok pixel fire.");
				console.log(<?php echo json_encode( $data ); ?>);
			</script>
			<?php
		}
	}

	/**
	 *  Helper for Logging API responses.
	 *
	 * @param string $method   The method where the log occurred.
	 * @param array  $response The body of the response.
	 * @param string $level    The default level/context of the message to be logged.
	 *
	 * @return void
	 */
	public function log_response( $method, $response, $level = 'debug' ) {
		if ( is_wp_error( $response ) ) {
			$level = 'error';
			$data  = "{$response->get_error_code()}: {$response->get_error_message()}";
		} else {
			$data = $response['http_response']->get_response_object()->raw;
		}

		self::log( $method, 'Response: ' . "\n\n" . $data . "\n", $level );
	}
}

