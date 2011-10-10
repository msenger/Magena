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
	 
	 include("install/installation_page.php");
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">

<html>
	<?php pageHeader("Mollify Update"); ?>
	
	<body id="page-file-installed">
		<?php pageBody("Update", "File Based Configuration"); ?>
	
		<div class="content">
			<p>
				Mollify updater does not support updating file based configuration. See <a href="http://code.google.com/p/mollify/wiki/ChangeLog" target="_blank">change log</a> for changes needed in configuration file.
			</p>
		</div>
		<?php pageFooter(); ?>
	</body>
</html>