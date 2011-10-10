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
	 
	class ConfigurationProviderFactory {
		public function createConfigurationProvider($configurationProviderId, $settings) {
			require_once("configuration/ConfigurationProvider.class.php");
			if (!$configurationProviderId or strcasecmp($configurationProviderId, 'file') == 0) {
				require_once("configuration/FileConfigurationProvider.class.php");
				return new FileConfigurationProvider($settings);
			} else if (!$configurationProviderId or strcasecmp($configurationProviderId, 'mysql') == 0) {
				require_once("configuration/MySQLConfigurationProvider.class.php");
				return new MySQLConfigurationProvider($settings);
			} else {
				Logging::logError("Unsupported data provider: [".$configurationProviderId."]");
				throw new ServiceException("INVALID_CONFIGURATION", "Unsupported data provider: [".$configurationProviderId."]");
			}
		}
		
		public function __toString() {
			return "ConfigurationProviderFactory";
		}
	}
?>