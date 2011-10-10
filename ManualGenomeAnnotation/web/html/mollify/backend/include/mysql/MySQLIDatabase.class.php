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

	class MySQLIDatabase {
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
			mysqli_report(MYSQLI_REPORT_ALL);
			try {
				if ($selectDb) $db = @mysqli_connect($this->host, $this->user, $this->pw, $this->database);
				else $db = @mysqli_connect($this->host, $this->user, $this->pw);
			} catch (mysqli_sql_exception $e) {
				throw new ServiceException("INVALID_CONFIGURATION", "Could not connect to database (host=".$this->host.", user=".$this->user.", password=".$this->pw."), error: ".mysqli_connect_error());
			}
			if (!$db) throw new ServiceException("INVALID_CONFIGURATION", "Could not connect to database (host=".$this->host.", user=".$this->user.", password=".$this->pw."), error: ".mysqli_connect_error());

			$this->db = $db;
		}
		
		public function databaseExists() {
			try {
				return mysqli_select_db($this->db, $this->database);
			} catch (mysqli_sql_exception $e) {
				return FALSE;
			}
		}

		public function selectDb() {
			if (!mysqli_select_db($this->db, $this->database)) throw new ServiceException("INVALID_CONFIGURATION", "Could not select database (".$this->database.") error: ".mysql_error($this->db));
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

		public function query($query, $expectResult = TRUE) {
			if (Logging::isDebug()) Logging::logDebug("DB QUERY: ".$query);
			
			try {
				$result = @mysqli_query($this->db, $query);
			} catch (mysqli_sql_exception $e) {
				if (Logging::isDebug()) Logging::logDebug("ERROR: ".$e);
				throw new ServiceException("INVALID_CONFIGURATION", "Error executing query (".$query."): ".mysqli_error($this->db));
			}
			if (!$result)
				throw new ServiceException("INVALID_CONFIGURATION", "Error executing query (".$query."): ".mysqli_error($this->db));

			if (!$expectResult) return TRUE;
			return new MySQLIResult($this->db, $result);
		}
		
		public function queries($sql) {			
			try {	
				@mysqli_multi_query($this->db, $sql);
			    do {
			        if ($result = @mysqli_store_result($this->db))
			        	mysqli_free_result($result);
			        
			        if (mysqli_error($this->db))
			        	throw new ServiceException("INVALID_CONFIGURATION", "Error executing queries (".(strlen($sql) > 40 ? substr($sql, 0, 40)."..." : $sql)."): ".mysqli_error($this->db));
			    } while (mysqli_next_result($this->db));
			} catch (mysqli_sql_exception $e) {
				if (Logging::isDebug()) Logging::logDebug("ERROR: ".$e);
				throw new ServiceException("INVALID_CONFIGURATION", "Error executing queries (".(strlen($sql) > 40 ? substr($sql, 0, 40)."..." : $sql)."...): ".mysqli_error($this->db));
			}
		}
		
		public function execSqlFile($file) {
			$sql = file_get_contents($file);
			if (!$sql) throw new ServiceException("INVALID_REQUEST", "Error reading sql file (".$file.")");

			$sql = str_replace('{TABLE_PREFIX}', (isset($this->tablePrefix) and $this->tablePrefix != '') ? $this->tablePrefix : '', $sql);
			$this->queries($sql);
		}
		
		public function startTransaction() {
			try {
				$result = @mysqli_query($this->db, "START TRANSACTION;");
			} catch (mysqli_sql_exception $e) {
				if (Logging::isDebug()) Logging::logDebug("ERROR: ".$e);
				throw new ServiceException("INVALID_CONFIGURATION", "Error starting transaction: ".mysqli_error($this->db));
			}

			if (!$result)
				throw new ServiceException("INVALID_CONFIGURATION", "Error starting transaction: ".mysqli_error($this->db));
		}

		public function commit() {
			try {
				$result = @mysqli_query($this->db, "COMMIT;");
			} catch (mysqli_sql_exception $e) {
				if (Logging::isDebug()) Logging::logDebug("ERROR: ".$e);
				throw new ServiceException("INVALID_CONFIGURATION", "Error committing transaction: ".mysqli_error($this->db));
			}

			if (!$result)
				throw new ServiceException("INVALID_CONFIGURATION", "Error committing transaction: ".mysqli_error($this->db));
		}

		public function rollback() {
			try {
				$result = @mysqli_query($this->db, "ROLLBACK;");
			} catch (mysqli_sql_exception $e) {
				if (Logging::isDebug()) Logging::logDebug("ERROR: ".$e);
				throw new ServiceException("INVALID_CONFIGURATION", "Error rollbacking transaction: ".mysqli_error($this->db));
			}

			if (!$result)
				throw new ServiceException("INVALID_CONFIGURATION", "Error rollbacking transaction: ".mysqli_error($this->db));
		}
		
		public function string($s) {
			return mysqli_real_escape_string($this->db, $s);
		}
	}
	
	class MySQLIResult {
		private $db;
		private $result;
		
		public function __construct($db, $result) {
			$this->db = $db;
			$this->result = $result;
		}
		
		public function count() {
			return mysqli_num_rows($this->result);
		}

		public function affected() {
			return mysqli_affected_rows($this->db);
		}
				
		public function rows() {
			$list = array();
			while ($row = mysqli_fetch_assoc($this->result)) {
				$list[] = $row;
			}
			mysqli_free_result($this->result);
			return $list;
		}
		
		public function firstRow() {
			$ret = mysqli_fetch_assoc($this->result);
			mysqli_free_result($this->result);
			return $ret;
		}
		
		public function firstValue($val) {
			$ret = $this->firstRow();
			return $ret[$val];
		}
		
		public function value($i) {
			$ret = $this->rows();
			return $ret[$i];
		}
		
		public function free() {
			if ($this->result === TRUE or $this->result === FALSE) return;
			mysqli_free_result($this->result);
		}
	}
?>