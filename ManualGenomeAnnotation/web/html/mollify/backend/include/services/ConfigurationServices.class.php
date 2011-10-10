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

	class ConfigurationServices extends ServicesBase {
		private static $ITEMS = array("users", "usergroups", "usersgroups", "folders");
		
		protected function isValidPath($method, $path) {
			if (count($path) == 0) return FALSE;
			if (!in_array($path[0], self::$ITEMS)) return FALSE;
			return TRUE;
		}
		
		public function processGet() {
			$this->env->authentication()->assertAdmin();
			
			switch($this->path[0]) {
				case 'users':
					$this->processGetUsers();
					break;
				case 'usergroups':
					$this->processGetUserGroups();
					break;
				case 'usersgroups':
					$this->processGetUsersAndGroups();
					break;
				case 'folders':
					$this->processGetFolders();
					break;
				default:
					throw $this->invalidRequestException();
			}
		}
		
		public function processPost() {
			$this->env->authentication()->assertAdmin();
			
			switch($this->path[0]) {
				case 'users':
					$this->processPostUsers();
					break;
				case 'usergroups':
					$this->processPostUserGroups();
					break;
				case 'folders':
					$this->processPostFolders();
					break;
				default:
					throw $this->invalidRequestException();
			}
		}
		
		public function processPut() {
			if (count(array_diff(array("users", "current", "password"), $this->path)) > 0)
				$this->env->authentication()->assertAdmin();
			
			switch($this->path[0]) {
				case 'users':
					$this->processPutUsers();
					break;
				case 'usergroups':
					$this->processPutUserGroups();
					break;
				case 'folders':
					$this->processPutFolders();
					break;
				default:
					throw $this->invalidRequestException();
			}
		}

		public function processDelete() {
			$this->env->authentication()->assertAdmin();
			
			switch($this->path[0]) {
				case 'users':
					$this->processDeleteUsers();
					break;
				case 'usergroups':
					$this->processDeleteUserGroups();
					break;
				case 'folders':
					$this->processDeleteFolders();
					break;
				default:
					throw $this->invalidRequestException();
			}
		}

		private function processGetUsersAndGroups() {
			if (count($this->path) == 1) {
				$this->response()->success(array(
					"users" => $this->env->configuration()->getAllUsers(),
					"groups" => $this->env->features()->isFeatureEnabled("user_groups") ? $this->env->configuration()->getAllUserGroups() : array()
				));
				return;
			}
			throw $this->invalidRequestException();
		}		
		
		private function processGetUsers() {
			if (count($this->path) == 1) {
				$this->response()->success($this->env->configuration()->getAllUsers());
				return;
			}
			$userId = $this->path[1];
			if (count($this->path) == 2) {
				$this->response()->success($this->env->configuration()->getUser($userId));
				return;
			}
			if (count($this->path) == 3)
				switch($this->path[2]) {
					case 'groups':
						$this->response()->success($this->env->configuration()->getUsersGroups($userId));
						return;
					case 'folders':
						$this->response()->success($this->env->configuration()->getUserFolders($userId));
						return;
			}
			throw $this->invalidRequestException();
		}		
		
		private function processPostUsers() {
			if (!$this->request->hasData()) throw $this->invalidRequestException();
			
			if (count($this->path) == 1) {
				$user = $this->request->data;
				if (!isset($user['name']) or !isset($user['password']) or !isset($user['permission_mode'])) throw $this->invalidRequestException();
				$user['permission_mode'] = strtoupper($user['permission_mode']);
				$this->env->authentication()->assertPermissionValue($user['permission_mode']);
				
				$this->env->configuration()->addUser($user['name'], $user['password'], isset($user['email']) ? $user['email'] : NULL, $user['permission_mode']);
				$this->response()->success(TRUE);
				return;
			}
			if (count($this->path) == 3) {
				$userId = $this->path[1];
				
				switch ($this->path[2]) {
					case 'groups':
						$groups = $this->request->data;
						$this->response()->success($this->env->configuration()->addUsersGroups($userId, $groups));
						return;
					case 'folders':
						$folder = $this->request->data;
						if (!isset($folder['id'])) throw $this->invalidRequestException();
						
						$this->env->configuration()->addUserFolder($userId, $folder['id'], isset($folder['name']) ? $folder['name'] : NULL);
						$this->response()->success(TRUE);
						return;
				}
			}
			throw $this->invalidRequestException();
		}

		private function processPutUsers() {
			if (count($this->path) < 2 or !$this->request->hasData()) throw $this->invalidRequestException();
			$userId = $this->path[1];
			
			// users/xx
			if (count($this->path) == 2) {
				$user = $this->request->data;
				if (!isset($user['name']) or !isset($user['permission_mode'])) throw $this->invalidRequestException();
				$user['permission_mode'] = strtoupper($user['permission_mode']);
				$this->env->authentication()->assertPermissionValue($user['permission_mode']);
				
				$this->env->configuration()->updateUser($userId, $user['name'], isset($user['email']) ? $user['email'] : NULL, $user['permission_mode']);
				$this->response()->success(TRUE);
				return;
			}
			
			// users/xx/password
			if (count($this->path) == 3) {
				$userId = $this->path[1];
				
				switch ($this->path[2]) {
					case 'password':
						$pw = $this->request->data;
						if (!isset($pw['new'])) throw $this->invalidRequestException();
						
						if ($userId === 'current') {
							if (!isset($pw['old'])) throw $this->invalidRequestException();
							$userId = $this->env->authentication()->getUserId();
							
							if ($pw['old'] != $this->env->configuration()->getPassword($userId)) throw new ServiceException("UNAUTHORIZED");
						}
						
						$this->response()->success($this->env->configuration()->changePassword($userId, $pw['new']));
						return;
				}				
			}

			// users/xx/folders/xx
			if (count($this->path) == 4) {
				switch ($this->path[2]) {
					case 'folders':
						$folderId = $this->path[3];
						$folder = $this->request->data;
						
						$this->env->configuration()->updateUserFolder($userId, $folderId, isset($folder['name']) ? $folder['name'] : NULL);
						$this->response()->success(TRUE);
						return;
				}
			}
			throw $this->invalidRequestException();
		}

		private function processDeleteUsers() {
			if (count($this->path) < 2) throw $this->invalidRequestException();

			$userId = $this->path[1];
			if (count($this->path) == 2) {
				$this->env->configuration()->removeUser($userId);
				$this->response()->success(TRUE);
				return;
			}
			if (count($this->path) == 4 and $this->path[2] === 'folders') {
				$folderId = $this->path[3];
				$this->env->configuration()->removeUserFolder($userId, $folderId);
				$this->response()->success(TRUE);
				return;
			}
			throw $this->invalidRequestException();
		}

		private function processGetUserGroups() {
			if (count($this->path) == 1) {
				$this->response()->success($this->env->configuration()->getAllUserGroups());
				return;
			}
			if (count($this->path) == 2) {
				$this->response()->success($this->env->configuration()->getUserGroup($this->path[1]));
				return;
			}
			if (count($this->path) == 3) {
				if ($this->path[2] != 'users') throw $this->invalidRequestException();
				$this->response()->success($this->env->configuration()->getGroupUsers($this->path[1]));
				return;
			}
			
			throw $this->invalidRequestException();
		}
		
		private function processPostUserGroups() {
			if (!$this->request->hasData()) throw $this->invalidRequestException();
			
			if (count($this->path) == 1) {
				$group = $this->request->data;
				if (!isset($group['name'])) throw $this->invalidRequestException();
								
				$this->env->configuration()->addUserGroup($group['name'], $group['description']);
				$this->response()->success(TRUE);
				return;
			}
			
			if (count($this->path) == 3) {
				$id = $this->path[1];		
				$users = $this->request->data;
				
				switch ($this->path[2]) {
					case 'users':
						$this->env->configuration()->addGroupUsers($id, $users);
						$this->response()->success(TRUE);
						return;
					case 'remove_users':
						$this->env->configuration()->removeGroupUsers($id, $users);
						$this->response()->success(TRUE);
					return;
				}
			}
			throw $this->invalidRequestException();
		}

		private function processPutUserGroups() {
			if (count($this->path) != 2 or !$this->request->hasData()) throw $this->invalidRequestException();

			$id = $this->path[1];
			$group = $this->request->data;
			if (!isset($group['name'])) throw $this->invalidRequestException();

			$this->env->configuration()->updateUserGroup($id, $group['name'], $group['description']);
			$this->response()->success(TRUE);			
		}
		
		private function processDeleteUserGroups() {
			if (count($this->path) != 2) throw $this->invalidRequestException();

			$id = $this->path[1];
			$this->env->configuration()->removeUserGroup($id);
			$this->response()->success(TRUE);			
		}

		private function processGetFolders() {
			if (count($this->path) == 1) {
				$this->response()->success($this->env->configuration()->getFolders());
				return;
			}
			$folderId = $this->path[1];
			
			if (count($this->path) == 3) {
				switch ($this->path[2]) {
					case 'users':
						$this->response()->success($this->env->configuration()->getFolderUsers($folderId));
						return;
				}
			}

			throw $this->invalidRequestException();
		}
		
		private function processPostFolders() {
			if (!$this->request->hasData()) throw $this->invalidRequestException();
			
			if (count($this->path) == 1) {
				$folder = $this->request->data;
				if (!isset($folder['name']) or !isset($folder['path'])) throw $this->invalidRequestException();
				$createNonExisting = (isset($folder['create']) and strcasecmp("true", $folder['create']) == 0);
				
				if (!$createNonExisting) {
					$this->env->filesystem()->assertFilesystem($folder);
				} else {
					$fs = $this->env->filesystem()->filesystem($folder, FALSE);
					if (!$fs->exists()) {
						Logging::logDebug("Added folder does not exist, creating: ".$folder['path']);
						$fs->create();
					}
				}
				
				$this->env->configuration()->addFolder($folder['name'], $folder['path']);
				$this->response()->success(TRUE);
				return;
			}
			
			if (count($this->path) == 3) {
				$id = $this->path[1];		
				$users = $this->request->data;
				
				switch ($this->path[2]) {
					case 'users':
						$this->env->configuration()->addFolderUsers($id, $users);
						$this->response()->success(TRUE);
						return;
					case 'remove_users':
						$this->env->configuration()->removeFolderUsers($id, $users);
						$this->response()->success(TRUE);
					return;
				}
			}
			
			throw $this->invalidRequestException();
		}
		
		private function processPutFolders() {
			if (count($this->path) != 2 or !$this->request->hasData()) throw $this->invalidRequestException();
			
			$id = $this->path[1];
			$folder = $this->request->data;
			if (!isset($folder['name']) or !isset($folder['path'])) throw $this->invalidRequestException();
			
			$this->env->filesystem()->assertFilesystem($folder);
			$this->env->configuration()->updateFolder($id, $folder['name'], $folder['path']);
			$this->response()->success(TRUE);	
		}
		
		private function processDeleteFolders() {
			if (count($this->path) != 2) throw $this->invalidRequestException();

			$this->env->configuration()->removeFolder($this->path[1]);
			$this->response()->success(TRUE);	
		}
		
		public function __toString() {
			return "ConfigurationServices";
		}
	}
?>