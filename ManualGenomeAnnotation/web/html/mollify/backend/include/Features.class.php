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

	class Features {
		private $features = array(
			"limited_http_methods" => FALSE,
			"file_upload" => FALSE,
			"folder_actions" => FALSE,
			"file_upload_progress" => FALSE,
			"zip_download" => FALSE,
			"change_password" => FALSE,
			"description_update" => FALSE,
			"permission_update" => FALSE,
			"administration" => FALSE,
			"user_groups" => FALSE,
			"public_links" => FALSE,
			"mail_notification" => FALSE
		);
		
		private $defaultValues = array();
		
		private static $featuresControlledByConfigurationProvider = array("change_password", "description_update", "permission_update", "administration", "user_groups");
		
		function __construct($configuration, $settings) {
			$configurationFeatures = $configuration->getSupportedFeatures();
			
			foreach ($this->features as $f=>$k) {
				$enabled = FALSE;
				$configControlled = in_array($f, self::$featuresControlledByConfigurationProvider);
				
				if ($configControlled) {
					$configSupported = in_array($f, $configurationFeatures);
					
					if (!$configSupported)
						$enabled = FALSE;
					else if ($settings->hasSetting("enable_".$f))
						$enabled = $settings->setting("enable_".$f);
					else
						$enabled = $configuration->featureEnabledByDefault($f, FALSE);					
				} else {
					$enabled = $settings->setting("enable_".$f, TRUE);
				}
				$this->features[$f] = $enabled;
			}
		}
		
		public function addFeature($name) {
			$this->features[$name] = TRUE;
		}
		
		public function isFeatureEnabled($feature) {
			if (!in_array($feature, $this->features)) throw new ServiceException("INVALID_REQUEST", "Invalid feature requested: ".$feature);
			return $this->features[$feature];
		}
		
		public function assertFeature($feature) {
			if (!$this->isFeatureEnabled($feature)) throw new ServiceException("FEATURE_DISABLED", "Required feature not enabled: ".$feature);
		}
		
		public function getFeatures() {
			return $this->features;
		}
		
		function log() {
			Logging::logDebug("FEATURES: ".Util::array2str($this->features));
		}

		public function __toString() {
			return "Features";
		}
	}
?>