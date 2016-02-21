<?php
/*
 * usage: php add_new_site.php mysite.com [-www]
 * -www flag will enable redirecting mysite.com to www.mysite.com
*/

// user must be root
if (posix_getuid() != 0) {
    echo "Run script as root\n";
    exit;
}

function createDirectory($path, $permissions, $owner) {
    // check if folder exists
    if (file_exists($path)) {
        echo $path." already exists! Skipping...\n";
        return;
    }

    // make directory
    if (!mkdir($path, $permissions)) {
        echo "Failed to create ".$path."\n";
        return;
    }

    // set owner
    if (!chown($path, $owner)) {
        echo "Failed to set owner of ".$path." to ".$owner."\n";
        return;
    }
}

function createFile($path, $fileContents, $permissions, $owner) {
    if (!$file = fopen($path, "w")) {
        echo "Failed to create ".$path."\n";
    } else {
        if (!fwrite($file, $fileContents)) {
            echo "Failed to write to ".$path."\n";
        }
    }

    fclose($file);

    // change permissions of public/index.php
    if (!chmod($path, $permissions)) {
        echo "Failed to change permissions on ".$path." to ".(string)$permissions."\n";
    }

    // change owner of public/index.php
    if (!chown($path, $owner)) {
        echo "Failed to set owner on ".$path." to ".$owner."\n";
    }
}

// validate site name
if (isset($argv[1])) {
    // site name must contain a '.'
    if (strpos($argv[1], ".") === false) {
        echo "Invalid usage.\n";
        echo "Site name should at least have a top-level domain and a second-level domain.\n";
        exit;
    } else {
        $siteName = $argv[1];
    }
} else {
    echo "Invalid usage.\n";
    echo "Usage: php add_new_site.php mysite.com [-www]\n";
    echo "-www flag will enable redirecting mysite.com to www.mysite.com\n";
    exit;
}

// validate flag
if (isset($argv[2])) {
    if ($argv[2] != '-www') {
        echo "Invalid flag.\n";
        echo "Usage: php add_new_site.php mysite.com [-www]\n";
        echo "-www flag will enable redirecting mysite.com to www.mysite.com\n";
        exit;
    } else {
        $www = true;
    }
} else {
    $www = false;
}

$cfg               = require "config.php";
$indexFileContents = require "my_index.php";
$vhostFileContents = require ($www) ? "vhost_www_redirect.php" : "vhost.php";

// websites directroy structure
//
// siteName
// └── public
//     ├── css
//     ├── doc
//     ├── img
//     │   ├── content
//     │   └── layout
//     ├── index.php
//     └── js
//
$directoryArray = array(
    "public",
    "public/css",
    "public/doc",
    "public/img",
    "public/img/content",
    "public/img/layout",
    "public/js"
);

// create websites folders
foreach ($directoryArray as $dir) {
    createDirectory($cfg['paths']['sites_dir']."/".$siteName."/".$dir, 0755, $cfg['user']);
}

// create public/index.php
createFile($cfg['paths']['sites_dir']."/".$siteName."/public/index.php", $indexFileContents, 0755, $cfg['user']);

// create virtual host file
createFile($cfg['paths']['sites_avail_dir']."/".$siteName.".conf", $vhostFileContents, 0644, 'root');

// create apache log directory for this site
createDirectory($cfg['paths']['apache_log_dir']."/".$siteName, 0755, 'root');

//enable site and reload apache
exec('a2ensite '.$siteName);
echo "Enabled site\r\n";
exec('/etc/init.d/apache2 reload');
echo "Apache has reloaded\r\n";