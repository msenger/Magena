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

	require_once("EventLogger.class.php");
	
	class EventLogging extends PluginBase {		
		public function hasAdminView() {
			return TRUE;
		}
		
		public function isConfigurationSupported($type) {
			return $type === ConfigurationProvider::TYPE_DATABASE;
		}

		public function setup() {
			$logged = $this->getSetting("logged_events", NULL);
			if (!$logged or count($logged) == 0) $logged = array("*");
			
			$this->addService("events", "EventServices");
			$this->env->features()->addFeature("event_logging");
			$e = new EventLogger($this->env);
			
			foreach($logged as $l)
				$this->env->events()->register($l, $e);
		}
		
		public function __toString() {
			return "EventLoggingPlugin";
		}
	}
?>