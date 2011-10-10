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

	class SessionServices extends ServicesBase {
		private static $PROTOCOL_VERSION = "1_5_0";
		private static $GET_ITEMS = array("info", "logout");
		private static $POST_ITEMS = array("authenticate", "logout");
		
		protected function isValidPath($method, $path) {
			if (count($path) < 1) return FALSE;
			
			if ($method === Request::METHOD_GET and !in_array($path[0], self::$GET_ITEMS)) return FALSE;
			if ($method === Request::METHOD_POST and !in_array($path[0], self::$POST_ITEMS)) return FALSE;
			if ($path[0] === 'info' and count($path) < 2) return FALSE;
			
			return TRUE;
		}
		
		public function isAuthenticationRequired() {
			return FALSE;
		}

		public function processGet() {
			if ($this->path[0] === 'logout') {
				$this->env->events()->onEvent(SessionEvent::logout($this->env->request()->ip()));
				$this->env->session()->reset();
				$this->response()->success(TRUE);
				return;
			}
			$this->response()->success($this->getSessionInfo($this->path[1]));
		}

		public function processPost() {
			if ($this->path[0] === 'logout') {
				$this->env->events()->onEvent(SessionEvent::logout($this->env->request()->ip()));
				$this->env->session()->reset();
				$this->response()->success(TRUE);
				return;
			}
			
			$this->authenticate();
		}
		
		private function authenticate() {
			if (!$this->request->hasData("username") or !$this->request->hasData("password") or !$this->request->hasData("protocol_version"))
				throw new ServiceException("INVALID_REQUEST", "Missing parameters");
			
			$this->env->authentication()->authenticate($this->request->data("username"), $this->request->data("password"));
			$this->env->events()->onEvent(SessionEvent::login($this->env->request()->ip()));
			$this->response()->success($this->getSessionInfo($this->request->data("protocol_version")));
		}
		
		private function getSessionInfo($protocolVersion) {
			Logging::logDebug("Requesting session info for protocol version ".$protocolVersion);
			
			if ($protocolVersion != self::$PROTOCOL_VERSION)
				throw new ServiceException("INVALID_CONFIGURATION", "Unsupported protocol version [".$protocolVersion."], expected [".self::$PROTOCOL_VERSION."]");
			$this->env->configuration()->checkProtocolVersion($protocolVersion);
			
			$auth = $this->env->authentication();
			$info = array("authentication_required" => $auth->isAuthenticationRequired(), "authenticated" => $auth->isAuthenticated(), "features" => $this->env->features()->getFeatures());
			
			if (!$auth->isAuthenticationRequired() or $auth->isAuthenticated()) {
				$info = array_merge(
					$info,
					$this->env->session()->getSessionInfo(),
					$this->env->authentication()->getUserInfo(),
					$this->env->filesystem()->getSessionInfo(),
					$this->env->plugins()->getSessionInfo()
				);
				
			}
			return $info;
		}
		
		public function __toString() {
			return "SessionServices";
		}
	}
?>