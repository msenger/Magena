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

	require_once("include/Settings.class.php");
	require_once("include/event/EventHandler.class.php");
	require_once("include/Session.class.php");
	require_once("include/ServiceEnvironment.class.php");
	require_once("include/Util.class.php");

	class MollifyBackend {
		private $environment;
		
		function __construct($settingsVar, $configurationProviderId, $configurationProviderFactory, $responseHandler) {
			$settings = new Settings($settingsVar);
			$session = new Session($settings);
			$configurationProvider = $configurationProviderFactory->createConfigurationProvider($configurationProviderId, $settings);
			
			$this->environment = new ServiceEnvironment($session, $responseHandler, $configurationProvider, $settings);
			$this->setup();
		}
	
		private function setup() {
			$this->environment->addService("authentication", "AuthenticationServices");
			$this->environment->addService("session", "SessionServices");
			$this->environment->addService("configuration", "ConfigurationServices");
			$this->environment->addService("filesystem", "FilesystemServices");
			$this->environment->addService("public", "PublicServices");
			
			$this->environment->plugins()->setup();
		}
		
		public function env() {
			return $this->environment;
		}
		
		public function processRequest($request) {
			$this->environment->initialize($request);
			$service = $this->environment->getService($request);
			
			if ($service->isAuthenticationRequired() and !$this->environment->authentication()->isAuthenticated()) {
				$this->environment->session()->reset();
				throw new ServiceException("UNAUTHORIZED");
			}
			
			$service->processRequest();
		}
		
		public function __toString() {
			return "MollifyBackend";
		}
	}
?>