<?php

	/**
	 * Copyright (c) 2008- Samuli J�rvel�
	 *
	 * All rights reserved. This program and the accompanying materials
	 * are made available under the terms of the Eclipse Public License v1.0
	 * which accompanies this distribution, and is available at
	 * http://www.eclipse.org/legal/epl-v10.html. If redistributing this code,
	 * this entire header must remain intact.
	 */
	
	require_once("install/MollifyInstaller.class.php");
	
	class DefaultInstaller extends MollifyInstaller {
		public function __construct() {
			parent::__construct("", "", array());			
		}
		
		public function isConfigured() { return FALSE; }
	}
?>