<?php

return
'<virtualHost *:80>
        ServerAdmin mike@sendmejokes.com
        ServerName '.$siteName.'
        ServerAlias '.$siteName.'

        SetEnv APP_ENV "development"
        SetEnv APP_DEBUG "true"

        DocumentRoot '.$cfg['paths']['sites_dir'].'/'.$siteName.'/public

        <Directory '.$cfg['paths']['sites_dir'].'/'.$siteName.'/public>
                Options Indexes FollowSymLinks MultiViews
                AllowOverride All
                Order allow,deny
                allow from all
        </Directory>

        ErrorLog ${APACHE_LOG_DIR}/'.$siteName.'/error.log

        # Possible values include: debug, info, notice, warn, error, crit,
        # alert, emerg.
        LogLevel warn

        CustomLog ${APACHE_LOG_DIR}/'.$siteName.'/access.log combined
</VirtualHost>';