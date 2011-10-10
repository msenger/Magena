<?php

	/**
	 * Copyright (c) 2008- Samuli Järvelä
	 *
	 * All rights reserved. This program and the accompanying materials
	 * are made available under the terms of the Eclipse Public License v1.0
	 * which accompanies this distribution, and is available at
	 * http://www.eclipse.org/legal/epl-v10.html. If redistributing this code,
	 * this entire header must remain intact.
	 */

	class ResponseHandler {
		private static $ERRORS = array(
			"UNAUTHORIZED" => array(100, "Unauthorized request", 401), 
			"INVALID_REQUEST" => array(101, "Invalid request", 403),
			"FEATURE_DISABLED" => array(104, "Feature disabled", 403),
			"INVALID_CONFIGURATION" => array(105, "Invalid configuration", 403),
			"FEATURE_NOT_SUPPORTED" => array(106, "Feature not supported", 403),
			"AUTHENTICATION_FAILED" => array(107, "Authentication failed", 403),
			"REQUEST_FAILED" => array(108, "Request failed", 403),
		
			"INVALID_PATH" => array(201, "Invalid path", 403), 
			"FILE_DOES_NOT_EXIST" => array(202, "File does not exist", 403),
			"DIR_DOES_NOT_EXIST" => array(203, "Directory does not exist", 403),
			"FILE_ALREADY_EXISTS" => array(204, "File already exists", 403),
			"DIR_ALREADY_EXISTS" => array(205, "Directory already exists", 403),
			"NOT_A_FILE" => array(206, "Target is not a file", 403),
			"NOT_A_DIR" => array(207, "Target is not a directory", 403),
			"DELETE_FAILED" => array(208, "Could not delete", 403),
			"NO_UPLOAD_DATA" => array(209, "No upload data available", 403),
			"UPLOAD_FAILED" => array(210, "File upload failed", 403),
			"SAVING_FAILED" => array(211, "Saving file failed", 403),
			"INSUFFICIENT_RIGHTS" => array(212, "User does not have sufficient rights", 403),
			"ZIP_FAILED" => array(213, "Creating a zip package failed", 403),
			"NO_GENERAL_WRITE_PERMISSION" => array(214, "User has no general read/write permission", 403),
			"NOT_AN_ADMIN" => array(215, "User is not an administrator", 403),
			
			"UNEXPECTED_ERROR" => array(999, "Unexpected server error", 500),
		);
		private $output;
		
		function __construct($output) {
			$this->output = $output;
		}
		
		public function download($filename, $type, $stream, $size = NULL, $range = NULL) {
			$this->output->downloadBinary($filename, $type, $stream, $size, $range);
		}

		public function send($filename, $type, $stream, $size = NULL) {
			$this->output->sendBinary($filename, $type, $stream, $size);
		}
		
		public function html($html) {
			$this->output->sendResponse(new Response(200, "html", $html));
		}
		
		public function success($data) {
			$this->output->sendResponse(new Response(200, "json", $this->getSuccessResponse($data)));
		}
		
		public function error($type, $details) {
			$error = $this->getError($type);
			$this->output->sendResponse(new Response($error[2], "json", $this->getErrorResponse($error, $details)));
		}
		
		public function unknownServerError($msg) {
			$this->error("UNEXPECTED_ERROR", $msg);
		}
		
		private function getSuccessResponse($data) {
			if (Logging::isDebug()) {
				Logging::logDebug("RESPONSE success ".Util::toString($data));
				return array("result" => $data, "trace" => Logging::getTrace());
			}
			return array("result" => $data);
		}
		
		private function getError($error) {
			if (array_key_exists($error, self::$ERRORS)) {
				return self::$ERRORS[$error];
			} else {
				return array(0, "Unknown error: ".$error, 403);
			}			
		}
		
		private function getErrorResponse($err, $details) {
			if (Logging::isDebug()) {
				Logging::logDebug("RESPONSE error ".Util::toString($err)." ".$details);
				return array("code" => $err[0], "error" => $err[1], "details" => $details, "trace" => Logging::getTrace());
			}
			return array("code" => $err[0], "error" => $err[1], "details" => $details);
		}

		public function __toString() {
			return "ResponseHandler";
		}
	}
?>