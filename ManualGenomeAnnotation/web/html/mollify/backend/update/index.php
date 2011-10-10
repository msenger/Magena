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

	$MAIN_PAGE = "update";
	$updater = NULL;
	
	set_include_path("..");
	if (!file_exists("../configuration.php")) die();
	require("configuration.php");
	global $SETTINGS, $CONFIGURATION_PROVIDER;

	$installer = createUpdater($CONFIGURATION_PROVIDER, $SETTINGS);
	try {
		$installer->process();
	} catch (Exception $e) {
		$installer->onError($e);
	}

	function isValidConfigurationType($type) {
		$TYPES = array("file","mysql");
		return in_array(strtolower($type), $TYPES);
	}
		
	function createUpdater($type, $settings) {
		if (!isset($type) or !isValidConfigurationType($type)) die();
		
		switch (strtolower($type)) {
			case 'file':
				require_once("update/file/FileUpdater.class.php");
				return new FileUpdater($type, $settings);
			case 'mysql':
				require_once("update/mysql/MySQLUpdater.class.php");
				return new MySQLUpdater($type, $settings);
			default:
				die("Unsupported updater type: ".$type);

		}
	}
?>