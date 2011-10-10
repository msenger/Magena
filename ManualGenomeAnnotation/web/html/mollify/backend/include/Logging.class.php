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

	class Logging {
		private static $debug = FALSE;
		private static $firebug = FALSE;
		private static $trace = array();
	
		static function initialize($settings) {
			self::$debug = isset($settings["debug"]) and $settings['debug'];
			self::$firebug = isset($settings["firebug_logging"]) and $settings['firebug_logging'];

			if (self::$firebug) {
				require_once('FirePHPCore/fb.php');
				FB::setEnabled(true);
			}
		}
		
		public static function isDebug() {
			return self::$debug;
		}
		
		public static function getTrace() {
			return self::$trace;
		}
		
		public static function logDebug($m) {
			if (!self::isDebug()) return;

			$s = self::toStr($m);
			error_log("MOLLIFY DEBUG: ".$s);
			if (self::$firebug) FB::log($m);
			self::$trace[] = $s;
		}
		
		public static function logInfo($m) {
			$s = self::toStr($m);
			error_log("MOLLIFY INFO: ".$s);

			if (self::$firebug) FB::log($m);
			if (self::isDebug()) self::$trace[] = $s;
		}
		
		public static function logError($m) {
			$s = self::toStr($m);
			error_log("MOLLIFY ERROR: ".$s);
			
			if (self::$firebug) FB::error($message);
			if (self::isDebug()) self::$trace[] = $s;
		}

		public static function logException($e) {
			$c = get_class($e);
			if ($c === "ServiceException") {
				$msg = "ServiceException: ".$e->type()."=".$e->details();
			} else {
				$msg = "Exception (".$c."): ".$e->getMessage();
			}
			self::logError($msg);
			self::logError($e->getTrace());
		}
				
		public static function logSystem() {
			if (!self::isDebug()) return;
			self::logDebug("SERVER: ".Util::array2str($_SERVER, array("HTTP_USER_AGENT", "HTTP_ACCEPT", "HTTP_HOST", "HTTP_ACCEPT_LANGUAGE", "HTTP_ACCEPT_ENCODING", "HTTP_ACCEPT_CHARSET", "HTTP_KEEP_ALIVE", "HTTP_CONNECTION", "PATH", "SERVER_SIGNATURE", "SERVER_SOFTWARE", "SERVER_NAME", "SERVER_ADDR", "SERVER_PORT", "REMOTE_ADDR", "DOCUMENT_ROOT", "SERVER_ADMIN", "REMOTE_PORT", "GATEWAY_INTERFACE", "FILES")));
		}
		
		private static function toStr($o) {
			if (is_array($o)) return Util::array2str($o);
			return (string) $o;
		}
	}
?>