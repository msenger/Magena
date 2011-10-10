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

	require_once("include/Session.class.php");
	
	class InstallerSession extends Session {
		private $settings;
		
		public function __construct($settings) {
			$this->settings = $settings;
		}
		
		public function initialize($env) {
			parent::initialize($env);
			if ($this->env->authentication()->isAdmin())
				return;
			session_destroy();
		}
	}
?>