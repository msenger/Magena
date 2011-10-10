<?php

	/**
	 * Copyright (c) 2008- Samuli JŠrvelŠ
	 *
	 * All rights reserved. This program and the accompanying materials
	 * are made available under the terms of the Eclipse Public License v1.0
	 * which accompanies this distribution, and is available at
	 * http://www.eclipse.org/legal/epl-v10.html. If redistributing this code,
	 * this entire header must remain intact.
	 */
	
	abstract class MollifyInstaller {
		protected $type;
		protected $pageRoot;
		private $settingsVar;

		private $settings;
		private $session;
		private $authentication;
		private $configuration;
		private $plugins;
		private $features;
		
		private $error = NULL;
		private $errorDetails = NULL;
		private $data = array();
		
		public function __construct($pageRoot, $type, $settingsVar) {
			$this->pageRoot = $pageRoot;
			$this->type = $type;
			$this->settingsVar = $settingsVar;
			foreach($_POST as $key => $val) $this->data[$key] = $val;
			
			require_once("include/Logging.class.php");
			Logging::initialize($this->settingsVar);
			Logging::logDebug("Installer: ".get_class($this));
		}
		
		public function createEnvironment() {
			require_once("include/Settings.class.php");
			require_once("include/Features.class.php");
			require_once("include/Util.class.php");
			require_once("include/Session.class.php");
			require_once("InstallerAuthentication.class.php");
			require_once("include/ConfigurationProviderFactory.class.php");
			require_once("plugin/PluginController.class.php");
			$configurationProviderFactory = new ConfigurationProviderFactory();
			
			$this->settings = new Settings($this->settingsVar);
			$this->session = new Session($this->settings);
			$this->configuration = $configurationProviderFactory->createConfigurationProvider($this->type, $this->settings);
			$this->authentication = new InstallerAuthentication($this);
			$this->features = new Features($this->configuration, $this->settings);
			$this->plugins = new PluginController($this);
			
			$this->plugins->setup();
			$this->session->initialize($this);
		}
		
		public abstract function isConfigured();
		
		public function onError($e) {
			Logging::logException($e);
		}
		
		public function session() {
			return $this->session;
		}
		
		public function authentication() {
			return $this->authentication;
		}
		
		public function configuration() {
			return $this->configuration;
		}

		public function events() {
			return $this;
		}

		public function features() {
			return $this->features;
		}

		public function plugins() {
			return $this->plugins;
		}

		public function filesystem() {
			return $this;
		}
		
		public function hasError() {
			return $this->error != NULL;
		}

		public function hasErrorDetails() {
			return $this->errorDetails != NULL;
		}
		
		public function error() {
			return $this->error;
		}

		public function errorDetails() {
			return $this->errorDetails;
		}

		public function setError($title, $details = NULL) {
			$this->error = $title;
			$this->errorDetails = $details;
		}
	
		public function action() {
			return $this->data("action");
		}

		public function clearAction() {
			unset($this->data["action"]);
		}

		public function phase() {
			return $this->data("phase");
		}
				
		public function setPhase($val) {
			Logging::logDebug("New installer phase: [".$val."]");
			$this->data['phase'] = $val;
		}
		
		public function data($name = NULL) {
			if ($name == NULL) return $this->data;
			return isset($this->data[$name]) ? $this->data[$name] : NULL;
		}
		
		public function setData($name, $value) {
			$this->data[$name] = $value;
		}
		
		protected function getPagePath($page) {
			return $this->pageRoot."/".$this->type."/"."page_".$page.".php";
		}
		
		protected function showPage($page) {
			$page = $this->getPagePath($page);
			Logging::logDebug("Opening page: ".$page." ".($this->hasError() ? "(error=".$this->error.")" : ""));
			require($page);
			die();
		}
		
		public function addFeature($f) {}
		
		public function addService($p, $s) {}
		
		public function registerEventType($e, $d) {}
		
		public function register($e, $d) {}
		
		public function registerObject($e, $d) {}
		
		public function registerDetailsPlugin($p) {}
		
		public function __toString() {
			return "MollifyInstaller";
		}
	}
?>