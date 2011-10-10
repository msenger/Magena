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
	 
	include("installation_page.php");
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">

<html>
	<?php pageHeader("Mollify Installation", "init"); ?>

	<body id="install-instructions-create">
		<?php pageBody("Installation", "Welcome to Mollify Installer"); ?>

		<?php if ($installer->action() == 'retry') { ?>
			<div class="error">
				<div class="title">
				Configuration file cannot be found.
				</div>
				
				<div class="details">
					Make sure that the file "<code>configuration.php</code>"
					<ul>
						<li>is located in the Mollify backend folder</li>
						<li>is accessible to PHP</li>
					</ul>
				</div>
			</div>
		<?php }?>
		
		<div class="content">
			<p>
				To begin with the installation process, first create empty configuration file called "<code>configuration.php</code>" in the Mollify backend directory.
			</p>	
			<p>
				<a id="button-retry" href="#" class="btn">Continue</a>
			</p>
		</div>
		
		<?php pageFooter(); ?>
	</body>
	
	<script type="text/javascript">
		function init() {
			$("#button-retry").click(function() {
				action("retry");
			});
		}
	</script>
</html>