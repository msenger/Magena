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
	
	/* For configuration instructions, see ReadMe.txt or wiki page at http://code.google.com/p/mollify/wiki/Installation */
	
	/* File permission mode: "A" = Admin, "RW" = Read/write, "RO" = Read-only (default if omitted) */
	
	$PUBLISHED_FOLDERS = array(
		"1" => array("name" => "Folder A", "path" => "/foo/bar"),
		"2" => array("name" => "Folder B", "path" => "/foo/bay"),
		"3" => array("name" => "Folder C", "path" => "/foo/bat")
	);
	
	$USERS = array(
		"1" => array("name" => "User 1", "password" => "foo", "default_permission" => "rw", "folders" => array(
			"1" => NULL,
			"2" => NULL,
			"3" => NULL
		)),
		"2" => array("name" => "User 2", "password" => "bar", "default_permission" => "ro", "folders" => array(
			"1" => "Custom name for Folder A",
			"2" => NULL
		))
	);
	
?>