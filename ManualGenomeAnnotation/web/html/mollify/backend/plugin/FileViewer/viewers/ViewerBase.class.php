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

	abstract class ViewerBase {
		protected $env;
		protected $id;
		
		public function __construct($env, $id) {
			$this->env = $env;
			$this->id = $id;
		}
		
		protected function response() {
			return $this->env->response();
		}

		protected function request() {
			return $this->env->request();
		}

		protected function getDataUrl($item, $p, $fullUrl = FALSE) {
			return $this->env->getViewServiceUrl($item, array("data", $p), $fullUrl);
		}
				
		public function getServiceUrl($id, $path, $fullUrl = FALSE) {
			return $this->env->getServiceUrl($id, $path, $fullUrl);
		}
		
		public function getContentUrl($item, $session = FALSE) {
			return $this->env->getContentUrl($item, $session);
		}

		public function getResourceUrl() {
			return $this->env->getResourceUrl($this->id);
		}
		
		public function getSettings() {
			return $this->env->getViewerSettings($this->id);
		}
		
		protected function invalidRequestException($details = NULL) {
			return new ServiceException("INVALID_REQUEST", "Invalid ".get_class($this)." request: ".strtoupper($this->env->request()->method())." ".$this->env->request()->URI().($details != NULL ? (" ".$details): ""));
		}
	}
?>