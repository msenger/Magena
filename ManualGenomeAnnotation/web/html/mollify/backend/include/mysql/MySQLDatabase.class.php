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

	class MySQLDatabase {
		private $host;
		private $user;
		private $pw;
		private $database;
		private $tablePrefix;
		
		private $db = NULL;
		
		public function __construct($host, $user, $pw, $database, $tablePrefix) {
			Logging::logDebug("MySQL DB: ".$user."@".$host.":".$database."(".$tablePrefix.")");
			$this->host = $host;
			$this->user = $user;
			$this->pw = $pw;
			$this->database = $database;
			$this->tablePrefix = $tablePrefix;
		}
		
		public function host() {
			return $this->host;
		}
		
		public function user() {
			return $this->user;
		}

		public function password() {
			return $this->password;
		}

		public function database() {
			return $this->database;
		}

		public function tablePrefix() {
			return $this->tablePrefix;
		}
		
		public function isConnected() {
			return $this->db != NULL;
		}
		
		public function connect($selectDb = TRUE) {
			$db = @mysql_connect($this->host, $this->user, $this->pw);
			if (!$db) throw new ServiceException("INVALID_CONFIGURATION", "Could not connect to database (host=".$this->host.", user=".$this->user.", password=".$this->pw."), error: ".mysql_error());

			$this->db = $db;			
			if ($selectDb) $this->selectDb();
		}
		
		public function databaseExists() {
			return mysql_select_db($this->database, $this->db);
		}

		public function selectDb() {
			if (!mysql_select_db($this->database, $this->db)) throw new ServiceException("INVALID_CONFIGURATION", "Could not select database (".$this->database.") error: ".mysql_error($this->db));
		}

		public function table($name) {
			return $this->tablePrefix.$name;
		}
		
		public function update($query) {
			$result = $this->query($query);
			$affected = $result->affected();
			$result->free();
			return $affected;
		}

		public function query($query) {
			if (Logging::isDebug()) Logging::logDebug("DB: ".$query);
			
			$result = @mysql_query($query, $this->db);
			if (!$result)
				throw new ServiceException("INVALID_CONFIGURATION", "Error executing query (".$query."): ".mysql_error($this->db));
			return new Result($this->db, $result);
		}
		
		public function startTransaction() {
			$result = @mysql_query("START TRANSACTION;", $this->db);
			if (!$result)
				throw new ServiceException("INVALID_CONFIGURATION", "Error starting transaction: ".mysql_error($this->db));
		}

		public function commit() {
			$result = @mysql_query("COMMIT;", $this->db);
			if (!$result)
				throw new ServiceException("INVALID_CONFIGURATION", "Error committing transaction: ".mysql_error($this->db));
		}
		
		public function string($s, $quote = FALSE) {
			if ($s == NULL) return 'NULL';
			$r = mysql_real_escape_string($s, $this->db);
			if ($quote) return "'".$r."'";
			return $r;
		}
		
		public function arrayString($a, $quote = FALSE) {
			$result = '';
			$first = TRUE;
			foreach($a as $s) {
				if (!$first) $result .= ',';
				if ($quote) $result .= "'".$s-"'";
				else $result .= $s;
				$first = FALSE;
			}
			return $result;
		}
		
		public function lastId() {
			return mysql_insert_id($this->db);
		}
	}
	
	class Result {
		private $db;
		private $result;
		
		public function __construct($db, $result) {
			$this->db = $db;
			$this->result = $result;
		}
		
		public function count() {
			return mysql_num_rows($this->result);
		}

		public function affected() {
			return mysql_affected_rows($this->db);
		}
				
		public function rows() {
			$list = array();
			while ($row = mysql_fetch_assoc($this->result)) {
				$list[] = $row;
			}
			mysql_free_result($this->result);
			return $list;
		}
		
		public function firstRow() {
			$ret = mysql_fetch_assoc($this->result);
			mysql_free_result($this->result);
			return $ret;
		}
		
		public function value($i) {
			$ret = mysql_result($this->result, $i);
			mysql_free_result($this->result);
			return $ret;
		}
		
		public function free() {
			if ($this->result === TRUE or $this->result === FALSE) return;
			mysql_free_result($this->result);
		}
	}
?>