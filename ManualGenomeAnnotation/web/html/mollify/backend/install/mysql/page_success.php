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
	<?php pageHeader("Mollify Installation"); ?>
	<body id="page-mysql-success">
		<?php pageBody("Installation", "Installation Complete"); ?>
		<div class="content">
			<h2>Mollify is successfully installed</h2>
			<p>
				You can now log into Mollify using the admin account created, and finish configuration with the <a href="../admin/">administration utility</a>.
			</p>
			<p>
				For additional configuration options or instructions, see <a href="http://code.google.com/p/mollify/wiki/Installation" target="_blank">Installation instructions</a>.
			</p>
		</div>
		<?php pageFooter(); ?>
	</body>
</html>