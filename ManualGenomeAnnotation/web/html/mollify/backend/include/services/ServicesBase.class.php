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

	class ServicesBase {
		protected $env;
		protected $request;
		protected $id;
		protected $path;
		
		public function __construct($serviceEnvironment, $request, $id, $path) {
			$this->env = $serviceEnvironment;
			$this->request = $request;
			$this->id = $id;
			$this->path = $path;
			
			if (!$this->isValidPath($this->request->method(), $this->path)) throw $this->invalidRequestException();
		}
		
		public function isAuthenticationRequired() {
			return $this->env->configuration()->isAuthenticationRequired();
		}
		
		protected function isValidPath($method, $path) {
			return FALSE;
		}
		
		public function response() {
			return $this->env->response();
		}
		
		public function processRequest() {
			switch($this->request->method()) {
				case Request::METHOD_GET:
					$this->processGet();
					break;
				case Request::METHOD_PUT:
					$this->processPut();
					break;
				case Request::METHOD_POST:
					$this->processPost();
					break;
				case Request::METHOD_DELETE:
					$this->processDelete();
					break;
				default:
					throw new RequestException("Unsupported method '".$this->request->method()."'");
			}
		}
		
		function processGet() { throw new ServiceException("INVALID_REQUEST", "Unimplemented method 'get'"); }
		
		function processPut() { throw new ServiceException("INVALID_REQUEST", "Unimplemented method 'put'"); }
		
		function processPost() { throw new ServiceException("INVALID_REQUEST", "Unimplemented method 'post'"); }
		
		function processDelete() { throw new ServiceException("INVALID_REQUEST", "Unimplemented method 'delete'"); }
		
		protected function item($id, $convert = TRUE) {
			$i = $convert ? $this->convertItemId($id) : $id;
			return $this->env->filesystem()->item(base64_decode($i));
		}
		
		protected function convertItemId($id) {
			return strtr(urldecode($id), '-_,', '+/=');
		}
		
		protected function invalidRequestException($details = NULL) {
			return new ServiceException("INVALID_REQUEST", "Invalid ".get_class($this)." request: ".strtoupper($this->request->method())." ".$this->request->URI().($details != NULL ? (" ".$details): ""));
		}
		
		function log() {
			if (!Logging::isDebug()) return;
			$this->request->log();
			Logging::logDebug("SERVICE (".get_class($this)."): is_auth_required=".$this->isAuthenticationRequired($this->request));
		}
	}
?>