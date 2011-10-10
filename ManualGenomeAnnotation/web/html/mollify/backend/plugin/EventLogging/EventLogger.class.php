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

	class EventLogger {
		private $env;
		
		public function __construct($env) {
			$this->env = $env;
		}
		
		public function onEvent($e) {
			$time = date('YmdHis', $e->time());
			
			$item = $e->itemToStr();
			$details = $e->details();
			$type = $e->typeId();
			$username = $this->getUser($e);
						
			$db = $this->env->configuration()->db();
			$db->update(sprintf("INSERT INTO ".$db->table("event_log")." (time, user, type, item, details) VALUES (%s, %s, '%s', %s, %s)", $time, $db->string($username, TRUE), $db->string($type), $db->string($item, TRUE), $db->string($details, TRUE)));
		}
		
		private function getUser($e) {
			if ($e->user() != NULL) return $e->user();
			
			$user = $this->env->authentication()->isAuthenticated() ? $this->env->authentication()->getUserInfo() : NULL;
			return $user != NULL ? $user['username'] : NULL;
		}
		
		public function __toString() {
			return "EventLogger";
		}
	}
?>