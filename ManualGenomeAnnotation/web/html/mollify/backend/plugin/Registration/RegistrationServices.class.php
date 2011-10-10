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

	class RegistrationServices extends ServicesBase {
		protected function isValidPath($method, $path) {
			return count($path) >= 1;
		}
		
		public function isAuthenticationRequired() {
			return FALSE;
		}
		
		public function processGet() {
			if (count($this->path) != 1 or $this->path[0] != 'list') throw $this->invalidRequestException();
			$this->env->authentication()->assertAdmin();
			
			$db = $this->env->configuration()->db();
			$result = $db->query("select `id`, `name`, `email`, `key`, `time` from ".$db->table("pending_registrations")." order by id asc")->rows();
			$this->response()->success($result);
		}

		public function processDelete() {
			if (count($this->path) != 2 or $this->path[0] != 'list') throw $this->invalidRequestException();
			$this->env->authentication()->assertAdmin();
			
			$id = $this->path[1];
			$db = $this->env->configuration()->db();
			$result = $db->update("delete from ".$db->table("pending_registrations")." where id=".$db->string($id, TRUE));
			$this->response()->success(array());
		}
		
		public function processPost() {
			if (count($this->path) == 1 and $this->path[0] === 'create') {
				$this->processRegister();
			} else if (count($this->path) >= 1 and $this->path[0] === 'confirm') {
				if (count($this->path) == 1) $this->processConfirm();
				else $this->processConfirmById($this->path[1]);
			}
			else throw $this->invalidRequestException();
		}
		
		private function processRegister() {
			$registration = $this->request->data;
			if (!isset($registration['name']) or !isset($registration['password']) or !isset($registration['email'])) throw $this->invalidRequestException();
			
			$this->assertUniqueNameAndEmail($registration['name'], $registration['email']);
			
			$plugin = $this->env->plugins()->getPlugin("Registration");
			if ($plugin->getSetting("user_assert_global_uniqueness", FALSE))
				$this->assertUniqueNameAndEmailGlobal($registration['name'], $registration['email']);

			$db = $this->env->configuration()->db();
			$name = $registration['name'];
			$password = $registration['password'];
			$email = $registration['email'];
			$time = date('YmdHis', time());
			$key = str_replace(".", "", uniqid("", TRUE));
			
			$db->update(sprintf("INSERT INTO ".$db->table("pending_registrations")." (`name`, `password`, `email`, `key`, `time`) VALUES (%s, %s, %s, %s, %s)", $db->string($name, TRUE), $db->string($password, TRUE), $db->string($email, TRUE), $db->string($key, TRUE), $time));
			
			$this->notify($name, $email, $key);
			$this->env->events()->onEvent(RegistrationEvent::registered($name, $email));
			$this->response()->success(array());
		}

		private function assertUniqueNameAndEmail($name, $email) {
			$db = $this->env->configuration()->db();
			$query = "select count(id) from ".$db->table("pending_registrations")." where name=".$db->string($name,TRUE)." or email=".$db->string($email,TRUE);
			$count = $db->query($query)->value(0);
			if ($count > 0) throw new ServiceException("REQUEST_FAILED", "User already registered with same name or email"); 
		}

		private function assertUniqueNameAndEmailGlobal($name, $email) {
			$db = $this->env->configuration()->db();
			$query = "select count(id) from ".$db->table("user")." where name=".$db->string($name,TRUE)." or email=".$db->string($email,TRUE);
			$count = $db->query($query)->value(0);
			if ($count > 0) throw new ServiceException("REQUEST_FAILED", "User already exists with same name or email");
		}

		private function processConfirm() {
			$confirmation = $this->request->data;
			if (!isset($confirmation['email']) or !isset($confirmation['key'])) throw $this->invalidRequestException();
			
			$db = $this->env->configuration()->db();
			$query = "select `id`, `name`, `password`, `email` from ".$db->table("pending_registrations")." where `email`=".$db->string($confirmation['email'],TRUE)." and `key`=".$db->string($confirmation['key'],TRUE);
			$result = $db->query($query);
			
			if ($result->count() != 1) throw new ServiceException("REQUEST_FAILED", "Email and confirmation key don't match");
			$this->confirm($result->firstRow());
		}
		
		private function processConfirmById($id) {
			$this->env->authentication()->assertAdmin();
			
			$db = $this->env->configuration()->db();
			$query = "select `id`, `name`, `password`, `email` from ".$db->table("pending_registrations")." where `id`=".$db->string($id,TRUE);
			$result = $db->query($query);
			
			if ($result->count() != 1) throw new ServiceException("Registration not found");
			$this->confirm($result->firstRow());
		}
		
		private function confirm($registration) {
			$db = $this->env->configuration()->db();
			$plugin = $this->env->plugins()->getPlugin("Registration");
			$permission = $plugin->getSetting("permission", Authentication::PERMISSION_VALUE_READONLY);
			
			$id = $this->env->configuration()->addUser($registration['name'], $registration['password'], $registration['email'], $permission);
			$db->update("DELETE from ".$db->table("pending_registrations")." where `id`=".$db->string($registration['id'],TRUE));
			
			$this->addUserProperties($id, $registration['name'], $plugin);
			
			$this->env->events()->onEvent(RegistrationEvent::confirmed($registration['name']));
			$this->response()->success(array());
		}
		
		private function addUserProperties($id, $name, $plugin) {
			$groups = $plugin->getSetting("groups", array());
			if (count($groups) > 0) {
				$existing = array();
				foreach ($this->env->configuration()->getAllUserGroups() as $group) {
					if (in_array($group['id'], $groups)) $existing[] = $group['id'];
				}
				
				if (count($existing) > 0)
					$this->env->configuration()->addUsersGroups($id, $existing);
			}
			
			$folders = $plugin->getSetting("folders", array());
			if (count($folders) > 0) {
				$existing = array();
				foreach ($this->env->configuration()->getFolders() as $folder)
					if (in_array($folder['id'], $folders)) $existing[] = $folder['id'];

				if (count($existing) > 0)
					$this->env->configuration()->addUserFolders($id, $existing);
			}
			
			$userFolder = $plugin->getSetting("user_folder", NULL);
			if ($userFolder == NULL) return;
			
			// automatic user folder
			if (!isset($userFolder["path"])) {
				Logging::logError("Registration: missing configuration for user folder");
				return;
			}
			$basePath = $userFolder["path"];
			$folderName = $name;
			if (isset($userFolder["folder_name"])) $folderName = $userFolder["folder_name"];
			$folderPath = $basePath.DIRECTORY_SEPARATOR.$name;
			
			$fs = $this->env->filesystem()->filesystem(array("path" => $folderPath, "name" => $folderName), FALSE);
			if ($fs->exists()) {
				Logging::logError("Registration: user folder [".$folderPath."] already exists, not added");
				return;
			}
			if (!$fs->create()) {
				Logging::logError("Registration: user folder [".$folderPath."] could not be created, not added");
				return;
			}
			
			$folderId = $this->env->configuration()->addFolder($name, $folderPath);
			$this->env->configuration()->addUserFolder($id, $folderId, $folderName);
			
			$fs = $this->env->filesystem()->filesystem(array("id" => $folderId, "path" => $folderPath, "name" => $folderName), FALSE);
			$this->env->configuration()->addItemPermission($fs->root()->id(), Authentication::PERMISSION_VALUE_READWRITE, $id);
			
			if (isset($userFolder["add_to_users"]) and count($userFolder["add_to_users"]) > 0) {
				$users = $userFolder["add_to_users"];
				$existing = array();
				foreach ($this->env->configuration()->getAllUsers() as $user) {
					if (in_array($user['id'], $users)) $existing[] = $user['id'];
				}
				
				if (count($existing) > 0)
					$this->env->configuration()->addFolderUsers($folderId, $existing);
			}
		}
		
		private function notify($name, $email, $key) {
			require_once("Messages.php");
			$link = $this->env->getPluginUrl("Registration")."?confirm=".urlencode($email)."&key=".$key;
			$values = array("name" => $name, "email" => $email, "link" => $link);
			
			$subject = $this->replaceParams($REGISTRATION_NOTIFICATION_SUBJECT, $values);
			$msg = $this->replaceParams($REGISTRATION_NOTIFICATION_MESSAGE, $values);
			
			$this->env->notification()->send($email, $subject, $msg);
		}
		
		private function replaceParams($text, $values) {
			foreach($values as $k => $v)
				$text = str_replace('%'.$k.'%', $v, $text);
			return $text;
		}
				
		public function __toString() {
			return "RegistrationServices";
		}
	}
?>