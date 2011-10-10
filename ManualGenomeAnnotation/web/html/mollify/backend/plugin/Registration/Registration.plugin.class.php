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
	
	class Registration extends PluginBase {
		const EVENT_TYPE_REGISTRATION = 'registration';
		
		public function setup() {			
			$this->addService("registration", "RegistrationServices");
			$this->env->features()->addFeature("registration");
			RegistrationEvent::register($this->env->events());
		}
		
		public function hasAdminView() {
			return TRUE;
		}
		
		public function isConfigurationSupported($type) {
			return $type === ConfigurationProvider::TYPE_DATABASE;
		}
		
		public function version() {
			return "1_0";
		}

		public function versionHistory() {
			return array("1_0");
		}
				
		public function __toString() {
			return "RegistrationPlugin";
		}
	}
	
	 class RegistrationEvent extends Event {
		const REGISTER = "register";
		const CONFIRM = "confirm";
			
		static function register($eventHandler) {
			$eventHandler->registerEventType(Registration::EVENT_TYPE_REGISTRATION, self::REGISTER, "User registered");
			$eventHandler->registerEventType(Registration::EVENT_TYPE_REGISTRATION, self::CONFIRM, "User registration confirmed");
		}
		
		static function registered($name, $email) {
			return new RegistrationEvent($name, self::REGISTER, "email=".$email);
		}

		static function confirmed($name) {
			return new RegistrationEvent($name, self::CONFIRM);
		}
		
		function __construct($name, $type, $info = "") {
			parent::__construct(time(), Registration::EVENT_TYPE_REGISTRATION, $type);
			$this->setUser($name);
			$this->info = $info;
		}
			
		public function details() {
			return $this->info;
		}
	}
?>