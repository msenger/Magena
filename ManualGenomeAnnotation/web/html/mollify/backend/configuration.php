<?php
    $CONFIGURATION_PROVIDER = "file";
    $USERS = array();

    $PUBLISHED_FOLDERS = array(
      "1" => array("name" => "Data", "path" => "/home/senger/magena/web/datadir/data")
    );

    $DEFAULT_PERMISSION = "RW";

    $SETTINGS = array(
      "enable_zip_download" => TRUE,
      "host_public_address" => "http://localhost"
    );

    $PLUGINS = array(
      "FileViewer" => array(
         "viewers" => array(
            "Image" => array("gif", "png", "jpg"),
            "TextFile" => array("txt", "js", "css", "xml", "html", "xhtml", "py", "c", "cpp",
                                "as3", "sh", "java", "sql", "php",
                                "gff", "bls")
            ),
         "previewers" => array(
            "Image" => array("gif", "png", "jpg")
            )
         )
      );
?>
