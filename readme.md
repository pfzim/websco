# WebSCO - alternative web interface for System Center Orchestrator

## Installation

```
sudo apt-get install php php-mysql php-ldap php-curl php-xml
```


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