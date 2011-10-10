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

	class Settings {
		private $settings = array();
		
		private static $VALUES = array(
			"host_public_address" => NULL,
			"session_name" => NULL,
			"timezone" => NULL,
			"enable_limited_http_methods" => FALSE,
			"enable_file_upload" => TRUE,
			"enable_folder_actions" => TRUE,
			"enable_file_upload_progress" => FALSE,
			"enable_zip_download" => FALSE,
			"enable_change_password" => TRUE,
			"enable_description_update" => FALSE,
			"enable_permission_update" => FALSE,
			"allowed_file_upload_types" => array(),
			"firebug_logging" => FALSE,
			"zip_options" => array(),
			"permission_file" => "mollify.uac",
			"description_file" => "mollify.dsc",
			"enable_public_links" => FALSE,
			"enable_mail_notification" => FALSE,
			"mail_notification_from" => NULL,
			"debug" => FALSE
		);
		
		function __construct($settings) {
			$settingsExist = (isset($settings) and $settings != NULL);
			
			foreach(self::$VALUES as $s=>$v) {
				if (!$settingsExist or !array_key_exists($s, $settings)) continue;
				$this->settings[$s] = $settings[$s];
			}
		}

		public function setting($setting, $allowDefaultIfNotDefined = FALSE) {
			if (!$this->hasSetting($setting)) {
				if (!$allowDefaultIfNotDefined) return NULL;
				if (!isset(self::$VALUES[$setting])) throw new ServiceException("Invalid setting: ".$setting);
				return self::$VALUES[$setting];
			}
			return $this->settings[$setting];
		}
		
		public function hasSetting($setting) {
			return array_key_exists($setting, $this->settings);
		}
		
		function log() {
			Logging::logDebug("SETTINGS: ".Util::array2str($this->settings));
		}
		
		public function __toString() {
			return "Settings";
		}
	}
?>