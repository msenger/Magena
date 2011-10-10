<?php

	/**
	 * Copyright (c) 2008- Samuli JŠrvelŠ
	 *
	 * All rights reserved. This program and the accompanying materials
	 * are made available under the terms of the Eclipse Public License v1.0
	 * which accompanies this distribution, and is available at
	 * http://www.eclipse.org/legal/epl-v10.html. If redistributing this code,
	 * this entire header must remain intact.
	 */

	require_once("include/Authentication.class.php");
	
	class InstallerAuthentication extends Authentication {
		public function __construct($env) {
			parent::__construct($env);
		}
		
		public function getDefaultPermission() {
			if ($this->env->session()->hasParam('default_permission')) return $this->env->session()->param('default_permission');
			return parent::getDefaultPermission();
		}
	}
?>