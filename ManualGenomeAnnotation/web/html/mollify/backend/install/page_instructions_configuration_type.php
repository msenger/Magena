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
	global $CONFIGURATION_PROVIDER;
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">

<html>
	<?php pageHeader("Mollify Installation", "init"); ?>
	
	<body id="install-instructions-type">
		<?php pageBody("Installation", "Welcome to Mollify Installer"); ?>
		<?php if (isset($CONFIGURATION_PROVIDER)) { ?>
		<div class="error">
			<div class="title">	
				Configuration type value is invalid.
			</div>
			<div class="details">
				Configuration type "<code><?php echo($CONFIGURATION_PROVIDER); ?></code>" is invalid. For more information, see <a href="http://code.google.com/p/mollify/wiki/Installation" target="_blank">installation instructions</a>.
			</div>
		</div>
		<?php } ?>
		
		<div class="content">
			<?php if (!isset($CONFIGURATION_PROVIDER)) { ?>
			<p>
				To continue with Mollify installation, you have to choose the configuration type suitable for your installation.
			</p>
			<p>
				Options are:
				<ul>
					<li>
						<b>File based configuration</b>
						<p>In file based configuration all the configuration options are in the <code>configuration.php</code>. Options are read-only,  and all changes must be made by manually editing the file. This option does not support all features, and is best for non-changing environments.</p>
					</li>
					<li>
						<b>Database configuration (MySQL)</b>
						<p>In database configuration, all the options are stored in the database, and can be modified from the client.</p>
					</li>
				</ul>
				For more information about the alternatives, see <a href="http://code.google.com/p/mollify/wiki/Installation" target="_blank">installation instructions</a>.
			</p>
			<?php } ?>
	
			<p>
				Edit the configuration file <code>configuration.php</code> by adding the configuration provider variable, for example:
				<div class="example code">
					&lt;?php<br/>
					&nbsp;&nbsp;&nbsp;&nbsp;$CONFIGURATION_PROVIDER = &quot;<span class="value">[ENTER VALUE HERE]</span>&quot;;<br/>
					?&gt;<br/>
				</div>
			</p>
			<p>
				Possible values are:
				<ul>
					<li>"<code>file</code>" for file based configuration</li>
					<li>"<code>mysql</code>" for database configuration</li>
				</ul>
				
				When this is added, click "Continue".
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
				action("retry");
			});
		}
	</script>
</html>