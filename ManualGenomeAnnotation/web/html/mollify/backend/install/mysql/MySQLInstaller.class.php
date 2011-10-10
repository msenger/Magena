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
	require_once("include/ServiceEnvironment.class.php");
	require_once("include/mysql/DatabaseUtil.class.php");
	require_once("install/mysql/MySQLInstallUtil.class.php");
	
	class MySQLInstaller extends MollifyInstaller {
		private $configured;
		protected $db;

		public function __construct($type, $settingsVar, $pageRoot = "install") {
			parent::__construct($pageRoot, $type, $settingsVar);
			
			global $DB_HOST, $DB_USER, $DB_PASSWORD, $DB_DATABASE, $DB_TABLE_PREFIX;
			$this->configured = isset($DB_USER, $DB_PASSWORD);
			$this->db = $this->createDB($DB_HOST, $DB_USER, $DB_PASSWORD, $DB_DATABASE, $DB_TABLE_PREFIX);
			$this->dbUtil = new DatabaseUtil($this->db);
		}

		private function createDB($host, $user, $password, $database, $tablePrefix) {
			if (!isset($host)) $host = "localhost";
			if (!isset($database)) $database = "mollify";
			if (!isset($tablePrefix)) $tablePrefix = "";
			
			require_once("include/mysql/MySQLIDatabase.class.php");
			return new MySQLIDatabase($host, $user, $password, $database, $tablePrefix);
		}
		
		protected function util() {
			require_once("install/mysql/MySQLInstallUtil.class.php");
			return new MySQLInstallUtil($this->db);
		}
		
		public function isConfigured() {
			return $this->configured;
		}

		public function isInstalled() {
			if (!$this->isConfigured())
				return FALSE;
			
			try {
				if (!$this->db->isConnected()) $this->db->connect(FALSE);
			} catch (ServiceException $e) {
				return FALSE;
			}

			if (!$this->db->databaseExists())
				return FALSE;
			
			try {
				$this->db->selectDb();
			} catch (ServiceException $e) {
				Logging::logDebug('Mollify not installed');
				return FALSE;
			}
			
			try {
				$ver = $this->dbUtil->installedVersion();
			} catch (ServiceException $e) {
				Logging::logDebug('Mollify not installed');
				return FALSE;
			}

			if ($ver != NULL)
				Logging::logDebug('Mollify installed version: '.$ver);
			else
				Logging::logDebug('Mollify not installed');

			return $ver != NULL;
		}
		
		public function isCurrentVersionInstalled() {
			return ($this->installedVersion() === $this->currentVersion());
		}
		
		public function installedVersion() {
			return $this->dbUtil->installedVersion();
		}
		
		public function pluginInstalledVersion($id) {
			return $this->dbUtil->pluginInstalledVersion($id);
		}

		public function currentVersion() {
			return MySQLConfigurationProvider::VERSION;
		}
		
		public function db() {
			return $this->db;
		}		

		public function process() {
			$this->checkSystem();
			$this->checkInstalled();
			$this->checkConfiguration();

			$phase = $this->phase();
			if ($phase == NULL) $phase = 'db';
			Logging::logDebug("Installer phase: [".$phase."]");	
			
			$this->onPhase($phase);
		}
		
		private function checkSystem() {
			if (!function_exists('mysql_connect')) {
				$this->setError("MySQL not detected", "Mollify cannot be installed to this system when MySQL is not available. Check your system configuration or choose different configuration type.");
				$this->showPage("install_error");
			}
		
			if (!function_exists('mysqli_multi_query')) {
				$this->setError("MySQL Improved (mysqli) not detected", "Mollify installer cannot continue without <a href='http://www.php.net/manual/en/mysqli.overview.php' target='_blank'>MySQL Improved</a> installed. Either check your configuration to install or enable this, or install Mollify manually (see instructions <a href='http://code.google.com/p/mollify/wiki/ConfigurationMySql' target='_blank'>here</a>).");
				$this->showPage("install_error");
			}
		}
		
		private function checkInstalled() {
			if (!$this->isInstalled()) return;
			
			$this->createEnvironment();
			if (!$this->authentication()->isAdmin()) die("Mollify Installer requires administrator user");
			
			$this->showPage("installed");
		}
		
		private function checkConfiguration() {
			if (!$this->isConfigured())
				$this->showPage("configuration");

			try {
				$this->db->connect(FALSE);
			} catch (ServiceException $e) {
				if ($e->type() === 'INVALID_CONFIGURATION') {
					$this->setError("Could not connect to database", '<code>'.$e->details().'</code>');
					$this->showPage("configuration");
					die();
				}
				throw $e;
			}
		}
		
		private function onPhase($phase) {
			$this->setPhase($phase);
			
			switch ($phase) {
				case 'db':
					$this->onPhaseDatabase();
					break;
				case 'admin':
					$this->onPhaseAdmin();
					break;
				case 'success':
					$this->showPage("success");
					break;
				default:
					Logging::logError("Invalid installer phase: ".$phase);
					die();
			}
		}
		
		// PHASES
				
		private function onPhaseDatabase() {
			if ($this->action() === 'continue_db') {
				$this->clearAction();
				
				if (!$this->db->databaseExists()) {
					try {
						$this->dbUtil->createDatabase();
					} catch (ServiceException $e) {
						$this->setError("Unable to create database", '<code>'.$e->details().'</code>');
						$this->onPhase('db');
					}
				}

				$this->checkDatabasePermissions();
			}
			
			$this->showPage("database");
		}
		
		private function checkDatabasePermissions() {
			try {
				$this->util()->checkPermissions();
			} catch (ServiceException $e) {
				$this->setError("Insufficient database permissions", '<code>'.$e->details().'</code>');
				$this->onPhase('db');
			}
			$this->onPhase('admin');
		}
		
		private function onPhaseAdmin() {
			if ($this->action() === 'install')
				$this->install();
			$this->showPage("admin");
		}
		
		private function install() {
			try {
				$this->db->selectDb();
			} catch (ServiceException $e) {
				$this->setError("Could not select database", '<code>'.$e->details().'</code>');
				$this->showPage("install_error");
			}
			
			$this->db->startTransaction();
			
			try {
				$this->util()->execCreateTables();
				$this->util()->execInsertParams();
			} catch (ServiceException $e) {
				$this->setError("Could not install", '<code>'.$e->details().'</code>');
				$this->showPage("install_error");
			}

			try {
				$this->util()->createAdminUser($this->data("name"), $this->data("password"));
			} catch (ServiceException $e) {
				$this->setError("Could not create admin user", '<code>'.$e->details().'</code>');
				$this->showPage("install_error");
			}
			
			try {
				$this->db->commit();
			} catch (ServiceException $e) {
				$this->setError("Could install", '<code>'.$e->details().'</code>');
				$this->showPage("install_error");
			}

			$this->onPhase('success');
		}
	}
?>