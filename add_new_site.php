<?php
/*
 * usage: php add_new_site.php mysite.com [-laravel]
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
        echo 'Failed to create '.$path."\n";
        return;
    }

    // set owner
    if (!chown($path, $owner)) {
        echo 'Failed to set owner of '.$path.' to '.$owner."\n";
        return;
    }
}

function createFile($path, $fileContents, $permissions, $owner) {
    if (!$file = fopen($path, 'w')) {
        echo 'Failed to create '.$path."\n";
    } else {
        if (!fwrite($file, $fileContents)) {
            echo 'Failed to write to '.$path."\n";
        }
    }
    fclose($file);
    // change permissions of public/index.php
    if (!chmod($path, $permissions)) {
        echo 'Failed to change permissions on '.$path.' to '.(string)$permissions."\n";
    }
    // change owner of public/index.php
    if (!chown($path, $owner)) {
        echo 'Failed to set owner on '.$path.' to '.$owner."\n";
    }
}

// echoed if an argument is invalid
$usageMessage =  "Usage: php add_new_site.php mysite.com [-laravel]\n";
$usageMessage .= "Site name must have a top-level domain and a second-level domain.\n";
$usageMessage .= "-laravel flag will set up a new laravel project\n";

//get args and validate them
$laravel = false;
if (count($argv) < 2) {
    echo $usageMessage;
    exit;
}
foreach($argv as $i => $flag) {
    if ($i == 0) {
        continue;
    } elseif ($i == 1) {
        if (strpos($flag, '.') === false) {
            echo $usageMessage;
            exit;
        } else {
            $siteName = $flag;
        }
    } elseif ($i > 1) {
        switch ($flag) {
            case '-laravel': $laravel = true; break;
            default: echo $usageMessage; exit;
        }
    }
}

$cfg               = require 'config.php';
$indexFileContents = require 'site_index.php';
$vhostFileContents = require 'vhost.php';

if ($laravel) {
    if (!chdir($cfg['paths']['sites_dir'])) {
        echo 'Failed to change directory to '.$cfg['paths']['sites_dir']."\n";
        exit;
    }
    echo shell_exec('laravel new '.$siteName);
    //change onwership and set permissions
    echo shell_exec('chown '.$cfg['user'].' -R '.$cfg['paths']['sites_dir'].'/'.$siteName);
    echo shell_exec('chmod 755 -R '.$cfg['paths']['sites_dir'].'/'.$siteName);
    echo shell_exec('chmod 777 -R '.$cfg['paths']['sites_dir'].'/'.$siteName.'/storage');
    echo shell_exec('chmod 777 -R '.$cfg['paths']['sites_dir'].'/'.$siteName.'/bootstrap/cache');
} else {
    // websites directory structure
    //
    // siteName
    // └── public
    //     ├── css
    //     ├── doc
    //     ├── fonts
    //     ├── img
    //     │   ├── content
    //     │   └── layout
    //     ├── index.php
    //     └── js
    //
    $directoryArray = array(
        'public',
        'public/css',
        'public/doc',
        'public/fonts',
        'public/img',
        'public/img/content',
        'public/img/layout',
        'public/js'
    );

    // create websites folders
    foreach ($directoryArray as $dir) {
        createDirectory($cfg['paths']['sites_dir'].'/'.$siteName.'/'.$dir, 0755, $cfg['user']);
    }

    // copy bootstrap framework
    echo shell_exec('cp '.$cfg['paths']['resources'].'/bootstrap/bootstrap-3.3.6-dist/css/bootstrap.min.css '.$cfg['paths']['sites_dir'].'/'.$siteName.'/public/css/');
    echo shell_exec('cp '.$cfg['paths']['resources'].'/bootstrap/bootstrap-3.3.6-dist/js/bootstrap.min.js '.$cfg['paths']['sites_dir'].'/'.$siteName.'/public/js/');
    echo shell_exec('cp -R '.$cfg['paths']['resources'].'/bootstrap/bootstrap-3.3.6-dist/fonts/ '.$cfg['paths']['sites_dir'].'/'.$siteName.'/public/');
    // copy font-awesome framework
    echo shell_exec('cp '.$cfg['paths']['resources'].'/font-awesome/font-awesome-4.6.1/css/font-awesome.min.css '.$cfg['paths']['sites_dir'].'/'.$siteName.'/public/css/');
    echo shell_exec('cp -R '.$cfg['paths']['resources'].'/font-awesome/font-awesome-4.6.1/fonts/ '.$cfg['paths']['sites_dir'].'/'.$siteName.'/public/');
    // copy jquery framework
    echo shell_exec('cp '.$cfg['paths']['resources'].'/jquery/jquery-1.12.3/jquery-1.12.3.min.js '.$cfg['paths']['sites_dir'].'/'.$siteName.'/public/js/');

    // create public/index.php
    createFile($cfg['paths']['sites_dir'].'/'.$siteName.'/public/index.php', $indexFileContents, 0755, $cfg['user']);

    //change onwership and set permissions
    echo shell_exec('chown '.$cfg['user'].' -R '.$cfg['paths']['sites_dir'].'/'.$siteName);
    echo shell_exec('chmod 775 -R '.$cfg['paths']['sites_dir'].'/'.$siteName);
}

// create virtual host file
createFile($cfg['paths']['sites_avail_dir'].'/'.$siteName.'.conf', $vhostFileContents, 0644, 'root');

// create apache log directory for this site
createDirectory($cfg['paths']['apache_log_dir'].'/'.$siteName, 0755, 'root');

//enable site and reload apache
echo shell_exec('a2ensite '.$siteName);
echo shell_exec('/etc/init.d/apache2 reload');
