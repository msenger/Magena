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

	abstract class PluginBase {
		protected $env;
		protected $id;
		protected $settings;
		
		public function __construct($env, $id, $settings) {
			$this->env = $env;
			$this->id = $id;
			$this->settings = $settings;
		}
		
		public abstract function setup();
		
		public function initialize() {}
		
		public function version() {
			return NULL;
		}

		public function versionHistory() {
			return array();
		}
		
		public function hasAdminView() {
			return FALSE;
		}
		
		public function isConfigurationSupported($type) {
			return TRUE;
		}
				
		public function id() {
			return $this->id;
		}
		
		public function env() {
			return $this->env;
		}
		
		public function getSettings() {
			return $this->settings;
		}
		
		public function getSetting($name, $default = NULL) {
			if (!$this->settings or !isset($this->settings[$name])) return $default;
			return $this->settings[$name];
		}
		
		public function addService($path, $controller) {
			$this->env->addService($path, $controller, "plugin/".$this->id."/");
		}
		
		public function getSessionInfo() {
			return array();
		}
		
		function log() {
			if (!Logging::isDebug()) return;
			Logging::logDebug("PLUGIN (".get_class($this).")");
		}
	}
?>