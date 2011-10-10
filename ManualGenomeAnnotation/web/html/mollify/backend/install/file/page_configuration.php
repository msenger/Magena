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
	<?php pageHeader("Mollify Installation", "init"); ?>
	
	<body id="page-file-configuration">
		<?php pageBody("Installation", "File Based Configuration"); ?>
		<?php if ($installer->action() == 'continue') { ?>
			<div class="error">
				<div class="title">
					No configuration
				</div>
				<div class="details">
					No proper configuration was found in "<code>configuration.php</code>". Make sure configuration is done according to the instructions below.
				</div>
			</div>
		<?php }?>
		
		<div class="content">
			<p>
				File based configuration is always read-only, and therefore configuration must be done by manually editing the configuration file "<code>configuration.php</code>".
			</p>
			<h2>Operation Modes</h2>
			<p>
				File based configuration supports single user and multi user configurations:
				<ul>
					<li>In single user mode, no authentication is required and all access rules apply to everybody.</li>
					<li>In multi user mode, different user accounts can be set up with different published folders and access permissions.</li>
				</ul>
			</p>
			<h2>Configuration</h2>
			<p>
				<ol>
					<li>Choose the preferred operation mode (as described above)</li>
					<li>Modify "<code>configuration.php</code>" by following instructions based on the selected mode
					<ul>
						<li><a href="http://code.google.com/p/mollify/wiki/ConfigurationSingleUserMode" target="_blank">Single user</a></li>
						<li><a href="http://code.google.com/p/mollify/wiki/ConfigurationMultiUserMode" target="_blank">Multi user</a></li>
					</ul>
					</li>
					<li>Click "Continue"</li>
				</ol>
			</p>
			<p>
				<a id="button-continue" href="#" class="btn">Continue</a>
			</p>
		</div>
		<?php pageFooter(); ?>
	</body>
	
	<script type="text/javascript">
		function init() {
			$("#button-continue").click(function() {
				action("continue");
			});
		}
	</script>
</html>