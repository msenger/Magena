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

	abstract class ConfigurationProvider {
		const TYPE_DATABASE = "db";
		const TYPE_FILE = "file";
		
		protected $env;
		
		function initialize($env) {
			$this->env = $env;
		}
		
		public abstract function getType();
		
		function initializeSession($session, $userId) {}
		
		public function checkProtocolVersion($version) {}
		
		public function getSupportedFeatures() {
			return array();
		}
		
		public function featureEnabledByDefault($name, $default) {
			return $default;
		}
		
		public function getInstalledVersion() { return NULL; }
		
		public function onSessionStart($userId, $username) {
			return TRUE;
		}
	
		function findUser($username, $password) {
			return FALSE;
		}
		
		function getAllUsers() {
			return array();
		}

		function getUser($id) {
			return FALSE;
		}
		
		function getDefaultPermission($userId = "") {
			return FALSE;
		}
	
		function isAuthenticationRequired() {
			return TRUE;
		}
		
		public function getUserFolders($userId) {
			return array();
		}
		
		function getItemDescription($item) {
			return NULL;
		}
				
		function setItemDescription($item, $description) {
			return FALSE;
		}
	
		function removeItemDescription($item) {
			return FALSE;
		}
		
		function moveItemDescription($from, $to) {
			return FALSE;
		}
					
		function getItemPermission($item, $userId) {
			return FALSE;
		}
	
		function getItemPermissions($item) {
			return FALSE;
		}
			
		function updateItemPermissions($updates) {
			return FALSE;
		}

		function removeItemPermissions($item) {
			return FALSE;
		}
		
		function moveItemPermissions($from, $to) {
			return FALSE;
		}
		
		function log() {
			Logging::logDebug("CONFIGURATION PROVIDER (".get_class($this)."): supported features=".Util::array2str($this->getSupportedFeatures())." auth=".$this->isAuthenticationRequired());
		}
		
		public function __toString() {
			return "ConfigurationProvider (".get_class($this).")";
		}
	}
?>