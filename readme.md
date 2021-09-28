# WebSCO - alternative web interface for System Center Orchestrator

## Installation

```
sudo apt-get install php php-mysql php-ldap php-curl php-xml
sudo apt-get install memcached php-memcached
```

Only when using Kerberos:
```
sudo apt-get install libsasl2-modules-gssapi-mit ldap-utils libapache2-mod-auth-kerb libapache2-mod-auth-gssapi libsasl2-2 krb5-clients krb5-user krb5 ldap-utils gss-ntlmssp
```

Example Apache config:
```
<IfModule mod_ssl.c>
        <VirtualHost *:443>
                ServerName localhost
                ServerAdmin webmaster@localhost

                DocumentRoot /var/www/html

                <Directory /var/www/html/websco>
                        Options Indexes FollowSymLinks
                        AllowOverride All
                        Require all granted
                </Directory>

                ErrorLog ${APACHE_LOG_DIR}/error.log
                CustomLog ${APACHE_LOG_DIR}/access.log combined

                SSLEngine on

                SSLCertificateFile    /etc/ssl/certs/websco.cer
                SSLCertificateKeyFile /etc/ssl/private/websco.key

                <FilesMatch "\.(cgi|shtml|phtml|php)$">
                                SSLOptions +StdEnvVars
                </FilesMatch>
                <Directory /usr/lib/cgi-bin>
                                SSLOptions +StdEnvVars
                </Directory>

        </VirtualHost>
</IfModule>
```

## Screenshots

### Runbook input form
![Runbook input form example](/docs/screenshots/runbook1.png "Runbook input form example")

### Result
![Result example](/docs/screenshots/result1.png "Result example")

### Runbook input form
![Runbook input form example](/docs/screenshots/runbook2.png "Runbook input form example")

### Result
![Result example](/docs/screenshots/result2.png "Result example")

## Technical information

Bit `flags` in table `runbooks`

| Bits   | Description                               |
|--------|-------------------------------------------|
| 0x0001 | Deleted                                   |
| 0x0002 | Hide from list                            |


Query all ACL:

```
SELECT
	f.`id`,
	f.`name`,
	a.`dn`,
	HEX(a.`allow_bits`)
FROM w_access AS a
LEFT JOIN w_runbooks_folders AS f
	ON f.`id` = a.`oid`
ORDER BY
	a.`dn`,
	f.`name`
;
```
