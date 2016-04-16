<?php

return
'<virtualHost *:80>
        ServerAdmin '.$cfg['email'].'
        ServerName '.$siteName.'
        #ServerAlias '.$siteName.'

        SetEnv APP_ENV "prod"

        DocumentRoot '.$cfg['paths']['sites_dir'].'/'.$siteName.'/public

        <Directory '.$cfg['paths']['sites_dir'].'/'.$siteName.'/public>
                Options Indexes FollowSymLinks MultiViews
                AllowOverride All
                Order allow,deny
                allow from all
        </Directory>

        #RewriteEngine On
        # redirect to www.
        #RewriteCond %{HTTP_HOST} ^'.$siteName.'
        #RewriteRule ^/(.*)$ http://www.'.$siteName.'/$1 [R=301,L]

        # add trailing slash
        #RewriteCond %{REQUEST_URI}  !\.(.*)$
        #RewriteRule ^/(.*)([^/])$ http://'.$siteName.'/$1$2/ [R=301,L]

        ErrorLog ${APACHE_LOG_DIR}/'.$siteName.'/error.log

        # Possible values include: debug, info, notice, warn, error, crit,
        # alert, emerg.
        LogLevel warn

        CustomLog ${APACHE_LOG_DIR}/'.$siteName.'/access.log combined
</VirtualHost>';