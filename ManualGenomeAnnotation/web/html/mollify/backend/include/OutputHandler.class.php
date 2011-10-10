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

	class OutputHandler {
		private $codes = Array(  
            100 => 'Continue',  
            101 => 'Switching Protocols',  
            200 => 'OK',  
            201 => 'Created',  
            202 => 'Accepted',  
            203 => 'Non-Authoritative Information',  
            204 => 'No Content',  
            205 => 'Reset Content',  
            206 => 'Partial Content',  
            300 => 'Multiple Choices',  
            301 => 'Moved Permanently',  
            302 => 'Found',  
            303 => 'See Other',  
            304 => 'Not Modified',  
            305 => 'Use Proxy',  
            306 => '(Unused)',  
            307 => 'Temporary Redirect',  
            400 => 'Bad Request',  
            401 => 'Unauthorized',  
            402 => 'Payment Required',  
            403 => 'Forbidden',  
            404 => 'Not Found',  
            405 => 'Method Not Allowed',  
            406 => 'Not Acceptable',  
            407 => 'Proxy Authentication Required',  
            408 => 'Request Timeout',  
            409 => 'Conflict',  
            410 => 'Gone',  
            411 => 'Length Required',  
            412 => 'Precondition Failed',  
            413 => 'Request Entity Too Large',  
            414 => 'Request-URI Too Long',  
            415 => 'Unsupported Media Type',  
            416 => 'Requested Range Not Satisfiable',  
            417 => 'Expectation Failed',  
            500 => 'Internal Server Error',  
            501 => 'Not Implemented',  
            502 => 'Bad Gateway',  
            503 => 'Service Unavailable',  
            504 => 'Gateway Timeout',  
            505 => 'HTTP Version Not Supported'  
        );
        
		function __construct() {}
		
		public function sendResponse($response) {
			header($this->getStatus($response));
			header('Content-type: text/html');
			
			$data = $response->data();
			if (!$data) return;
			
			if ($response->type() === 'json') {
				echo json_encode($data);
			} else {
				echo $data;
			}
		}
		
		public function downloadBinary($filename, $type, $stream, $size = NULL, $range = NULL) {
			if ($range) {
				$start = $range[0];
				$end = $range[1];
				$size = $range[2];
				
				if ($start > 0 || $end < ($size - 1))
					header('HTTP/1.1 206 Partial Content');
				header("Cache-Control:");
				header("Cache-Control: public");
				header('Accept-Ranges: bytes');
				header('Content-Range: bytes '.$start.'-'.$end.'/'.$size);
				header('Content-Length: '.($end - $start + 1));
			} else {
				if ($size) header("Content-Length: ".$size);
				header("Cache-Control: public, must-revalidate");
				header("Content-Type: application/force-download");
				header("Content-Type: application/octet-stream");
				header("Content-Type: application/download");
				header("Content-Disposition: attachment; filename=\"".$filename."\";");
				header("Content-Transfer-Encoding: binary");
				header("Pragma: hack");
			}
			
			if ($range) fseek($stream, $range[0]);
			while (!feof($stream)) {
				set_time_limit(0);
				echo fread($stream, 1024);
				flush();
			}
			fclose($stream);
		}

		public function sendBinary($filename, $type, $stream, $size = NULL) {
			if ($size) header("Content-Length: ".$size);
			header("Content-Type: ".$this->getMime(trim(strtolower($type))));
			
			while (!feof($stream)) {
				set_time_limit(0);
				echo fread($stream, 1024);
				flush();
			}
			fclose($stream);
		}
		
		private function getStatus($response) {
			return 'HTTP/1.1 '.$response->code().' '.$this->codes[$response->code()];
		}
		
		private function getMime($type) {
			if ($type === 'ogg') return 'application/ogg';
			if ($type === 'mov') return 'video/quicktime';
			if ($type === 'mp4') return 'video/mp4';
			return 'application/octet-stream';
		}
		
		public function __toString() {
			return "OutputHandler";
		}
	}
?>