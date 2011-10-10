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

	abstract class MollifyFilesystem {
		const TYPE_LOCAL = "local";
		
		protected $id;
		protected $name;
		protected $filesystemInfo;
		
		function __construct($id, $name, $filesystemInfo) {
			$this->id = $id;
			$this->name = $name;
			$this->filesystemInfo = $filesystemInfo;
		}
		
		abstract function type();
		
		abstract function exists();
		
		abstract function create();

		public abstract function createItem($id, $path, $nonexisting = FALSE);
				
		public abstract function items($parent);
		
		public abstract function parent($item);
		
		public abstract function size($file);
		
		public abstract function lastModified($item);
		
		public abstract function rename($item, $name);
		
		public abstract function copy($item, $to);
		
		public abstract function move($item, $to);
		
		public abstract function delete($item);
		
		public abstract function read($item, $range = NULL);
		
		public abstract function write($item);
		
		public abstract function put($item, $content);
		
		public abstract function addToZip($item, $zip);
				
		public abstract function createFolder($folder, $name);
		
		public abstract function createFile($folder, $name);
		
		public abstract function itemWithName($folder, $name);

		public function id() {
			return $this->id;
		}

		protected function rootId() {
			return $this->itemId('');
		}

		public function itemId($path) {
			return $this->id().":".DIRECTORY_SEPARATOR.$path;
		}
		
		public function name() {
			return $this->name;
		}
				
		public function root() {
			return new Folder($this->itemId(''), $this->rootId(), '', $this->name, $this);
		}
		
		public function details($item) {
			return array();
		}
				
		protected function ignoredItems($path) {
			return $this->filesystemInfo->ignoredItems($this, $path);
		}
		
		protected function itemWithPath($path, $nonExisting = FALSE) {
			return $this->createItem($this->itemId($path), $path, $nonExisting);
		}

		public function __toString() {
			return get_class($this)." (".$this->id.") ".$this->name;
		}
	}
	
	class NonExistingFolderException extends ServiceException {}
?>