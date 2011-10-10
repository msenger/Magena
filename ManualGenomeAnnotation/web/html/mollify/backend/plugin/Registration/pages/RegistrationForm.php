<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
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
	 if (!isset($PATH)) die();
?>
<html>
	<head>
		<title>User registration</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		
		<link rel="stylesheet" href="<?php echo $PATH ?>resources/style.css">
		<link rel="stylesheet" href="pages/style.css">

		<script type="text/javascript" src="<?php echo $PATH ?>resources/jquery-1.3.2.min.js"></script>
		<script type="text/javascript" src="<?php echo $PATH ?>resources/json.js"></script>
		<script type="text/javascript" src="<?php echo $PATH ?>resources/md5.js"></script>
		<script type="text/javascript" src="<?php echo $PATH ?>resources/template.js"></script>
		<script type="text/javascript" src="js/registration.js"></script>
		<script type="text/javascript" src="js/registration_form.js"></script>
		<script type="text/javascript">
			$(document).ready(function() {
				init('<?php echo $PATH ?>');
			});
		</script>
	</head>

	<body>
		<div id="registration-form" style="display:none">
			<div class="title">
				Please enter the information below, and click "Register".
			</div>
			<div class="registration-form-field">
				<div class="registration-field-title">Name:</div>
				<input type="text" id="username-field" class="registration-field"></input>
				<div id="username-hint" class="registration-field-hint"></div>
			</div>
			<div class="registration-form-field">
				<div class="registration-field-title">Password:</div>
				<input type="password" id="password-field" class="registration-field"></input>
				<div id="password-hint" class="registration-field-hint"></div>
			</div>
			<div class="registration-form-field">
				<div class="registration-field-title">Confirm password:</div>
				<input type="password" id="confirm-password-field" class="registration-field"></input>
				<div id="confirm-password-hint" class="registration-field-hint"></div>
			</div>
			<div class="registration-form-field">
				<div class="registration-field-title">E-mail:</div>
				<input type="text" id="email-field" class="registration-field"></input>
				<div id="email-hint" class="registration-field-hint"></div>
			</div>
			<div class="buttons">
				<a id="register-button" href="#" class="btn">Register</a>
			</div>
		</div>
	</body>
</html>