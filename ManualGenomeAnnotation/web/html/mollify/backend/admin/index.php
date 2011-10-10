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

	if (!file_exists("../configuration.php")) die();
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<title>Mollify Administration</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		
		<link rel="stylesheet" href="../resources/jquery-ui-1.7.2.custom.css">
		<link rel="stylesheet" href="../resources/ui.jqgrid.css">
		<link rel="stylesheet" href="resources/style.css">
		
		<script type="text/javascript" src="../resources/jquery-1.3.2.min.js"></script>
		<script type="text/javascript" src="../resources/md5.js"></script>
		<script type="text/javascript" src="../resources/json.js"></script>
		<script type="text/javascript" src="../resources/template.js"></script>
		<script type="text/javascript" src="../resources/jquery-ui-1.7.2.custom.min.js"></script>
		<script type="text/javascript" src="../resources/jquery.jqGrid.min.js"></script>
		<script type="text/javascript" src="resources/service.js"></script>
		<?php if (file_exists("settings.js")) { ?><script type="text/javascript" src="settings.js"></script><?php } ?>
		<script type="text/javascript" src="resources/main.js"></script>
		<script type="text/javascript">
			var scriptLocation = '<?php echo dirname(dirname($_SERVER['SCRIPT_FILENAME']));?>';
			
			function getScriptLocation() {
				return scriptLocation;
			}
		</script>
	</head>	
	
	<body id="page-admin">
		<header>
			<h1>Administration</h1>
		</header>

		<div id="content" class="content" style="display:none">
			<div id="main-menu">
			</div>
			<div id="page-area">
				<div id="page-header">
					<div id="page-title">Administration</div>
					<div id="request-indicator"></div>
				</div>
				<div id="page">
					<p>Select an item to configure from the menu on the left</p>
				</div>
			</div>
		</div>
	</body>
</html>