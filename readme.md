# WebSCO - alternative web interface for System Center Orchestrator

[:ru:](#description-ru) [:us:](#description)  

## System requirements
- Apache
- MariaDB (MySQL)
- PHP
- Microsoft System Center Orchestrator
- Active Directory (optional)
- memcached (optional)
- Kerberos (optional)
- mod_rewrite (optional)

## Installation

```
sudo apt-get install apache2 mariadb-server
sudo apt-get install php php-mysql php-ldap php-curl php-xml libapache2-mod-php
sudo apt-get install memcached php-memcached
```

Only when using Kerberos:
```
sudo apt-get install libsasl2-modules-gssapi-mit ldap-utils libapache2-mod-auth-kerb libapache2-mod-auth-gssapi libsasl2-2 krb5-clients krb5-user krb5 ldap-utils gss-ntlmssp
```

If you want to use pretty links, then `mod_rewrite` is required.
By default, the .htaccess file is not enabled. Enable it in Apache config (`AllowOverride All`):
```
	<Directory /var/www/html/websco>
			Options Indexes FollowSymLinks
			AllowOverride All
			Require all granted
	</Directory>
```

Next, you need to run the script http://localhost/websco/install.php through the browser and fill in the parameters.

## Screenshots

### Main screen
![Main screen](/docs/screenshots/main.png "Main screen")

### Runbook classic form
![Runbook input form example](/docs/screenshots/runbook_classic.png "Runbook input form example")

### Same form in WebSCO
![Runbook input form example](/docs/screenshots/runbook1.png "Runbook input form example")

### Result
![Result example](/docs/screenshots/result1.png "Result example")

### Runbook input form
![Runbook input form example](/docs/screenshots/runbook2.png "Runbook input form example")

### Result
![Result example](/docs/screenshots/result2.png "Result example")

## Description

Pros over the standard console:
 - The form for launching runbooks supports drop-down lists, check-boxes, fields for entering dates and numbers
 - Validation of required fields (runbook will not run with an empty required parameter)
 - Parameters are displayed in sorted order, and do not dance haphazardly (it is enough to number them)
 - Restarting runbooks with previously entered parameters
 - Escaping a single quote when passing parameters to the runbook (so that you cannot inject into the runbook code)
 - The description of the runbook is displayed in the launch window
 - Does not required Silverlight

Minuses:
 - The console works from under a service account and all runbooks are launched from under it
 - Access control is regulated in the console settings

To improve the responsiveness of the console, lists of folders, runbooks and
their parameters are loaded into the local database. And to reduce the number
of LDAP requests when checking access rights, you can use memcached.

Access to runbooks is regulated less conveniently by folders, but also through
AD groups. When configuring, you need to specify the DN of the groups. There
is no inheritance, but it is possible to copy the rights to all subfolders.

In order to hide the service account password in the config, you can configure
Kerberos authentication using the keytab file.

In order for the fields to be displayed as a drop-down list, check-box or
calendar for entering a date, you need to add flags to the field names to the
end after / slash:

 - s - regular input field (string)
 - l - dropdown list (list)
 - d - field for entering date (date)
 - t - field for entering time (time)
 - dt - field for entering date with time (datetime)
 - i - field for entering integers (integer)
 - a - field with autocomplete for entering SamAccountName (query LDAP) (account)
 - c - field with autocomplete for entering computer name (query LDAP) (computer)
 - m - field with autocomplete for entering e-mail (query LDAP) (mail)
 - f - checkboxes switches (flags)
 - w - hidden field with login who start runbook in WebSCO (who run)
 - r - the flag means that the parameter is required

You can also use * (asterisk) before the slash to indicate a required parameter.

For a list and check-boxes, in addition, before the slash in brackets, you need
to list the parameters separated by commas. For example:

1. Select the type of access (admin, guest)*/l

This field will turn into a drop-down list with two values ​​admin and guest and
will be required.

2. Select the protocol (HTTP, HTTPS)/rf

In this case, two HTTP and HTTPS checkboxes will be displayed, and at least one
must be checked. The flag r is specified (analogous to the asterisk from the
example above). The selected HTTP will correspond to set bit 1, and HTTPS,
respectively, to bit 2. Check-boxes will be difficult to reproduce in the
standard console if, for some reason, you have to run the runbook from it.

After completing the configuration, you need to load the list of runbooks into
the database by running Sync. And download every time after adding new and
changing existing runbooks (do not forget about the Orchestrator glitch, when
the user does not immediately see the new runbook and needs to clear the cache).
Loading Jobs is not necessary and takes a long time (I have ~ 20,000 jobs loaded
for about 30 minutes), if they have already started, then you need to wait for
the download to finish without interrupting or restarting it.

### Access rights management

Local users are administrators and have access to all sections, can run all
runbooks and manage access rights.

Domain users who have access to the Root level with Execute rights are
administrators of access rights.

Local Administrators and Users with access to Root Level with Execute rights
see hidden folders.

## Description (RU)

Плюсы по сравнению со стандартной консолью:
 - Форма для запуска ранбуков поддерживает выпадающие списки, чек-боксы, поля для ввода дат и цифр
 - Валидация обязательных полей (с незаполненным обязательным параметром ранбук не запустит)
 - Параметры выводятся в отсортированном порядке, а не пляшут как попало (достаточно пронумеровать их)
 - Перезапуск ранбуков с ранее введенными параметрами
 - Экранирование одинарной кавычки при передаче параметров в ранбук (чтобы нельзя было сделать инъекцию в код ранбука)
 - Описание ранбука отображается в окне запуска
 - Не требуется Silverlight

Минусы:
 - Консоль работает из-под сервисной учетной записи и все ранбуки запускаются из-под неё
 - Разграничение доступа регулируется в настройках консоли

Для повышения отзывчивости консоли, списки папок, ранбуков и их параметров
загружаются в локальную БД. А для уменьшения количества LDAP запросов при
проверке прав доступа можно подключить memcached.

Доступ к ранбукам регулируется менее удобно по папкам, но так же через группы
AD. При настройке нужно указывать DN групп. Нет наследования, но есть
возможность скопировать права на все вложенные папки.

Чтобы не "светить" пароль от сервисной учетной записи в конфиге, можете
настроить аутентификацию по Kerberos с использованием keytab файла.

Для того, чтобы поля отображались как выпадающий список, чек-бокс или календарь
для ввода даты, к названиям полей в конце через / слэш нужно добавить ключи:

 - s - обычное поле для ввода (string)
 - l - выпадающий список (list)
 - d - поле для ввода даты (date)
 - t - поле для ввода времени (time)
 - dt - поле для ввода даты и времени (datetime)
 - i - поле для ввода целых чисел (integer)
 - f - переключатели чек-боксы (flags)
 - a - поле с автодополнением SamAccountName (account)
 - c - поле с автодополнением имени компьютера (computer)
 - m - поле с автодополнением e-mail (mail)
 - w - скрытое поле будет содержать логин пользователя, который запусил ранбук в WebSCO (who run)
 - r - флаг означает, что параметр обязателен для заполнения (required)

Еще можно использовать * (звёздочку) перед слэшем для обозначения
обязательного параметра.

Для списка и чек-боксов дополнительно перед слэшем в скобках нужно перечислить
параметры через запятую. Например:

1. Выберите тип доступа (admin, guest)*/l

Такое поле превратится в выпадающий список с двумя значениями admin и guest
и будет обязательным для заполнения.

2. Выберите протокол (HTTP, HTTPS)/rf

В данном случае будет отображено два чек-бокса HTTP и HTTPS и хотя бы один
должен будет отмечен т.к. указан флаг r (аналог звездочки из примера выше).
Выбранный HTTP будет соответствовать взведенному биту 1, а HTTPS
соответственно биту 2. Чек-боксы будет сложно воспроизвести в стандартной
консоли, если по каким-либо причинам придется запускать ранбук из неё.

После завершения настройки нужно загрузить список ранбуков в БД выполнив Sync.
И выполнять загрузку каждый раз после добавления новых и изменения
существующих ранбуков (не забываем про глюк Оркестратора, когда пользователь
не сразу видит новый ранбук и нужно очищать кэш). Загрузка Job'ов не обязательна
и занимает продолжительное время (~20 000 джобов загружаютя около 30 минут),
если уж запустили, то надо дождаться окончания загрузки не прерывая и не
перезапуская её.

### Управление правами доступа

Локальные пользователи являются администраторами и имеют доступ во все разделы,
могут запускать все ранбуки и управлять правами доступа.

Пользователи домена имеющие доступ к Корневому уровню с правами Выполнения
являются администраторами прав доступ.

Локальные администраторы и пользователи имеющие доступ к Корневому уровню
с правами Выполнения видят скрытые папки.

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

Clear logs:
```
TRUNCATE w_logs;
```

Clear jobs:
```
TRUNCATE w_runbooks_jobs;
TRUNCATE w_runbooks_jobs_params;
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
