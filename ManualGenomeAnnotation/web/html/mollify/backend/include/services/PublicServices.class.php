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

	class PublicServices extends ServicesBase {
		protected function isValidPath($method, $path) {
			return count($path) == 2;
		}
		
		public function isAuthenticationRequired() {
			return FALSE;
		}
		
		public function processGet() {
			if ($this->path[0] === 'items' and $this->env->features->isFeatureEnabled("public_links")) {
				$item = $this->item($this->path[1]);
				$this->env->filesystem()->temporaryItemPermission($item, Authentication::PERMISSION_VALUE_READONLY);
				$this->env->filesystem()->download($item);
				return;
			}
			throw $this->invalidRequestException();
		}
		
		public function __toString() {
			return "PublicServices";
		}
	}
?>