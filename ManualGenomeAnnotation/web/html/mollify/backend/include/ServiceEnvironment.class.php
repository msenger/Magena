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

	require_once("Features.class.php");
	require_once("Authentication.class.php");
	require_once("filesystem/FilesystemController.class.php");
	require_once("plugin/PluginController.class.php");
	require_once("services/ServicesBase.class.php");
	require_once("MailNotification.class.php");
	
	class ServiceEnvironment {
		const ENTRY_SCRIPT = 'r.php';
		const RESOURCE_LOCATION = 'resources/';
		
		private $services = array();
		private $serviceControllerPaths = array();
		
		private $session;
		private $authentication; 
		private $responseHandler;
		private $configurationProvider;
		private $settings;
		private $eventHandler;
		private $filesystem;
		private $request;
		private $notification;
		
		public function __construct($session, $responseHandler, $configurationProvider, $settings) {
			$this->session = $session;
			$this->responseHandler = $responseHandler;
			$this->configurationProvider = $configurationProvider;
			$this->settings = $settings;
			$this->features = new Features($configurationProvider, $settings);
			$this->authentication = new Authentication($this);
			$this->eventHandler = new EventHandler();
			$this->filesystem = new FilesystemController($this);
			$this->plugins = new PluginController($this);
			$this->notification = new MailNotification($this);
			
			if ($settings->hasSetting('timezone')) date_default_timezone_set($settings->setting('timezone'));
		}
		
		public function session() {
			return $this->session;
		}

		public function response() {
			return $this->responseHandler;
		}
		
		public function authentication() {
			return $this->authentication;
		}
		
		public function configuration() {
			return $this->configurationProvider;
		}

		public function features() {
			return $this->features;
		}

		public function filesystem() {
			return $this->filesystem;
		}

		public function plugins() {
			return $this->plugins;
		}
		
		public function settings() {
			return $this->settings;
		}

		public function events() {
			return $this->eventHandler;
		}

		public function request() {
			return $this->request;
		}

		public function notification() {
			return $this->notification;
		}
										
		public function initialize($request = NULL) {
			$sessionId = NULL;
			if ($request != NULL and $request->hasParam("session")) $sessionId = $request->param("session");
			$this->request = $request;
			
			$this->session->initialize($this, $sessionId);
			$this->configurationProvider->initialize($this);
			$this->filesystem->initialize();
			$this->authentication->initialize();
			$this->plugins->initialize($this);

			$this->log();
		}
				
		public function addService($id, $controller, $controllerPath = NULL) {
			$this->services[$id] = $controller;
			if ($controllerPath != NULL) $this->serviceControllerPaths[$id] = $controllerPath;
		}
		
		public function getService($request) {
			$path = $request->path();
			$id = $path[0];
			if (!array_key_exists($id, $this->services)) throw new ServiceException("Unknown service '".$id."'");
			
			$service = $this->createService($this->services[$id], $request, $id, array_slice($path, 1));
			if (Logging::isDebug()) $service->log();
			return $service;
		}
		
		private function createService($controller, $request, $id, $path) {
			$controllerPath = "services/";
			if (array_key_exists($id, $this->serviceControllerPaths)) $controllerPath = $this->serviceControllerPaths[$id];
			
			require_once($controllerPath.$controller.".class.php");
			return new $controller($this, $request, $id, $path);
		}
		
		public function getServiceUrl($id, $path, $full = FALSE) {
			if ($full and !$this->settings->hasSetting("host_public_address")) throw new ServiceException("No host public address defined in configuration");
			$url = ($full ? $this->settings->setting("host_public_address").$_SERVER['SCRIPT_NAME']."/" : "").$id;
			foreach($path as $p) $url .= "/".$p;
			return $url."/";
		}
		
		public function getPluginUrl($pluginId, $path = NULL) {
			return $this->getResourceUrl("plugin/".$pluginId."/".($path != NULL ? $path."/" : ""));
		}
		
		public function getResourceUrl($path) {
			return $this->getRootUrl().$path;
		}

		public function getCommonResourcesUrl() {
			return $this->getRootUrl().'resources/';
		}
		
		private function getRootUrl() {
			if (!$this->settings->hasSetting("host_public_address")) throw new ServiceException("No host public address defined in configuration");
			
			$root = substr($_SERVER['SCRIPT_NAME'], 0, strlen($_SERVER['SCRIPT_NAME']) - strlen(self::ENTRY_SCRIPT));
			return $this->settings->setting("host_public_address").$root;
		}
		
		public function log() {
			if (!Logging::isDebug()) return;
			Logging::logSystem();
			
			$this->settings->log();
			$this->configurationProvider->log();
			$this->features->log();
			$this->filesystem->log();
			$this->session->log();
			$this->authentication->log();
		}
		
		public function __toString() {
			return "ServiceEnvironment";
		}
	}

	class ServiceException extends Exception {
		private $type;
		
		public function __construct($type, $details = "") {
			parent::__construct($details);
			$this->type = $type;
		}
		
		function type() {
			return $this->type;
		}
		
		function details() {
			return $this->getMessage();
		}
	}
?>