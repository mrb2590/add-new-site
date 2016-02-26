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
    if (!mkdir($path, $permissions, true)) {
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

$usageMessage =  "Usage: php add_new_site.php mysite.com -www -laravel\n";
$usageMessage .= "Site name should at least have a top-level domain and a second-level domain.\n";
$usageMessage .= "-www flag will enable redirecting mysite.com to www.mysite.com\n";
$usageMessage .= "-laravel flag will set up a new laravel project\n";

//get args and validate them
$www     = false;
$laravel = false;
foreach($argv as $i => $flag) {
    if ($i == 0) {
        continue;
    } elseif ($i == 1) {
        if (strpos($flag, ".") === false) {
            echo $usageMessage;
            exit;
        } else {
            $siteName = $flag;
        }
    } elseif ($i == 2) {
        if ($flag == '-www') {
            $www = true;
        } elseif ($flag == '-laravel') {
            $laravel = true;
        } else {
            echo $usageMessage;
            exit;
        }
    }
}

$cfg               = require "config.php";
$indexFileContents = require "my_index.php";
if ($www) {
    $vhostFileContents = require "vhost_www_redirect.php";
} elseif ($laravel) {
    $vhostFileContents = require "vhost_laravel.php";
} else {
    $vhostFileContents = require "vhost.php";
}

if ($laravel) {
    if (!chdir($cfg['paths']['sites_dir'])) {
        echo "Failed to change directory to ".$cfg['paths']['sites_dir']."\n";
        exit;
    }
    echo shell_exec('laravel new '.$siteName);
    shell_exec('chown '.$cfg['user'].' -R '.$cfg['paths']['sites_dir']);
    shell_exec('chmod 755 -R '.$cfg['paths']['sites_dir']);
    shell_exec('chmod 777 -R '.$cfg['paths']['sites_dir'].'/'.$siteName.'/storage');
    shell_exec('chmod 777 -R '.$cfg['paths']['sites_dir'].'/'.$siteName.'/bootstrap/cache');
} else {
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
}

// create virtual host file
createFile($cfg['paths']['sites_avail_dir']."/".$siteName.".conf", $vhostFileContents, 0644, 'root');

// create apache log directory for this site
createDirectory($cfg['paths']['apache_log_dir']."/".$siteName, 0755, 'root');

//enable site and reload apache
echo shell_exec('a2ensite '.$siteName);
echo shell_exec('/etc/init.d/apache2 reload');