<?php

return
'<virtualHost *:80>
        ServerAdmin mike@sendmejokes.com
        ServerName '.$siteName.'
        ServerAlias '.$siteName.'

        SetEnv APP_ENV "production"

        DocumentRoot '.SITES_DIR.'/'.$siteName.'/public

        <Directory '.SITES_DIR.'/'.$siteName.'/public>
                Options Indexes FollowSymLinks MultiViews
                AllowOverride All
                Order allow,deny
                allow from all
        </Directory>

        RewriteEngine On
        # add trailing slash
        RewriteCond %{REQUEST_URI}  !\.(.*)$
        RewriteRule ^/(.*)([^/])$ http://'.$siteName.'/$1$2/ [R=301,L]

        ErrorLog ${APACHE_LOG_DIR}/'.$siteName.'/error.log

        # Possible values include: debug, info, notice, warn, error, crit,
        # alert, emerg.
        LogLevel warn

        CustomLog ${APACHE_LOG_DIR}/'.$siteName.'/access.log combined
</VirtualHost>';