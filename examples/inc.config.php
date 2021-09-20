<?php
	define('DB_RW_HOST', 'localhost');
	define('DB_USER', 'wscouser');
	define('DB_PASSWD', '');
	define('DB_NAME', 'websco');
	define('DB_CPAGE', 'utf8');
	define('DB_PREFIX', 'w_');

	/*
		USE_GSSAPI required for create keytab file.
		
		ktpass -princ <HTTP/web.contoso.com@CONTOSO.COM> -mapuser <svc_user> -crypto ALL -ptype KRB5_NT_PRINCIPAL -pass <password> -target dc.contoso.com -out c:\temp\server.keytab

		configure krb5.conf:
		[libdefaults]
			default_realm = CONTOSO.COM

		[realms]
			CONTOSO.COM = {
				kdc = 10.0.0.1
				kdc = 10.0.0.2
				kdc = 10.0.0.3
				kdc = 10.0.0.4
				admin_server = 10.0.0.1
			}
		[domain_realm]
			.contoso.com = CONTOSO.COM
			contoso.com = CONTOSO.COM
			
		check:
			kinit -S HTTP/web.contoso.com -p <any_user>@CONTOSO.COM
			klist
		
		kdestroy -A
	*/

	define('USE_GSSAPI', TRUE);

	define('LDAP_URI', 'ldap://contoso-dc-01');
	define('LDAP_PORT', 389);
	define('LDAP_USER', 'domain\\websco');
	define('LDAP_PASSWD', '');
	define('LDAP_BASE_DN', 'DC=contoso,DC=local');
	define('LDAP_ADMIN_GROUP_DN', 'CN=WEBSCO-Administrators,OU=Administrators,OU=DC=contoso,DC=local');

	define('MAIL_HOST', 'smtp.contoso.com');
	define('MAIL_FROM', 'no-reply@contoso.com');
	define('MAIL_FROM_NAME', 'WebSCO');
	define('MAIL_AUTH', TRUE);
	define('MAIL_LOGIN', '');
	define('MAIL_PASSWD', '');
	define('MAIL_SECURE', '');
	define('MAIL_PORT', 25);
	define('MAIL_TO_ADMIN', 'admin@contoso.com');

	define('WS_URL', 'http://websco.contoso.com/');
	define('ORCHESTRATOR_URL', 'http://contoso-scor-01:81/Orchestrator2012/Orchestrator.svc');
