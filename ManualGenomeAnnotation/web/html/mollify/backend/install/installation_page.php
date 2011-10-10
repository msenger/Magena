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

	global $MAIN_PAGE, $installer;
	if (!isset($MAIN_PAGE)) die();
	
	function pageHeader($title, $onLoad = NULL) { ?>
		<head>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<title><?php echo($title);?></title>
			<link rel="stylesheet" href="../install/resources/style.css">
			
			<script type="text/javascript" src="../resources/jquery-1.3.2.min.js"></script>
			<script type="text/javascript" src="../resources/md5.js"></script>
			<script type="text/javascript" src="../install/resources/common.js"></script>
			<script type="text/javascript">
			<?php if ($onLoad != NULL) {?>
				$(document).ready(function() {
					$('.btn').each(function() {
						var b = $(this);
						var tt = b.text() || b.val();
						
						if ($(':submit,:button',this)) {
							b = $('<a>').insertAfter(this). addClass(this.className).attr('id',this.id);
							$(this).remove();
						}
						b.text('').css({cursor:'pointer'}). prepend('<i></i>').append($('<span>').
						text(tt).append('<i></i><span></span>'));
					});
					
					<?php echo($onLoad);?>();
				 });
			<?php }?>
			</script>
		</head><?php
	}
	
	function pageBody($title, $subTitle = NULL) {
		global $installer; ?>
		<header>
			<h1><?php echo $title; ?></h1>
		</header>
		<?php if ($subTitle != NULL) {?>
		<subheader>
			<h1><?php echo $subTitle; ?></h1>
		</subheader>
		<?php }?>
		<?php if (isset($installer) and $installer->hasError()) { ?>
		<div class="error">
			<div class="title"><?php echo $installer->error(); ?></div>
			<?php if ($installer->hasErrorDetails()) { ?><div class="details"><?php echo $installer->errorDetails(); ?></div><?php } ?>
		</div>	
		<?php } ?>

		<form id="page-data" method="post">
		<?php if (isset($installer)) foreach ($installer->data() as $key => $val) if ($key != 'action') echo '<input type="hidden" name="'.$key.'" value="'.$val.'">';?>
		</form><?php
 	}
 	
 	function pageFooter() { ?>
 		<div class="clear" />
 		<footer>
 			Copyright &copy; Samuli J&auml;rvel&auml; 2008 -
 		</footer><?php
 	}
?>