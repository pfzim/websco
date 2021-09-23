# WebSCO - alternative web interface for System Center Orchestrator

## Installation

```
sudo apt-get install php php-mysql php-ldap php-curl php-xml
sudo apt-get install memcached php-memcached
sudo apt-get install libsasl2-modules-gssapi-mit ldap-utils libapache2-mod-auth-kerb libapache2-mod-auth-gssapi libsasl2-2 krb5-clients krb5-user krb5 ldap-utils gss-ntlmssp?
```

## Screenshots

### Runbook input form
![Runbook input form example](/docs/screenshots/runbook1.png "Runbook input form example")

### Result
![Result example](/docs/screenshots/result2.png "Result example")

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
