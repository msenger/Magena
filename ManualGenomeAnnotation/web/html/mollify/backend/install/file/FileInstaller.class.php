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
	
	require_once("install/MollifyInstaller.class.php");
	
	class FileInstaller extends MollifyInstaller {
		private $users;
		private $publishedDirectories;
		
		public function __construct($type, $settingsVar, $pageRoot = "install") {
			parent::__construct($pageRoot, $type, $settingsVar);
			
			global $USERS, $PUBLISHED_FOLDERS;
			$this->users = $USERS;
			$this->publishedDirectories = $PUBLISHED_FOLDERS;
		}
		
		public function process() {
			if (!$this->isConfigured()) {
				$this->showPage("configuration");
				return;
			}
			
			$this->createEnvironment();
			
			if ($this->action() != 'continue') {
				// don't show installation information unless admin user is logged in (in single user mode, this is never)
				if (!$this->authentication()->isAdmin()) die();
			}
			
			$this->showPage("installed");
		}
		
		public function isConfigured() {
			return isset($this->publishedDirectories);
		}
		
		public function users() {
			return $this->users;
		}

		public function publishedDirectories() {
			return $this->publishedDirectories;
		}
	}
?>