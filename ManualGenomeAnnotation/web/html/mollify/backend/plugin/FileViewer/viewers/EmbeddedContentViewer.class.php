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

	abstract class EmbeddedContentViewer extends ViewerBase {
		public function getInfo($item) {
			return array(
				"embedded" => $this->getDataUrl($item, "embedded"),
				"full" => $this->getDataUrl($item, "full", TRUE)
			);
		}
		
		public function processDataRequest($item, $path) {
			if (count($path) != 1) throw $this->invalidRequestException();
			
			if ($path[0] === 'full')
				$this->processFullPageRequest($item);
			else if ($path[0] === 'embedded')
				$this->processEmbeddedViewRequest($item);
			else
				throw $this->invalidRequestException();
		}

		protected function processEmbeddedViewRequest($item) {
			$html = $this->getHtml($item, FALSE);
			$size = $this->getEmbeddedSize();
			$element = $this->getResizedElementId();
			
			$result = array("html" => $html);
			if ($size) $result["size"] = $size[0].";".$size[1];
			if ($element) $result["resized_element_id"] = $element;
			
			$this->response()->success($result);
		}

		protected function processFullPageRequest($item) {
			$html = "<html><head><title>".$item->name()."</title></head><body>".$this->getHtml($item, TRUE)."</body></html>";
			$this->response()->html($html);
		}
		
		protected function getEmbeddedSize() {
			return NULL;
		}
		
		protected function getResizedElementId() {
			return NULL;
		}
		
		protected abstract function getHtml($item, $full);
		
	}
	
	abstract class EmbeddedContentOnlyViewer extends EmbeddedContentViewer {
		public function getInfo($item) {
			return array(
				"embedded" => $this->getDataUrl($item, "embedded")
			);
		}
	}
?>