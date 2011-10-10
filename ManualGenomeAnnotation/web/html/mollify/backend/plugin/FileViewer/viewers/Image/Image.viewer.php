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

	class ImageViewer extends EmbeddedContentViewer {
		protected function getHtml($item, $full) {
			return '<img src="'.$this->getContentUrl($item).'">';
		}
	}
?>