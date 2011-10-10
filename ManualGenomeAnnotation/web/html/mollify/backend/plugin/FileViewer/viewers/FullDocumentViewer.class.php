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

	abstract class FullDocumentViewer extends ViewerBase {
		public function getInfo($item) {
			return array(
				"embedded" => $this->getDataUrl($item, "embedded"),
				"full" => $this->getDataUrl($item, "view", TRUE)
			);
		}
		
		public function processDataRequest($item, $path) {
			if (count($path) != 1) throw $this->invalidRequestException();
			
			if ($path[0] === 'view')
				$this->processViewRequest($item);
			else if ($path[0] === 'embedded')
				$this->processEmbeddedViewRequest($item);
			else
				throw $this->invalidRequestException();
		}

		protected function processEmbeddedViewRequest($item) {
			$html = '<iframe id="viewer-frame" src="'.$this->getDataUrl($item, "view", TRUE).'?embedded=true" style="border: none;"></iframe>';
			$size = $this->getEmbeddedSize();
			 
			$this->response()->success(array(
				"html" => $html,
				"resized_element_id" => "viewer-frame",
				"size" => $size[0].";".$size[1]
			));
		}
		
		protected function getEmbeddedSize() {
			return array("600", "400");
		}
		
		protected abstract function getHtml($item, $full);
		
		protected function processViewRequest($item) {
			$full = $this->request()->hasParam("embedded") and (strcasecmp("true", $this->request()->param("embedded")) == 0);
			$this->response()->html($this->getHtml($item, $full));
		}
	}
	
	abstract class FullPageOnlyViewer extends FullDocumentViewer {
		public function getInfo($item) {
			return array(
				"full" => $this->getDataUrl($item, "view", TRUE)
			);
		}
	}
	
	abstract class FullDocumentEmbeddedOnlyViewer extends FullDocumentViewer {
		public function getInfo($item) {
			return array(
				"embedded" => $this->getDataUrl($item, "embedded")
			);
		}
	}
?>