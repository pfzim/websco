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
			default_client_keytab_name = FILE:/etc/kerberos/server.keytab
			default_ccache_name = FILE:/tmp/krb5cc_%{uid}
			#default_keytab_name = FILE:/etc/kerberos/server.keytab

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
		[logging]
			kdc = FILE:/var/log/krb5/krb5kdc.log
			admin_server = FILE:/var/log/krb5/kadmin.log
			default = FILE:/var/log/krb5/krb5lib.log
			
		check:
			ktinit -ki
			kinit -S HTTP/web.contoso.com -p <any_user>@CONTOSO.COM
			klist
		
		kdestroy -A
		
		after update keytab run on client:
			klist purge
	*/

	define('USE_GSSAPI', TRUE);

	define('USE_LDAP', TRUE);
	define('LDAP_URI', 'ldap://contoso-dc-01 ldap://contoso-dc-02');
	define('LDAP_USER', 'domain\\websco');
	define('LDAP_PASSWD', '');
	define('LDAP_BASE_DN', 'DC=contoso,DC=local');

	define('APP_LANGUAGE', 'en');

	//define('LDAP_ADMIN_GROUP_DN', 'CN=WEBSCO-Administrators,OU=Administrators,OU=DC=contoso,DC=local');

	define('MAIL_HOST', 'smtp.contoso.com');
	define('MAIL_FROM', 'no-reply@contoso.com');
	define('MAIL_FROM_NAME', 'WebSCO');
	define('MAIL_AUTH', TRUE);
	define('MAIL_LOGIN', '');
	define('MAIL_PASSWD', '');
	define('MAIL_SECURE', '');
	define('MAIL_PORT', 25);
	define('MAIL_TO_ADMIN', 'admin@contoso.com');
	define('MAIL_VERIFY_PEER', TRUE);
	define('MAIL_VERIFY_PEER_NAME', TRUE);
	define('MAIL_ALLOW_SELF_SIGNED', FALSE);

	define('ORCHESTRATOR_URL', 'http://srv-scor-01.contoso.com:81/Orchestrator2012/Orchestrator.svc');
	define('ORCHESTRATOR_USER', 'domain\\websco');
	define('ORCHESTRATOR_PASSWD', '');

	define('USE_MEMCACHED', TRUE);

	define('APP_URL', 'https://websco.contoso.com/websco/');
	define('USE_PRETTY_LINKS', FALSE);
	define('USE_PRETTY_LINKS_FORCE', FALSE);
	define('PRETTY_LINKS_BASE_PATH', '/websco/');
