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

	class FileDescriptionDao {
		private $fileName;
		
		public function __construct($fileName) {
			$this->fileName = $fileName;
		}
		
		public function getItemDescription($item) {
			$this->assertLocalFilesystem($item);
			$file = $this->getDescriptionFilename($item);
			Logging::logDebug("Reading description for [".$item->name()."] from: ".$file);
			$descriptions = $this->readDescriptionsFromFile($file);
			
			if (!isset($descriptions[$item->name()])) return NULL;
			return $descriptions[$item->name()];
		}

		public function setItemDescription($item, $description) {
			$this->assertLocalFilesystem($item);
			
			$file = $this->getDescriptionFilename($item);
			$descriptions = $this->readDescriptionsFromFile($file);
			$descriptions[$item->name()] = $description;
			$this->writeDescriptionsToFile($file, $descriptions);
		}

		public function removeItemDescription($item) {
			$this->assertLocalFilesystem($item);
			
			$file = $this->getDescriptionFilename($item);
			$descriptions = $this->readDescriptionsFromFile($file);
			if (!isset($descriptions[$item->name()])) return;
			
			unset($descriptions[$item->name()]);
			$this->writeDescriptionsToFile($file, $descriptions);
		}
		
		public function moveItemDescription($from, $to) {
			$this->assertLocalFilesystem($from);
			$this->assertLocalFilesystem($to);
			
			$fromFile = $this->getDescriptionFilename($from);
			$fromDescriptions = $this->readDescriptionsFromFile($fromFile);
			if (!isset($fromDescriptions[$from->name()])) return;
			
			$description = $fromDescriptions[$from->name()];
			unset($fromDescriptions[$from->name()]);
			
			$sameDir = FALSE;
			if (dirname($to->filesystem()->localPath($to)) === dirname($from->filesystem()->localPath($from))) {
				$sameDir = TRUE;
				$fromDescriptions[$to->name()] = $description;
			}
			$this->writeDescriptionsToFile($fromFile, $fromDescriptions);
			
			if (!$sameDir) {
				$toFile = $this->getDescriptionFilename($to);
				$toDescriptions = $this->readDescriptionsFromFile($toFile);

				$toDescriptions[$to->name()] = $description;
				$this->writeDescriptionsToFile($toFile, $toDescriptions);
			}
		}
	
		private function getDescriptionFilename($item) {
			return dirname($item->filesystem()->localPath($item)).DIRECTORY_SEPARATOR.$this->fileName;
		}
		
		private function readDescriptionsFromFile($descriptionFile) {			
			$result = array();
			if (!file_exists($descriptionFile)) return $result;
		
			$handle = @fopen($descriptionFile, "r");
			if (!$handle)
				throw new ServiceException("REQUEST_FAILED", "Could not open description file for reading: ".$descriptionFile);
			
		    while (!feof($handle)) {
		        $line = fgets($handle, 4096);
	
				// check for quote marks (")
				if (ord(substr($line, 0, 1)) === 34) {
					$line = substr($line, 1);
					$split = strpos($line, chr(34));
				} else {
		        	$split = strpos($line, ' ');
				}
				if ($split <= 0) continue;
	
				$name = trim(substr($line, 0, $split));
				$desc = str_replace('\n', "\n", trim(substr($line, $split + 1)));
				$result[$name] = $desc;
		    }
		    fclose($handle);
			
			return $result;
		}
		
		private function writeDescriptionsToFile($file, $descriptions) {
			if (file_exists($file)) {
				if (!is_writable($file))
					throw new ServiceException("REQUEST_FAILED", "Could not open description file for writing: ".$file);
			} else {
				$dir = dirname($file);
				if (!is_writable($dir))
					throw new ServiceException("REQUEST_FAILED", "Could not write to the folder for description file: ".$file);
			}
		
			$handle = @fopen($file, "w");
			if (!$handle)
				throw new ServiceException("REQUEST_FAILED", "Could not open description file for writing: ".$file);
			
			foreach($descriptions as $name => $description)
				fwrite($handle, sprintf('"%s" %s', $name, str_replace("\n", '\n', $description))."\n");
	
			fclose($handle);
		}
		
		private function assertLocalFilesystem($item) {
			if ($item->filesystem()->type() != MollifyFilesystem::TYPE_LOCAL) throw new ServiceException("INVALID_CONFIGURATION", "Unsupported filesystem with file descriptions: ".get_class($item->filesystem()));
		}
	}
?>