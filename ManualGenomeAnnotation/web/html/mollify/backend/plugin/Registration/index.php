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
	 
	$PATH = "../../";
	
	if (!file_exists($PATH."configuration.php")) die("Mollify not configured");
	$confirmMode = isset($_GET["confirm"]);
	$confirmEmail = $confirmMode ? $_GET["confirm"] : NULL;
	
	if ($confirmMode) {
		if ($confirmEmail == NULL or strlen($confirmEmail) == 0) {
			include("pages/InvalidConfirmation.php");
		} else {
			include("pages/Confirmation.php");
		}
		die();
	}
	include("pages/RegistrationForm.php");
?>