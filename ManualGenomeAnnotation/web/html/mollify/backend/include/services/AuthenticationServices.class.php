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

	class AuthenticationServices extends ServicesBase {
		protected function isValidPath($method, $path) {
			return ($method == Request::METHOD_GET and count($path) == 0);
		}
		
		public function processGet() {
			$state = (!$this->env->authentication()->isAuthenticationRequired() or $this->env->authentication()->isAuthenticated());
			$this->response()->success($state);
		}
		
		public function __toString() {
			return "AuthenticationServices";
		}
	}
?>