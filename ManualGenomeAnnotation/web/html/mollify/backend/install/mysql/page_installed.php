<?php

	/**
	 * Copyright (c) 2008- Samuli J�rvel�
	 *
	 * All rights reserved. This program and the accompanying materials
	 * are made available under the terms of the Eclipse Public License v1.0
	 * which accompanies this distribution, and is available at
	 * http://www.eclipse.org/legal/epl-v10.html. If redistributing this code,
	 * this entire header must remain intact.
	 */
	 
	 include("install/installation_page.php");
	 
	 function version($ver) {
	 	return str_replace("_", ".", $ver);
	 }
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">

<html>
	<?php pageHeader("Mollify Installation"); ?>
	
	<body id="page-mysql-installed">
		<?php pageBody("Installation", "Mollify Already Installed"); ?>

		<div class="content">
		<?php if ($installer->isCurrentVersionInstalled()) { ?>
			<p>
				Mollify is already installed with the current version.
			</p>	
		<?php } else { ?>
			<p>
				Mollify is already installed, but needs updating to version <b><?php echo version($installer->currentVersion()) ?></b>.
			</p>
			<p>
				Open <a href="../update/">Mollify updater</a> to update.
			</p>
		<?php } ?>
		</div>
		<?php pageFooter(); ?>
	</body>
</html>