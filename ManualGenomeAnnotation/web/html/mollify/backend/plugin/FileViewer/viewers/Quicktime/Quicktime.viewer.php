<?php
	class QuicktimeViewer extends EmbeddedContentViewer {
		protected function getEmbeddedSize() {
			return array("450", "300");
		}
		
		protected function getResizedElementId() {
			return "quicktime-player";
		}
		
		protected function getHtml($item, $full) {
			return '<embed id="quicktime-player" width="400" height="240" src="'.$this->getContentUrl($item).'" autoplay="true" controller="true" pluginspace="/quicktime/download/">';
		}		
	}
?>