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

	class MySQLConfigurationProvider extends ConfigurationProvider {
		const VERSION = "1_6_0";
		
		private $db;
		
		public function __construct($settings) {
			global $DB_HOST, $DB_USER, $DB_PASSWORD, $DB_DATABASE, $DB_TABLE_PREFIX;
			
			if (!isset($DB_USER) or !isset($DB_PASSWORD)) throw new ServiceException("INVALID_CONFIGURATION", "No database information defined");
			
			if (isset($DB_HOST)) $host = $DB_HOST;
			else $host = "localhost";
			
			if (isset($DB_DATABASE)) $database = $DB_DATABASE;
			else $database = "mollify";

			if (isset($DB_TABLE_PREFIX)) $tablePrefix = $DB_TABLE_PREFIX;
			else $tablePrefix = "";
			
			require_once("include/mysql/MySQLDatabase.class.php");
			$this->db = new MySQLDatabase($host, $DB_USER, $DB_PASSWORD, $database, $tablePrefix);
			$this->db->connect();
		}
		
		public function getType() {
			return ConfigurationProvider::TYPE_DATABASE;
		}
		
		public function db() {
			return $this->db;
		}
		
		public function isAuthenticationRequired() {
			return TRUE;
		}

		public function getSupportedFeatures() {
			return array('change_password', 'description_update', 'administration', 'permission_update', 'user_groups');
		}
		
		public function featureEnabledByDefault($name, $default) {
			if ($name === 'event_logging') return FALSE;
			return TRUE;
		}
		
		public function getInstalledVersion() {
			return $this->db->query("SELECT value FROM ".$this->db->table("parameter")." WHERE name='version'")->value(0);
		}
		
		public function checkProtocolVersion($version) {}
	
		public function findUser($username, $password) {
			$result = $this->db->query(sprintf("SELECT id, name FROM ".$this->db->table("user")." WHERE name='%s' AND password='%s'", $this->db->string($username), $this->db->string($password)));
			$matches = $result->count();
			
			if ($matches === 0) {
				Logging::logError("No user found with name [".$username."], or password was invalid");
				return NULL;
			}
			
			if ($matches > 1) {
				Logging::logError("Duplicate user found with name [".$username."] and password");
				return FALSE;
			}
			
			return $result->firstRow();
		}

		public function getUserByName($username) {
			$result = $this->db->query(sprintf("SELECT id, name, password FROM ".$this->db->table("user")." WHERE name='%s'", $this->db->string($username)));
			$matches = $result->count();
			
			if ($matches === 0) {
				Logging::logError("No user found with name [".$username."]");
				return NULL;
			}
			
			if ($matches > 1) {
				Logging::logError("Duplicate user found with name [".$username."]");
				return FALSE;
			}
			
			return $result->firstRow();
		}
		
		public function getAllUsers() {
			return $this->db->query("SELECT id, name, email, permission_mode FROM ".$this->db->table("user")." where is_group = 0 ORDER BY id ASC")->rows();
		}

		public function getUser($id) {
			return $this->db->query(sprintf("SELECT id, name, email FROM ".$this->db->table("user")." WHERE id='%s'", $this->db->string($id)))->firstRow();
		}
		
		public function addUser($name, $pw, $email, $permission) {
			$this->db->update(sprintf("INSERT INTO ".$this->db->table("user")." (name, password, email, permission_mode, is_group) VALUES ('%s', '%s', %s, '%s', 0)", $this->db->string($name), $this->db->string($pw), $this->db->string($email, TRUE), $this->db->string($permission)));
			return $this->db->lastId();
		}
	
		public function updateUser($id, $name, $email, $permission, $description = NULL) {
			$affected = $this->db->update(sprintf("UPDATE ".$this->db->table("user")." SET name='%s', email=%s, permission_mode='%s', description='%s' WHERE id='%s'", $this->db->string($name), $this->db->string($email, TRUE), $this->db->string($permission), $this->db->string($description), $this->db->string($id)));			
			return TRUE;
		}
		
		public function removeUser($userId) {
			$id = $this->db->string($userId);

			$this->db->startTransaction();
			$this->db->update(sprintf("DELETE FROM ".$this->db->table("user_folder")." WHERE user_id='%s'", $id));
			$this->db->update(sprintf("DELETE FROM ".$this->db->table("user_group")." WHERE user_id='%s'", $id));
			$this->db->update(sprintf("DELETE FROM ".$this->db->table("item_permission")." WHERE user_id='%s'", $id));
			$affected = $this->db->update(sprintf("DELETE FROM ".$this->db->table("user")." WHERE id='%s'", $id));
			if ($affected === 0)
				throw new ServiceException("INVALID_REQUEST", "Invalid delete user request, user ".$id." not found");	
			$this->db->commit();					
			return TRUE;
		}

		public function getAllUserGroups() {
			return $this->db->query("SELECT id, name, description, is_group FROM ".$this->db->table("user")." where is_group = 1 ORDER BY id ASC")->rows();
		}

		public function getUserGroup($id) {
			return $this->getUser($id);
		}

		public function getUsersGroups($userId) {
			return $this->db->query("select id, name, description from ".$this->db->table("user")." where id in (SELECT user_group.group_id FROM ".$this->db->table("user")." as user, ".$this->db->table("user_group")." as user_group where user_group.user_id = user.id and user.id = '".$this->db->string($userId)."') ORDER BY id ASC")->rows();
		}
		
		public function addUsersGroups($userId, $groupIds) {
			$this->db->startTransaction();
			foreach($groupIds as $id) {
				$this->db->update("INSERT INTO ".$this->db->table("user_group")." (group_id, user_id) VALUES (".$this->db->string($id).",".$this->db->string($userId).")");
			}
			$this->db->commit();
			return TRUE;
		}

		public function getGroupUsers($id) {
			return $this->db->query("SELECT user.id, user.name, user.permission_mode FROM ".$this->db->table("user")." as user, ".$this->db->table("user_group")." as user_group where user_group.user_id = user.id and user_group.group_id = '".$this->db->string($id)."' ORDER BY user.id ASC")->rows();
		}

		public function addGroupUsers($groupId, $userIds) {
			$this->db->startTransaction();
			foreach($userIds as $id) {
				$this->db->update("INSERT INTO ".$this->db->table("user_group")." (group_id, user_id) VALUES (".$this->db->string($groupId).",".$this->db->string($id).")");
			}
			$this->db->commit();
			return TRUE;
		}

		public function removeGroupUsers($groupId, $userIds = NULL) {
			if ($userIds == NULL) $this->db->update("DELETE FROM ".$this->db->table("user_group")."  WHERE group_id = '".$this->db->string($groupId)."'");
			else $this->db->update("DELETE FROM ".$this->db->table("user_group")." WHERE group_id = '".$this->db->string($groupId)."' and user_id in (".$this->db->arrayString($userIds).")");
			return TRUE;
		}
		
		public function addUserGroup($name, $description) {
			$this->db->update(sprintf("INSERT INTO ".$this->db->table("user")." (name, description, password, permission_mode, is_group) VALUES ('%s', '%s', NULL, NULL, 1)", $this->db->string($name), $this->db->string($description)));
			return TRUE;
		}

		public function updateUserGroup($id, $name, $description) {
			return $this->updateUser($id, $name, NULL, NULL, $description);
		}
		
		public function removeUserGroup($id) {
			$this->db->startTransaction();
			$this->removeGroupUsers($id);
			$this->removeUser($id);
			$this->db->commit();
			return TRUE;
		}

		public function getPassword($userId) {
			return $this->db->query(sprintf("SELECT password FROM ".$this->db->table("user")." WHERE id=%s", $this->db->string($userId)))->value(0);
		}
	
		public function changePassword($id, $new) {
			$affected = $this->db->update(sprintf("UPDATE ".$this->db->table("user")." SET password='%s' WHERE id=%s", $this->db->string($new), $this->db->string($id)));
			return TRUE;
		}
	
		public function getFolders() {
			return $this->db->query("SELECT id, name, path FROM ".$this->db->table("folder")." ORDER BY id ASC")->rows();
		}

		public function getFolder($id) {
			return $this->db->query(sprintf("SELECT id, name, path FROM ".$this->db->table("folder")." where id='%s'", $this->db->string($id)))->firstRow();
		}
		
		public function getFolderUsers($id) {
			return $this->db->query("SELECT user.id, user.name, user.permission_mode FROM ".$this->db->table("user")." as user, ".$this->db->table("user_folder")." as user_folder where user_folder.user_id = user.id and user_folder.folder_id = '".$this->db->string($id)."' ORDER BY user.id ASC")->rows();
		}

		public function addFolderUsers($folderId, $userIds) {
			$this->db->startTransaction();
			foreach($userIds as $id) {
				$this->db->update("INSERT INTO ".$this->db->table("user_folder")." (folder_id, user_id) VALUES (".$this->db->string($folderId).",".$this->db->string($id).")");
			}
			$this->db->commit();
			return TRUE;
		}

		public function removeFolderUsers($folderId, $userIds) {
			$this->db->update("DELETE FROM ".$this->db->table("user_folder")." WHERE folder_id = '".$this->db->string($folderId)."' and user_id in (".$this->db->arrayString($userIds).")");
			return TRUE;
		}

		public function addFolder($name, $path) {
			$this->db->update(sprintf("INSERT INTO ".$this->db->table("folder")." (name, path) VALUES ('%s', '%s')", $this->db->string($name), $this->db->string($path)));
			return $this->db->lastId();
		}
	
		public function updateFolder($id, $name, $path) {
			$this->db->update(sprintf("UPDATE ".$this->db->table("folder")." SET name='%s', path='%s' WHERE id='%s'", $this->db->string($name), $this->db->string($path), $this->db->string($id)));
			return TRUE;
		}
		
		public function removeFolder($id) {
			$rootItem = $this->env->filesystem()->filesystemFromId($id, FALSE)->root();
			$rootId = $this->itemId($rootItem);
			$folderId = $this->db->string($id);
			
			$this->db->startTransaction();
			$this->db->update(sprintf("DELETE FROM ".$this->db->table("user_folder")." WHERE folder_id='%s'", $folderId));
			$this->db->update(sprintf("DELETE FROM ".$this->db->table("item_description")." WHERE item_id like '%s%%'", $rootId));
			$this->db->update(sprintf("DELETE FROM ".$this->db->table("item_permission")." WHERE item_id like '%s%%'", $rootId));
			$affected = $this->db->update(sprintf("DELETE FROM ".$this->db->table("folder")." WHERE id='%s'", $folderId));
			if ($affected === 0)
				throw new ServiceException("INVALID_REQUEST","Invalid delete folder request, folder ".$rootId." not found");
			$this->db->commit();
			return TRUE;
		}

		public function getUserFolders($userId) {
			$folderTable = $this->db->table("folder");
			$userFolderTable = $this->db->table("user_folder");
			
			return $this->db->query(sprintf("SELECT ".$folderTable.".id, ".$userFolderTable.".name, ".$folderTable.".name as default_name, ".$folderTable.".path FROM ".$userFolderTable.", ".$folderTable." WHERE user_id='%s' AND ".$folderTable.".id = ".$userFolderTable.".folder_id", $this->db->string($userId)))->rows();
		}
		
		public function addUserFolders($userId, $folderIds) {
			foreach($folderIds as $id) $this->addUserFolder($userId, $id, NULL);
		}
		
		public function addUserFolder($userId, $folderId, $name) {
			if ($name != NULL) {
				$this->db->update(sprintf("INSERT INTO ".$this->db->table("user_folder")." (user_id, folder_id, name) VALUES ('%s', '%s', '%s')", $this->db->string($userId), $this->db->string($folderId), $this->db->string($name)));
			} else {
				$this->db->update(sprintf("INSERT INTO ".$this->db->table("user_folder")." (user_id, folder_id, name) VALUES ('%s', '%s', NULL)", $this->db->string($userId), $this->db->string($folderId)));
			}
						
			return TRUE;
		}
	
		public function updateUserFolder($userId, $folderId, $name) {
			if ($name != NULL) {
				$this->db->update(sprintf("UPDATE ".$this->db->table("user_folder")." SET name='%s' WHERE user_id='%s' AND folder_id='%s'", $this->db->string($name), $this->db->string($userId), $this->db->string($folderId)));
			} else {
				$this->db->update(sprintf("UPDATE ".$this->db->table("user_folder")." SET name = NULL WHERE user_id='%s' AND folder_id='%s'", $this->db->string($userId), $this->db->string($folderId)));
			}
	
			return TRUE;
		}
		
		public function removeUserFolder($userId, $folderId) {
			$this->db->update(sprintf("DELETE FROM ".$this->db->table("user_folder")." WHERE folder_id='%s' AND user_id='%s'", $this->db->string($folderId), $this->db->string($userId)));
			return TRUE;
		}
		
		public function getDefaultPermission($userId = "") {
			$mode = strtoupper($this->db->query(sprintf("SELECT permission_mode FROM ".$this->db->table("user")." WHERE id='%s'", $this->db->string($userId)))->value(0));
			$this->env->authentication()->assertPermissionValue($mode);
			return $mode;
		}
		
		function getItemDescription($item) {
			$result = $this->db->query(sprintf("SELECT description FROM ".$this->db->table("item_description")." WHERE item_id='%s'", $this->itemId($item)));
			if ($result->count() < 1) return NULL;
			return $result->value(0);
		}
				
		function setItemDescription($item, $description) {
			$id = $this->itemId($item);
			$desc = $this->db->string($description);
			$exists = $this->db->query(sprintf("SELECT COUNT(item_id) FROM ".$this->db->table("item_description")." WHERE item_id='%s'", $id))->value(0) > 0;
			
			if ($exists)
				$this->db->update(sprintf("UPDATE ".$this->db->table("item_description")." SET description='%s' WHERE item_id='%s'", $desc, $id));
			else
				$this->db->update(sprintf("INSERT INTO ".$this->db->table("item_description")." (item_id, description) VALUES ('%s','%s')", $id, $desc));
			return TRUE;
		}
	
		function removeItemDescription($item) {
			if (!$item->isFile()) {
				$this->db->update(sprintf("DELETE FROM ".$this->db->table("item_description")." WHERE item_id like '%s%%'", $this->itemId($item)));
			} else {
				$this->db->update(sprintf("DELETE FROM ".$this->db->table("item_description")." WHERE item_id='%s'", $this->itemId($item)));
			}
			return TRUE;
		}
		
		function moveItemDescription($from, $to) {
			$fromId = $this->itemId($from);
			
			if (!$from->isFile()) {
				$this->db->update(sprintf("UPDATE ".$this->db->table("item_description")." SET item_id=CONCAT('%s', SUBSTR(item_id, %d)) WHERE item_id like '%s%%'", $this->itemId($to), strlen($fromId)+1, $fromId));
			} else {
				$this->db->update(sprintf("UPDATE ".$this->db->table("item_description")." SET item_id='%s' WHERE item_id='%s'", $this->itemId($to), $fromId));
			}
					
			return TRUE;
		}
					
		function getItemPermission($item, $userId) {
			$table = $this->db->table("item_permission");
			$id = $this->itemId($item);
			$userIds = array($userId);
			if ($this->env->authentication()->hasUserGroups()) {
				foreach($this->env->authentication()->getUserGroups() as $g)
					$userIds[] = $g['id'];
			}
			
			$userQuery = sprintf("(user_id in (%s))", $this->db->arrayString($userIds));
			$query = NULL;
	
			if ($item->isFile()) {
				$parent = $item->parent();
				
				if ($parent != NULL) {
					$parentId = $this->itemId($parent);					
					$query = sprintf(
						"(SELECT permission, user_id, 1 AS 'index' FROM `".$table."` WHERE item_id = '%s' AND %s) UNION ALL ".
						"(SELECT permission, user_id, 2 AS 'index' FROM `".$table."` WHERE item_id = '%s' AND user_id = 0) UNION ALL ".
						"(SELECT permission, user_id, 3 AS 'index' FROM `".$table."` WHERE item_id = '%s' AND %s) UNION ALL ".
						"(SELECT permission, user_id, 4 AS 'index' FROM `".$table."` WHERE item_id = '%s' AND user_id = 0)", $id, $userQuery, $id, $parentId, $userQuery, $parentId);
				}
			}
			
			if ($query === NULL) {
				$query = sprintf(
					"(SELECT permission, user_id, 1 AS 'index' FROM `".$table."` WHERE item_id = '%s' AND %s) UNION ALL ".
					"(SELECT permission, user_id, 2 AS 'index' FROM `".$table."` WHERE item_id = '%s' AND user_id = 0)", $id, $userQuery, $id);
			}
			
			$query = "SELECT permission FROM (".$query.") AS u ORDER BY u.index ASC, u.permission DESC";
			
			$result = $this->db->query($query);
			if ($result->count() < 1) return NULL;
			return $result->value(0);
		}
		
		public function getAllItemPermissions($parent, $userId) {
			$table = $this->db->table("item_permission");
			$userIds = array($userId);
			if ($this->env->authentication()->hasUserGroups()) {
				foreach($this->env->authentication()->getUserGroups() as $g)
					$userIds[] = $g['id'];
			}
			$userIds[] = "0";
			$userQuery = sprintf("(user_id in (%s))", $this->db->arrayString($userIds));

			$itemFilter = "SELECT distinct item_id from `".$table."` where ".$userQuery." and item_id REGEXP '^".$this->itemId($parent)."[^/]*[/]?$'";
			$query = sprintf('SELECT item_id, permission, if(`user_id` = "0", 0, 1) as ind from `'.$table.'` where '.$userQuery.' and item_id in ('.$itemFilter.') order by item_id asc, ind desc, permission desc');
			
			$all = $this->db->query($query)->rows();
			$k = array();
			$prev = NULL;
			foreach($all as $p) {
				$id = $p["item_id"];
				if ($id != $prev) $k[$id] = strtoupper($p["permission"]);
				$prev = $id;
			}
			return $k;
		}
	
		function getItemPermissions($item) {
			$id = $this->itemId($item);
			$rows = $this->db->query(sprintf("SELECT user.id as user_id, user.is_group as is_group, item_permission.permission as permission FROM `".$this->db->table("item_permission")."` as item_permission LEFT OUTER JOIN `".$this->db->table("user")."` as user ON user.id = item_permission.user_id WHERE item_permission.item_id = '%s'", $id))->rows();
			
			$list = array();
			foreach ($rows as $row) {
				if (!isset($row["user_id"]))
					$list[] = array("item_id" => $item->id(), "user_id" => '0', "is_group" => 0, "permission" => $row["permission"]);
				else
					$list[] = array("item_id" => $item->id(), "user_id" => $row["user_id"], "is_group" => $row["is_group"], "permission" => $row["permission"]);
			}
			return $list;
		}
			
		function updateItemPermissions($updates) {
			$new = $updates['new'];
			$modified = $updates['modified'];
			$removed = $updates['removed'];
			
			$this->db->startTransaction();
			if (count($new) > 0) $this->addItemPermissionValues($new);
			if (count($modified) > 0) $this->updateItemPermissionValues($modified);
			if (count($removed) > 0) $this->removeItemPermissionValues($removed);
			$this->db->commit();
							
			return TRUE;
		}

		private function addItemPermissionValues($list) {
			$query = "INSERT INTO `".$this->db->table("item_permission")."` (item_id, user_id, permission) VALUES ";
			$first = TRUE;
			
			foreach($list as $item) {
				$permission = $this->db->string(strtolower($item["permission"]));
				$id = $this->db->string(base64_decode($item["item_id"]));
				$user = '0';
				if ($item["user_id"] != NULL) $user = $this->db->string($item["user_id"]);
				
				if (!$first) $query .= ',';
				$query .= sprintf(" ('%s', '%s', '%s')", $id, $user, $permission);
				$first = FALSE;
			}
			
			$this->db->update($query);							
			return TRUE;
		}
		
		public function addItemPermission($id, $permission, $userId) {
			$permission = $this->db->string(strtolower($permission));
			$id = $this->db->string($id);
			$user = $this->db->string($userId);

			$query = sprintf("INSERT INTO `".$this->db->table("item_permission")."` (item_id, user_id, permission) VALUES ('%s', '%s', '%s')", $id, $user, $permission);
			$this->db->update($query);							
			return TRUE;
		}
	
		private function updateItemPermissionValues($list) {
			foreach($list as $item) {
				$permission = $this->db->string(strtolower($item["permission"]));
				$id = $this->db->string(base64_decode($item["item_id"]));
				$user = '0';
				if ($item["user_id"] != NULL) $user = $this->db->string($item["user_id"]);
			
				$this->db->update(sprintf("UPDATE `".$this->db->table("item_permission")."` SET permission='%s' WHERE item_id='%s' and user_id='%s'", $permission, $id, $user));
			}
							
			return TRUE;
		}
	
		private function removeItemPermissionValues($list) {
			foreach($list as $item) {
				$id = $this->db->string(base64_decode($item["item_id"]));
				$user = "user_id = '0'";
				if ($item["user_id"] != NULL) $user = sprintf("user_id = '%s'", $this->db->string($item["user_id"]));
				$this->db->update(sprintf("DELETE FROM `".$this->db->table("item_permission")."` WHERE item_id='%s' AND %s", $id, $user));
			}
							
			return TRUE;
		}

		function removeItemPermissions($item) {
			if (!$item->isFile()) {
				$this->db->update(sprintf("DELETE FROM `".$this->db->table("item_permission")."` WHERE item_id like '%s%%'", $this->itemId($item)));
			} else {
				$this->db->update(sprintf("DELETE FROM `".$this->db->table("item_permission")."` WHERE item_id='%s'", $this->itemId($item)));
			}
			return TRUE;
		}
		
		function moveItemPermissions($from, $to) {
			$fromId = $this->itemId($from);
			$toId = $this->itemId($to);
			
			if (!$from->isFile()) {
				$this->db->update(sprintf("UPDATE `".$this->db->table("item_permission")."` SET item_id=CONCAT('%s', SUBSTR(item_id, %d)) WHERE item_id like '%s%%'", $toId, strlen($fromId)+1, $fromId));
			} else {
				$this->db->update(sprintf("UPDATE `".$this->db->table("item_permission")."` SET item_id='%s' WHERE item_id='%s'", $toId, $fromId));
			}
					
			return TRUE;
		}
		
		private function itemId($item) {
			return $this->db->string($item->id());
		}
	}
?>