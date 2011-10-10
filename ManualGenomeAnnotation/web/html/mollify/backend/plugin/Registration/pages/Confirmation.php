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
	 $EMAIL = urldecode($_GET["confirm"]);
	 $KEY = isset($_GET["key"]) ? $_GET["key"] : "";
?>
<html>
	<head>
		<title>Confirmation</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		
		<link rel="stylesheet" href="<?php echo $PATH ?>resources/style.css">
		<link rel="stylesheet" href="pages/style.css">

		<script type="text/javascript" src="<?php echo $PATH ?>resources/jquery-1.3.2.min.js"></script>
		<script type="text/javascript" src="<?php echo $PATH ?>resources/json.js"></script>
		<script type="text/javascript" src="<?php echo $PATH ?>resources/md5.js"></script>
		<script type="text/javascript" src="<?php echo $PATH ?>resources/template.js"></script>
		<script type="text/javascript" src="<?php echo $PATH ?>resources/jquery-ui-1.7.2.custom.min.js"></script>
		<script type="text/javascript" src="js/registration.js"></script>
		<script type="text/javascript" src="js/registration_confirm.js"></script>
		<script type="text/javascript">
			$(document).ready(function() {
				init('<?php echo $PATH ?>', '<?php echo $EMAIL ?>', '<?php echo $KEY ?>');
			});
		</script>
	</head>

	<body>
		<div id="confirmation-form" style="display:none">
			<div class="title">
				Please enter the confirmation key for <span class="email"><?php echo $EMAIL ?></span> and click "Confirm".
			</div>
			<div class="registration-form-field">
				<input type="text" id="key-field" class="registration-field"></input>
				<div id="key-hint" class="registration-field-hint"></div>
			</div>
			<div class="buttons">
				<a id="confirm-button" href="#" class="btn">Confirm</a>
			</div>
		</div>
	</body>
</html>