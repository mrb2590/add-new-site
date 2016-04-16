<?php

return
'<virtualHost *:80>
        ServerAdmin '.$cfg['email'].'
        ServerName '.$siteName.'
        '.$www.'ServerAlias www.'.$siteName.'

        SetEnv APP_ENV "production"
        #SetEnv APP_ENV "development"

        DocumentRoot '.$cfg['paths']['sites_dir'].'/'.$siteName.'/public

        <Directory '.$cfg['paths']['sites_dir'].'/'.$siteName.'/public>
                Options Indexes FollowSymLinks MultiViews
                AllowOverride All
                Order allow,deny
                allow from all
        </Directory>

        '.$rewriteEngine.'RewriteEngine On
        # redirect to www.
        '.$www.'RewriteCond %{HTTP_HOST} ^'.$siteName.'
        '.$www.'RewriteRule ^/(.*)$ http://www.'.$siteName.'/$1 [R=301,L]

        # add trailing slash
        '.$slash.'RewriteCond %{REQUEST_URI}  !\.(.*)$
        '.$slash.'RewriteRule ^/(.*)([^/])$ http://'.$siteName.'/$1$2/ [R=301,L]

        ErrorLog ${APACHE_LOG_DIR}/'.$siteName.'/error.log

        # Possible values include: debug, info, notice, warn, error, crit,
        # alert, emerg.
        LogLevel warn

        CustomLog ${APACHE_LOG_DIR}/'.$siteName.'/access.log combined
</VirtualHost>';
