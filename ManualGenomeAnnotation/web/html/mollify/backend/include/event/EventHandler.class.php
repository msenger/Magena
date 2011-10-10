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

	class EventHandler {
		private $listeners = array();
		private $types = array();
				
		public function register($type, $listener) {
			if (Logging::isDebug()) Logging::logDebug("EVENT HANDLER: registering '".$type."': ".get_class($listener));
			
			if (!array_key_exists($type, $this->listeners)) $this->listeners[$type] = array();
			$list = $this->listeners[$type];
			$list[] = $listener;
			$this->listeners[$type] = $list;
		}
		
		public function registerEventType($type, $subType, $name) {
			$this->types[$type."/".$subType] = $name;
		}
		
		public function onEvent($e) {
			if (Logging::isDebug()) Logging::logDebug("EVENT HANDLER: onEvent: '".$e->type()."'");
			
			foreach($this->listeners as $type => $listeners) {
				if ($type == '*' or strpos($e->typeId(), $type) == 0) {
					foreach($listeners as $listener)
						$listener->onEvent($e);
				}
			}	
		}
		
		public function getTypes() {
			return $this->types;
		}
		
		public function __toString() {
			return "EventHandler";
		}
	}
	
	abstract class Event {
		private $time;
		private $user = NULL;
		private $type;
		private $subType;
		
		public function __construct($time, $type, $subType) {
			$this->time = $time;
			$this->type = $type;
			$this->subType = $subType;
		}

		public function time() {
			return $this->time;
		}

		public function user() {
			return $this->user;
		}
				
		public function type() {
			return $this->type;
		}

		public function subType() {
			return $this->subType;
		}
		
		public function typeId() {
			return $this->type."/".$this->subType;
		}
				
		public function itemToStr() { return ""; }
		
		public function details() { return ""; }
		
		protected function setUser($user) {
			$this->user = $user;
		}
		
		public function __toString() {
			return "Event ".$this->type;
		}
	}
?>