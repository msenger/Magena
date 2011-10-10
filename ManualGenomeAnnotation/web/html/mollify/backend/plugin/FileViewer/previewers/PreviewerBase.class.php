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

	abstract class PreviewerBase {
		protected $env;
		protected $id;
		
		public function __construct($env, $id) {
			$this->env = $env;
			$this->id = $id;
		}
		
		public function getPreview($item) {
			return array("html" => $this->getPreviewHtml($item));
		}
		
		protected abstract function getPreviewHtml($item);
		
		protected function response() {
			return $this->env->response();
		}
		
		public function getUrl($item) {
			return $this->env->getServiceUrl("preview", array($item->publicId(), "info"));
		}
				
		public function getContentUrl($item, $session = FALSE) {
			return $this->env->getServiceUrl("preview", array($item->publicId(), "content"), TRUE);
		}
		
		public function getSettings() {
			return $this->env->getViewerSettings($this->id);
		}
		
		protected function invalidRequestException($details = NULL) {
			return new ServiceException("INVALID_REQUEST", "Invalid ".get_class($this)." request: ".strtoupper($this->env->request()->method())." ".$this->env->request()->URI().($details != NULL ? (" ".$details): ""));
		}
	}
?>