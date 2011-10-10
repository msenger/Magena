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
	
	require_once("install/file/FileInstaller.class.php");
	
	class FileUpdater extends FileInstaller {
		public function __construct($type, $settingsVar) {
			parent::__construct($type, $settingsVar, "update");
		}
			
		public function process() {
			if (!$this->isConfigured()) die();
			
			$this->createEnvironment();
			if (!$this->authentication()->isAuthenticationRequired()) die();
			
			// don't show update information unless admin user is logged in
			if (!$this->authentication()->isAdmin()) die("Mollify Updater requires administrator user");

			$this->showPage("cannot_update");
		}		
	}
?>