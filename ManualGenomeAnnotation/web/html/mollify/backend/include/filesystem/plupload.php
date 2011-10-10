<?php
/**
 * upload.php
 *
 * Copyright 2009, Moxiecode Systems AB
 * Released under GPL License.
 *
 * License: http://www.plupload.com/license
 * Contributing: http://www.plupload.com/contributing
 */

function plupload($folder) {
	// HTTP headers for no cache etc
	header('Content-type: text/plain; charset=UTF-8');
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");

	// Settings
	$targetDir = $folder->filesystem()->localPath($folder);
	$cleanupTargetDir = false; // Remove old files
	$maxFileAge = 60 * 60; // Temp file age in seconds

	// 5 minutes execution time
	@set_time_limit(5 * 60);
	// usleep(5000);

	// Get parameters
	$chunk = isset($_REQUEST["chunk"]) ? $_REQUEST["chunk"] : 0;
	$chunks = isset($_REQUEST["chunks"]) ? $_REQUEST["chunks"] : 0;
	$fileName = isset($_REQUEST["name"]) ? $_REQUEST["name"] : '';

	// Clean the fileName for security reasons
	$fileName = preg_replace('/[^\w\._]+/', '', $fileName);
	
	// Create target dir
	if (!file_exists($targetDir))
		@mkdir($targetDir);

	// Remove old temp files
	if (is_dir($targetDir) && ($dir = opendir($targetDir))) {
		while (($file = readdir($dir)) !== false) {
			$filePath = $targetDir . DIRECTORY_SEPARATOR . $file;

			// Remove temp files if they are older than the max age
			if (preg_match('/\\.tmp$/', $file) && (filemtime($filePath) < time() - $maxFileAge))
				@unlink($filePath);
		}

		closedir($dir);
	} else
		throw new ServiceException("UPLOAD_FAILED", "Failed to open temp directory.");

	$file = $targetDir.$fileName;
	Logging::logDebug("Uploading to ".$file);

	if (isset($_SERVER["CONTENT_TYPE"]) and strpos($_SERVER["CONTENT_TYPE"], "multipart") !== false) {
		if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {		
			$out = fopen($file, $chunk == 0 ? "wb" : "ab");
			if (!$out) throw new ServiceException("UPLOAD_FAILED", "Failed to open output stream");

			$in = fopen($_FILES['file']['tmp_name'], "rb");
			if (!$in) throw new ServiceException("UPLOAD_FAILED", "Failed to open input stream");
			
			while ($buff = fread($in, 4096))
				fwrite($out, $buff);

			fclose($out);
			fclose($in);
			unlink($_FILES['file']['tmp_name']);
		} else {
			throw new ServiceException("UPLOAD_FAILED", "Failed to move uploaded file.");
		}
	} else {
		$out = fopen($file, $chunk == 0 ? "wb" : "ab");
		if (!$out) throw new ServiceException("UPLOAD_FAILED", "Failed to open output stream.");
		
		$in = fopen("php://input", "rb");
		if (!$in) throw new ServiceException("UPLOAD_FAILED", "Failed to open input stream.");
		
		while ($buff = fread($in, 4096)) {
			fwrite($out, $buff);
		}
		fclose($out);
		fclose($in);			
	}
}
?>