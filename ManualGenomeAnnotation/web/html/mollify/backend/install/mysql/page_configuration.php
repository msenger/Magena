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
	
	<body id="page-mysql-configuration">
		<?php pageBody("Installation", "Database Configuration"); ?>
		<?php if ($installer->action() === 'continue') { ?>
		<div class="error">
			<div class="title">	
				No database configuration found.
			</div>
			<div class="details">
				Database configuration is missing or it is not complete. Make sure that the configuration is done according to the instructions below. At minimum, database user and password must be defined.
			</div>
		</div>
		<?php } ?>
		
		<div class="content">
			<p>
				Installer needs the database connection information defined in the configuration file "<code>configuration.php</code>":
				<ul>
					<li>Host name (optional, by default "localhost")</li>
					<li>Database name (optional, by default "mollify")</li>
					<li>User</li>
					<li>Password</li>
					<li>Table prefix (optional)</li>
				</ul>
				
				For more information, see <a href="http://code.google.com/p/mollify/wiki/Installation">Installation instructions</a>.
			</p>
			<p>	
				An example configuration:
				<div class="example code">
					&lt;?php<br/>
					&nbsp;&nbsp;&nbsp;&nbsp;$CONFIGURATION_PROVIDER = &quot;<span class="value">mysql</span>&quot;;<br/>
					&nbsp;&nbsp;&nbsp;&nbsp;$DB_HOST = &quot;<span class="value">localhost</span>&quot;;<br/>
					&nbsp;&nbsp;&nbsp;&nbsp;$DB_DATABASE = &quot;<span class="value">mollify</span>&quot;;<br/>
					&nbsp;&nbsp;&nbsp;&nbsp;$DB_USER = &quot;<span class="value">[MYSQL_USERNAME]</span>&quot;;<br/>
					&nbsp;&nbsp;&nbsp;&nbsp;$DB_PASSWORD = &quot;<span class="value">[MYSQL_PASSWORD]</span>&quot;;<br/>
					&nbsp;&nbsp;&nbsp;&nbsp;$DB_TABLE_PREFIX = &quot;<span class="value">mollify_</span>&quot;;<br/>
					?&gt;
				</div>
			</p>
			<p>
				Edit the configuration and click "Continue".
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