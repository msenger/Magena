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

	class MySQLInstallUtil {
		private $db;
		
		public function __construct($db) {
			$this->db = $db;
		}
		
		public function db() {
			return $this->db;
		}
		
		public function checkPermissions() {
			mysqli_report(MYSQLI_REPORT_ERROR);
			$table = $this->db->table("mollify_install_test");

			// first cleanup, if test table was left
			try {
				$this->db->query('DROP TABLE '.$table, FALSE);
			} catch (ServiceException $e) {
				// ignore
			}
			
			$this->db->startTransaction();
			try {
				$tests = array("create table" => 'CREATE TABLE '.$table.' (id int NULL)',
					"insert data" => 'INSERT INTO '.$table.' (id) VALUES (1)',
					"update data" => 'UPDATE '.$table.' SET id = 2',
					"delete data" => 'DELETE FROM '.$table,
					"drop table" => 'DROP TABLE '.$table);
					
				foreach ($tests as $name => $query) {
					$phase = $name;
					$this->db->query($query, FALSE);
				}
			} catch (ServiceException $e) {
				throw new ServiceException("INVALID_CONFIGURATION", "Permission test failed, could not ".$phase." (".$e->details().")");
			}
			$this->db->commit();
		}
		
		public function execCreateTables() {
			$this->db->execSqlFile("../include/mysql/sql/create_tables.sql");
		}
		
		public function execInsertParams() {
			$this->db->execSqlFile("../include/mysql/sql/params.sql");
		}
		
		public function createAdminUser($name, $pw) {
			$this->db->query("INSERT INTO ".$this->db->table("user")." (name, password, permission_mode) VALUES ('".$this->db->string($name)."','".$pw."','".Authentication::PERMISSION_VALUE_ADMIN."')", FALSE);
		}
		
		public function updateVersionStep($from, $to) {
			$file = "../include/mysql/sql/".$from."-".$to.".sql";
			$this->db->execSqlFile($file);
		}

		public function execPluginCreateTables($id) {
			$this->db->execSqlFile("../plugin/".$id."/mysql/install.sql");
		}
		
		public function updatePluginVersionStep($id, $from, $to) {
			$file = "../plugin/".$id."/mysql/".$from."-".$to.".sql";
			$this->db->execSqlFile($file);
		}
	}
?>