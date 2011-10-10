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

	class MollifyZipStream {
		private $env;
		private $zip;
		
		function __construct($env, $name, $options) {
			require_once("zipstream.php");
			
			$this->env = $env;
			$this->zip = new ZipStream($name, $options);
		}
		
		public function add($name, $size, $hash, $stream) {
			$this->zip->add_file_stream($name, $size, $hash, $stream);
		}
		
		public function finish() {
			$this->zip->finish();
		}
	}