<?php
/*
 * usage: php add_new_site.php mysite.com [-laravel] [-slash] [-www] [-nobots] [-minimvc]
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
$usageMessage =  "php add_new_site.php mysite.com [-laravel] [-slash] [-www] [-nobots] [-minimvc]\n";
$usageMessage .= "Site name must have a top-level domain and a second-level domain.\n";
$usageMessage .= "-laravel flag will set up a new laravel project\n";
$usageMessage .= "-www flag will add www redirect rules to the host file\n";
$usageMessage .= "-slash flag will add trailing slash redirect rules to the host file\n";
$usageMessage .= "-nobots flag will add a robots.txt file which will disallow web crawlers (that listen to it)\n";
$usageMessage .= "-minimvc flag will install my mini-mvc framework\n";

//set flag defaults (if a flag is set, it will orverride these)
$laravel = false; // do not install laravel framework
$nobots = false; // do not add robots.txt file
$minimvc = false; // do not ainstall MiniMVC
$rewriteEngine = '#'; // turn off RewriteEngine in host file
$www = '#'; //comment out www redirect rules in host file
$slash = '#'; // comment out trailing slash redirect rules in host file
$scriptDir = dirname(__FILE__);

//get args and validate them
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
            case '-minimvc': $minimvc = true; break;
            case '-nobots': $nobots = true; break;
            case '-www':
                $rewriteEngine = '';
                $www = ''; // will not comment out www redirect rules in host file
                break;
            case '-slash':
                $rewriteEngine = '';
                $slash = ''; // will not comment out trailing slash redirect rules in host file
                break;
            default: echo $usageMessage; exit;
        }
    }
}

$cfg                = require $scriptDir.'/config.php';
$indexFileContents  = require $scriptDir.'/site_index.php';
$vhostFileContents  = require $scriptDir.'/vhost.php';
$robotsFileContents = ($nobots) ? require $scriptDir.'/robots.php' : null;

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
} elseif ($minimvc) {
    // create site folder
    createDirectory($cfg['paths']['sites_dir'].'/'.$siteName, 0755, $cfg['user']);
    if (!chdir($cfg['paths']['sites_dir'].'/'.$siteName)) {
        echo 'Failed to change directory to '.$cfg['paths']['sites_dir'].'/'.$siteName."\n";
        exit;
    }
    echo shell_exec('git clone https://github.com/mrb2590/mini-mvc.git');
    echo shell_exec('cp -a '.$cfg['paths']['sites_dir'].'/'.$siteName.'/mini-mvc/* '.$cfg['paths']['sites_dir'].'/'.$siteName);
    echo shell_exec('rm -r '.$cfg['paths']['sites_dir'].'/'.$siteName.'/mini-mvc');
    echo shell_exec('composer install');
    //set ownership and permissions
    echo shell_exec('chown '.$cfg['user'].' -R '.$cfg['paths']['sites_dir'].'/'.$siteName);
    echo shell_exec('chmod 775 -R '.$cfg['paths']['sites_dir'].'/'.$siteName);
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

    // add robots.txt if flag is set
    if ($nobots) {
        createFile($cfg['paths']['sites_dir'].'/'.$siteName.'/public/robots.txt', $robotsFileContents, 0755, $cfg['user']);
    }

    //set ownership and permissions
    echo shell_exec('chown '.$cfg['user'].' -R '.$cfg['paths']['sites_dir'].'/'.$siteName);
    echo shell_exec('chmod 775 -R '.$cfg['paths']['sites_dir'].'/'.$siteName);
}

// create virtual host file
createFile($cfg['paths']['sites_avail_dir'].'/'.$siteName.'.conf', $vhostFileContents, 0644, 'root');

// create apache log directory for this site
createDirectory($cfg['paths']['apache_log_dir'].'/'.$siteName, 0755, 'root');

//enable site and reload apache
echo shell_exec('a2ensite '.$siteName);
echo shell_exec('service apache2 reload');
