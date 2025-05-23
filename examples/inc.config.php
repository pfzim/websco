<?php
	define('DB_RW_HOST', 'localhost');
	define('DB_USER', 'wscouser');
	define('DB_PASSWD', '');
	define('DB_NAME', 'websco');
	define('DB_CPAGE', 'utf8');
	define('DB_PREFIX', 'w_');

	/*
		USE_GSSAPI required for create keytab file.
		
		ktpass -princ <HTTP/websco.contoso.com@CONTOSO.COM> -mapuser <svc_websco> -crypto ALL -ptype KRB5_NT_PRINCIPAL -pass <password> -target dc.contoso.com -out c:\temp\websco.keytab

		configure krb5.conf:
		[libdefaults]
			default_realm = CONTOSO.COM
			default_client_keytab_name = FILE:/etc/kerberos/websco.keytab
			default_ccache_name = FILE:/tmp/krb5cc_%{uid}
			#default_keytab_name = FILE:/etc/kerberos/websco.keytab

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
			kinit -V -ki -S HTTP/websco.contoso.com
			klist

			ktinit -ki
			kinit -S HTTP/websco.contoso.com -p <any_user>@CONTOSO.COM
			klist
			
			check KVNO version:
			  klist -k /etc/kerberos/websco.keytab
			  Get-ADUser svc_websco -Property msDS-KeyVersionNumber
		
		Clearing Kerberos authorization tickets after adding a WebSCO service account to an AD group or updating a keytab:
			Linux:
				kdestroy -A
				kdestroy -A -c /tmp/krb5cc_<user_id>
			
			Windows:
				klist purge
	*/

	define('USE_GSSAPI', TRUE);

	define('USE_LDAP', TRUE);
	define('LDAP_CERT_IGNORE', FALSE);
	define('LDAP_URI', 'ldap://contoso-dc-01 ldap://contoso-dc-02');
	define('LDAP_USER', 'domain\\websco');
	define('LDAP_PASSWD', '');
	define('LDAP_BASE_DN', 'DC=contoso,DC=local');
	define('LDAP_USE_SID', TRUE);

	define('APP_LANGUAGE', 'en');

	// define('LDAP_ADMIN_GROUP_DN', 'CN=WEBSCO-Administrators,OU=Administrators,OU=DC=contoso,DC=local');

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

	// define('ORCHESTRATOR_VERSION', 2022); -- remove this line
	define('ORCHESTRATOR_URL', 'http://srv-scorh-01.contoso.com:81/Orchestrator2012/Orchestrator.svc');
	define('ORCHESTRATOR_USER', 'domain\\websco');
	define('ORCHESTRATOR_PASSWD', '');

	define('ORCHESTRATOR2022_URL', 'https://srv-scorh-02.contoso.com:8443/api');
	define('ORCHESTRATOR2022_USER', 'domain\\websco');
	define('ORCHESTRATOR2022_PASSWD', '');

	define('AWX_URL', 'https://awx.contoso.com');
	define('AWX_USER', 'websco');
	define('AWX_PASSWD', '');

	// define('AWX_DONT_PARSE_EXTRA_VARS', FALSE); -- When TRUE, then load only survey variables.
	
	define('USE_MEMCACHED', TRUE);

	define('WEB_URL', 'https://websco.contoso.com/websco/');
	define('WEB_LINK_BASE_PATH', '/websco/');
	define('USE_PRETTY_LINKS', FALSE);
	define('USE_PRETTY_LINKS_FORCE', FALSE);
	
	define('LOG_FILE', '/var/log/websco/websco.log');
