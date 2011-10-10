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

	class FilePermissionDao {
		private $uacName;
		
		public function __construct($uacName) {
			$this->uacName = $uacName;
		}
		
		public function getItemPermission($item, $userId) {
			$this->assertLocalFilesystem($item);
			$permissions = $this->readPermissionsFromFile($this->getUacFilename($item));
			
			$match = FALSE;
			$id = $this->getPermissionId($item);
			
			if (array_key_exists($id, $permissions)) $match = $permissions[$id];
			if (!$match and $item->isFile() and array_key_exists(".", $permissions)) $match = $permissions["."];
			if (!$match) return FALSE;
			
			return $this->getEffectivePermission($match, $userId);
		}
		
		public function getItemPermissions($item) {
			$this->assertLocalFilesystem($item);
			
			$permissions = $this->readPermissionsFromFile($this->getUacFilename($item));
			if ($permissions === FALSE) return FALSE;
			$id = $this->getPermissionId($item);
			
			$result = array();
			if (array_key_exists($id, $permissions)) {
				foreach ($permissions[$id] as $id => $permission) {
					if ($id === "*") $permission_id = "0";
					else $permission_id = "".$id;
					
					$result[] = array("item_id" => $item->id(), "user_id" => $permission_id, "permission" => $permission);
				}
			}
			return $result;
		}

		public function getAllItemPermissions($parent, $userId) {
			$this->assertLocalFilesystem($parent);
			
			$permissions = $this->readPermissionsFromFile($this->getUacFilename($parent));
			if ($permissions === FALSE) return array();
			
			$result = array();
			foreach ($permissions as $item => $itemPermissions) {
				$itemId = $parent->id().DIRECTORY_SEPARATOR.$item;
				if (array_key_exists($userId, $itemPermissions)) $result[$itemId] = $itemPermissions[$userId];
				else if (array_key_exists("*", $itemPermissions)) $result[$itemId] = $itemPermissions["*"];
			}
			return $result;
		}

		public function moveItemPermissions($from, $to) {
			$this->assertLocalFilesystem($from);
			$this->assertLocalFilesystem($to);

			$fromPath = dirname($from->filesystem()->localPath($from));
			$fromName = $from->name();
			$fromId = $this->getPermissionId($from);
	
			$toPath = dirname($to->filesystem()->localPath($to));
			$toName = $to->name();
			$toId = $this->getPermissionId($to);
	
			$fromUac = $this->getUacFilename($from);
			$fromPermissions = $this->readPermissionsFromFile($fromUac);
			if (!$fromPermissions or !array_key_exists($fromId, $fromPermissions)) return;
			
			$itemPermissions = $fromPermissions[$fromId];
			unset($fromPermissions[$fromId]);
			
			if ($toPath === $fromPath) $fromPermissions[$toId] = $itemPermissions;
			$this->writePermissionsToFile($fromUac, $fromPermissions);
			
			if ($toPath != $fromPath) {
				$toUac = $this->getUacFilename($to);
				$toPermissions = $this->readPermissionsFromFile($toUac);
				if (!$toPermissions) $toPermissions = array();
				
				$toPermissions[$toId] = $itemPermissions;
				$this->writePermissionsToFile($toUac, $toPermissions);
			}
		}
		
		public function addItemPermission($item, $permission, $userId) {
			$this->updateItemPermissions($item, array(array("user_id" => $userId, "permission" => $permission), array(), array()));
		}
		
		public function updateItemPermissions($item, $new, $modified, $removed) {
			$this->assertLocalFilesystem($item);
			
			$uacFile = $this->getUacFilename($item);
			$permissions = $this->readPermissionsFromFile($uacFile);
			if (!$permissions) $permissions = array();
	
			$id = $this->getPermissionId($item);
			if (!array_key_exists($id, $permissions)) $permissions[$id] = array();
			$list = $permissions[$id];
			
			foreach(array_merge($new, $modified) as $permission) {
				$this->assertItemPermission($item, $permission);
				$userId = "*";
				if ($permission["user_id"] != NULL) $userId = $permission["user_id"];
				
				$list[$userId] = $permission["permission"];
			}
			
			foreach($removed as $permission) {
				$this->assertItemPermission($item, $permission);
				$userId = "*";
				if ($permission["user_id"] != NULL) $userId = $permission["user_id"];
				
				unset($list[$userId]);
			}
			
			if (count($list) === 0) unset($permissions[$id]);
			else $permissions[$id] = $list;
			
			if (Logging::isDebug())
				Logging::logDebug("Permissions updated (".$item->id()."): ".Util::array2str($permissions));

			$this->writePermissionsToFile($uacFile, $permissions);
		}
		
		public function removeItemPermissions($item) {
			$this->assertLocalFilesystem($item);
			
			$id = $this->getPermissionId($item);
			$uacFile = $this->getUacFilename($item);
			$permissions = $this->readPermissionsFromFile($uacFile);
			
			if (!$permissions or !array_key_exists($id, $permissions)) return;
			unset($permissions[$id]);
			$this->writePermissionsToFile($uacFile, $permissions);
		}
		
		private function assertItemPermission($item, $permission) {
			if ($permission["item_id"] != $item->id()) throw new ServiceException("INVALID_REQUEST", "Permission update for multiple item ids is not allowed");
		}
		
		private function getEffectivePermission($permissions, $userId) {
			if ($userId != "" and isset($permissions[$userId])) return strtoupper($permissions[$userId]);
			if (isset($permissions["*"])) return strtoupper($permissions["*"]);
			return FALSE;
		}
		
		private function getPermissionId($item) {
			if (!$item->isFile()) return ".";
			return $item->name();
		}
		
		private function getUacFilename($item) {
			$path = $item->filesystem()->localPath($item);
			if (!is_dir($path))
				$path = dirname($path);
	
			return $path.DIRECTORY_SEPARATOR.$this->uacName;
		}
		
		private function readPermissionsFromFile($uacFile) {
			$result = array();
			if (!file_exists($uacFile)) return $result;
		
			$handle = @fopen($uacFile, "r");
			if (!$handle) return $result;
			
			$i = 0;
		    while (!feof($handle)) {
		        $line = fgets($handle, 4096);
				$i = $i + 1;
				
				$parts = explode(chr(9), $line);
				if (count($parts) < 2) continue;
				
				$file = trim($parts[0], '" ');
				$data = trim($parts[count($parts) - 1]);
				
				$permissions = $this->parsePermissionString($data);
				if (!$permissions) {
					Logging.logError("Invalid file permission definition in file [".$uacFile."] at line ".$i);
					continue;
				}
							
				$result[$file] = $permissions;			
		    }
		    fclose($handle);
			
			return $result;
		}
		
		private function writePermissionsToFile($uacFile, $permissionTable) {
			if (file_exists($uacFile)) {
				if (!is_writable($uacFile))
					throw new ServiceException("REQUEST_FAILED", "Permission file (".$uacFile.") is not writable");
			} else {
				$dir = dirname($uacFile);
				if (!is_writable($dir))
					throw new ServiceException("REQUEST_FAILED", "Directory for permission file (".$dir.") is not writable");
			}
		
			$handle = @fopen($uacFile, "w");
			if (!$handle)
				throw new ServiceException("REQUEST_FAILED", "Could not open permission file for writing: ".$uacFile);
			
			foreach($permissionTable as $file => $permissions) {
				$value = $this->formatPermissionString($permissions);
				fwrite($handle, sprintf("\"%s\"\t%s\n", $file, $value));
			}
	
			fclose($handle);
		}
		
		private function parsePermissionString($string) {
			$result = array();
			if (strlen($string) < 1) return $result;
			
			$parts = explode(',', $string);
			if (count($parts) < 1) return $result;
			
			foreach($parts as $part) {
				$valueParts = explode('=', $part);
				if (count($valueParts) != 2) return FALSE;
	
				$id = trim($valueParts[0]);
				$permission = strtolower(trim($valueParts[1]));
				if (strlen($id) == 0 or strlen($permission) == 0) return FALSE;
	
				$result[$id] = $permission;
			}
			return $result;
		}
		
		private function formatPermissionString($permissions) {
			$result = "";
			if (count($permissions) < 1) return $result;
			
			$first = TRUE;
			foreach($permissions as $id => $permission) {
				if (!$first) $result .= ',';
				$result .= sprintf("%s=%s", $id, strtolower($permission));
				$first = FALSE;
			}
			return $result;
		}
		
		private function assertLocalFilesystem($item) {
			if ($item->filesystem()->type() != MollifyFilesystem::TYPE_LOCAL) throw new ServiceException("INVALID_CONFIGURATION", "Unsupported filesystem with file descriptions: ".get_class($item->filesystem()));
		}
	}
?>