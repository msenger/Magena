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
	
	require_once("install/mysql/MySQLInstaller.class.php");
	
	class MySQLUpdater extends MySQLInstaller {
		private static $versionHistory = array("0_9_5", "1_0_0", "1_5_0", "1_5_4", "1_6_0");
		
		public function __construct($type, $settingsVar) {
			parent::__construct($type, $settingsVar, "update");
		}
		
		public function process() {
			if (!$this->isInstalled()) die();

			$this->createEnvironment();
			if (!$this->authentication()->isAdmin()) die("Mollify Updater requires administrator user");
			
			if ($this->isCurrentVersionInstalled() and $this->arePluginsUptodate()) $this->showPage("current_installed");
			
			if ($this->action() === 'update') $this->update();
			$this->showPage("update");
		}
		
		public function versionString($ver) {
			return str_replace("_", ".", $ver);
		}
		
		private function arePluginsUptodate() {
			foreach ($this->plugins()->getPlugins() as $id => $p) {
				if ($p->version() == NULL) continue;
				$installed = $this->pluginInstalledVersion($id);
				$current = $p->version();
				
				if ($installed == NULL or strcmp($installed, $current) != 0) return FALSE;
			}
			return TRUE;
		}

		private function update() {
			$allPlugins = $this->plugins()->getPlugins();
			$updates = array();
			
			try {
				$this->db->startTransaction();
				
				// update system if required
				$desc = $this->updateSystem();
				if ($desc) $updates[] = $desc;
				
				// update required plugins
				foreach ($allPlugins as $id => $p) {
					$desc = $this->updatePlugin($id, $p);
					if ($desc) $updates[] = $desc;
				}

				$this->db->commit();
			} catch (ServiceException $e) {
				$this->db->rollback();
				$this->setError("Update failed", "<code>".$e->details()."</code>");
				$this->showPage("update_error");
			}
			
			$this->setData("updates", $updates);
			$this->session()->reset();
			$this->showPage("success");
		}
		
		private function updateSystem() {
			$installed = $this->installedVersion();
			$current = $this->currentVersion();
			
			if (strcmp($installed, $current) == 0) return;
			
			if (!in_array($installed, self::$versionHistory)) {
				$this->setError("Unknown version", "Installed version (".$this->versionString($installed).") is unknown, and updater cannot continue.");
				$this->showPage("update_error");
			}

			if (!in_array($current, self::$versionHistory)) {
				$this->setError("Updater error", "Mollify updater does not contain the update required to update to current version, report a new updater issue at <a href='http://code.google.com/p/mollify/issues/list'>issue list</a>");
				$this->showPage("update_error");
			}

			$indexFrom = array_search($installed, self::$versionHistory) + 1;
			$indexTo = array_search($current, self::$versionHistory);
			$stepFrom = $installed;

			for ($i = $indexFrom; $i <= $indexTo; $i++) {
				$stepTo = self::$versionHistory[$i];
				$this->util()->updateVersionStep($stepFrom, $stepTo);
				$stepFrom = $stepTo;
			}
			
			return "Mollify updated to ".$this->versionString($current);
		}
		
		private function updatePlugin($id, $plugin) {
			if ($plugin->version() == NULL) return;
			
			$installed = $this->pluginInstalledVersion($id);
			$current = $plugin->version();
			$versionHistory = $plugin->versionHistory();
			
			if (strcmp($installed, $current) == 0) return;

			if ($installed != NULL and !in_array($installed, $versionHistory)) {
				$this->setError("Unknown version", "Plugin ".$id." installed version (".$this->versionString($installed).") is unknown, and updater cannot continue.");
				$this->showPage("update_error");
			}

			if (!in_array($current, $versionHistory)) {
				$this->setError("Updater error", "Plugin ".$id." does not contain the update required, and cannot continue");
				$this->showPage("update_error");
			}
			
			if ($installed == NULL) {
				$this->util()->execPluginCreateTables($id);
				return "Plugin ".$id." installed";
			} else {
				$indexFrom = array_search($installed, $versionHistory) + 1;
				$indexTo = array_search($current, $versionHistory);
				$stepFrom = $installed;

				for ($i = $indexFrom; $i <= $indexTo; $i++) {
					$stepTo = $versionHistory[$i];
					$this->util()->updateVersionStep($stepFrom, $stepTo);
					$stepFrom = $stepTo;
				}
				return "Plugin ".$id." updated to ".$this->versionString($current);
			}
		}
		
		public function updateSummary() {
			$result = '';

			$systemInstalled = $this->installedVersion();
			$systemCurrent = $this->currentVersion();
			
			if (strcmp($systemInstalled, $systemCurrent) != 0) {
				$result .= 'Mollify system requires an update to version <b>'.$this->versionString($systemCurrent).'</b>';
			} else {
				$result .= 'Mollify system is up-to-date.';
			}

			$installedPlugins = array();
			$updatedPlugins = array();
			$allPlugins = $this->plugins()->getPlugins();
			
			foreach ($allPlugins as $id => $p) {
				if ($p->version() == NULL) continue;
				$installed = $this->pluginInstalledVersion($id);
				$current = $p->version();
				
				if ($installed == NULL) {
					$installedPlugins[] = $id;
				} else if (strcmp($installed, $current) != 0) {
					$updatedPlugins[] = $id;
				}
			}
			
			if (count($installedPlugins) > 0) {		
				$result .= '</p><p>Following plugins require installation:<ul>';
				foreach ($installedPlugins as $id) {
					$p = $allPlugins[$id];
					$result .= '<li>'.$id.' (version <b>'.$this->versionString($p->version()).'</b>)</li>';
				}
				$result .= '</ul>';
			}
			if (count($updatedPlugins) > 0) {		
				$result .= '</p><p>Following plugins require update:<ul>';
				foreach ($updatedPlugins as $id) {
					$p = $allPlugins[$id];
					$result .= '<li>'.$id.' (version <b>'.$this->versionString($p->version()).'</b>)</li>';
				}
				$result .= '</ul>';
			}
			
			return $result;
		}
		
		public function __toString() {
			return "MySQLUpdater";
		}
	}
?>