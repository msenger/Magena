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
	 	 
	 function getPermissionMode($mode) {
	 	if ($mode == NULL) return 'No permission level set, defaults to "Read-Only"';
	 	
	 	switch (strtolower(trim($mode))) {
	 		case "ro": return "Read-Only";
	 		case "rw": return "Read and Write";
	 		case "a": return "Admin";
	 		default: return "Unknown";
	 	}
	 }
	 
	 function instructionsSingleUser() { ?>
		Mollify has been configured with following published folders. To modify this configuration, edit the "<code>configuration.php</code>". For more information about the configuration, see <a href="http://code.google.com/p/mollify/wiki/ConfigurationSingleUserMode" target="_blank">instructions</a>.<?php
	 }
	 
	 function instructionsMultiUser() { ?>
		Mollify has been configured with following users and published folders. To modify this configuration, edit the "<code>configuration.php</code>". For more information about the configuration, see <a href="http://code.google.com/p/mollify/wiki/ConfigurationMultiUserMode" target="_blank">instructions</a>.<?php
	 }
	 
	 function instructionsInstalledMultiUser() { ?>
		Mollify has been configured with following users and published folders.<?php
	 }
	 
	 function dirItem($dir, $name = NULL) {
		 echo "<li>".($name != NULL ? $name : $dir['name'])." (<code>".$dir['path']."</code>)</li>";
	 }

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">

<html>
	<?php pageHeader("Mollify Installation"); ?>
	
	<body id="page-file-installed">
		<?php pageBody("Installation", "Installation Summary"); ?>
	
		<div class="content">
			<p>
			<?php
				if ($installer->action() != 'continue') {
					if ($this->authentication()->isAuthenticationRequired()) instructionsInstalledMultiUser();
					else instructionsSingleUser();
				} else {
					if ($this->authentication()->isAuthenticationRequired()) instructionsMultiUser();
					else instructionsSingleUser();
				}
			?>
			</p>
			<p>
				<?php if ($this->authentication()->isAuthenticationRequired()) {?>
					<h2>Configured users</h2>
					<ol>
					<?php foreach ($installer->users() as $id => $user) {
						echo "<li>".$user['name']." (".getPermissionMode(isset($user['default_permission']) ? $user['default_permission'] : NULL).")</li>";
					}?>
					</ol>
				<?php } ?>
				
				<h2>Published folders</h2>
				<ol>
				<?php
					if ($this->authentication()->isAuthenticationRequired()) {
						$folders = $installer->publishedDirectories();
						
						foreach ($installer->users() as $id => $user) {
							echo "<li>".$user['name']."<ul>";
							foreach($user["folders"] as $folder_id => $name) {
								$f = $folders[$folder_id];
								dirItem($f, $name);
							}
							echo "</ul></li>";
						}
					} else {
						foreach ($installer->publishedDirectories() as $id => $dir)
							dirItem($dir);
					}
				?>
				</ol>
			</p>
		</div>
		<?php pageFooter(); ?>
	</body>
</html>