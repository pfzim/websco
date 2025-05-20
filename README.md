# WebSCO - alternative web interface for System Center Orchestrator and AWX (Ansible)

[:ru:](#description-ru) [:us:](#description)  

[![Watch the video](/docs/screenshots/main.png)](https://github.com/user-attachments/assets/b2dc551a-db4f-4de6-9e3a-b30fe4bec4a1)

Web Interface for Running Orchestrator Runbooks & AWX Playbooks with Access Control
A unified automation web portal that enables HelpDesk teams and other users to easily execute Microsoft Orchestrator runbooks and AWX/Ansible playbooks through an intuitive interface with dropdown menus, checkboxes, and input forms.

Key Features:  
 ✅ Granular Access Control – Permissions are assigned by folder (like in Orchestrator), allowing task delegation to different teams (HelpDesk, DevOps, admins).  
 ✅ User-Friendly UI – Predefined input fields (server selection, user lists, options) instead of manual command entry.  
 ✅ Multi-Platform Automation – Supports both Orchestrator Runbooks and AWX Playbooks from a single dashboard.  
 ✅ Structured Organization – Playbooks and runbooks are organized in folders for easy navigation.  

## System requirements
- Apache or Nginx
- MariaDB (MySQL)
- PHP
- Microsoft System Center Orchestrator and/or Ansible AWX
- Active Directory (optional)
- memcached (optional)
- Kerberos (optional)
- mod_rewrite (optional)

## Installation

```
sudo apt-get install mariadb-server
sudo apt-get install php php-mysql php-ldap php-curl php-xml php-yaml
sudo apt-get install memcached php-memcached
sudo apt-get install nginx php-fpm
#sudo apt-get install apache2 libapache2-mod-php
```

Only when using Kerberos:
```
sudo apt-get install ldap-utils libsasl2-modules-gssapi-mit libsasl2-2 gss-ntlmssp krb5 krb5-clients krb5-user 
#sudo apt-get install libapache2-mod-auth-kerb libapache2-mod-auth-gssapi
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
![Runbook input form example](/docs/screenshots/runbook_classic_en.png "Runbook input form example")

### Same form in WebSCO
![Runbook input form example](/docs/screenshots/runbook1_en.png "Runbook input form example")

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
 - In the description, you can use the [wiki]your_url_here[/wiki] tag to add an arbitrary link to the instruction
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
 - g - field with autocomplete for entering group SamAccountName (query LDAP) (group)
 - f - checkboxes switches (flags)
 - w - hidden field with login who start runbook in WebSCO (who run)
 - u - field for select file, the file will be transferred as a base64 string (upload)
 - r - the flag means that the parameter is required

You can also use * (asterisk) before the slash to indicate a required parameter.

For a list and check-boxes, in addition, before the slash in brackets, you need
to list the parameters separated by commas. For example:

1. Select the type of access (admin, guest)*/l

This field will turn into a drop-down list with two values `admin` and `guest` and
will be required.

2. Select the protocol (HTTP, HTTPS)/rf

In this case, two HTTP and HTTPS checkboxes will be displayed, and at least one
must be checked. The flag r is specified (analogous to the asterisk from the
example above). The selected HTTP will correspond to set bit 1, and HTTPS,
respectively, to bit 2. Check-boxes will be difficult to reproduce in the
standard console if, for some reason, you have to run the runbook from it.

After completing the configuration, you need to load the list of runbooks into
the database by running Sync. And download every time after adding new and
changing existing runbooks (do not forget about the [Orchestrator glitch](https://wiki.it-kb.ru/microsoft-system-center/orchestrator/new-orchestrator-runbook-not-shown-on-scorch-orchestration-console-and-web-rest-api), when
the user does not immediately see the new runbook and needs to clear the cache).
Loading Jobs is not necessary and takes a long time (I have ~ 20,000 jobs loaded
for about 30 minutes), if they have already started, then you need to wait for
the download to finish without interrupting or restarting it.

### Access rights management

**Local users are administrators** and have access to all sections, can run all
runbooks and manage access rights.

Domain users who have access to the Root level with Execute rights are
administrators of access rights.

Local Administrators and Users with access to Root Level with Execute rights
see hidden folders.

## Description (RU)

### Так окно запуска выглядит в стандатной консоли Оркестратора
![Runbook input form example](/docs/screenshots/runbook_classic.png "Runbook input form example")

### А вот так выглядит в тот же ранбук в WebSCO
![Runbook input form example](/docs/screenshots/runbook1.png "Runbook input form example")

Плюсы по сравнению со стандартной консолью:
 - Форма для запуска ранбуков поддерживает выпадающие списки, чек-боксы, поля для ввода дат и цифр
 - Валидация обязательных полей (с незаполненным обязательным параметром ранбук не запустит)
 - Параметры выводятся в отсортированном порядке, а не пляшут как попало (достаточно пронумеровать их)
 - Перезапуск ранбуков с ранее введенными параметрами
 - Экранирование одинарной кавычки при передаче параметров в ранбук (чтобы нельзя было сделать инъекцию в код ранбука)
 - Описание ранбука отображается в окне запуска
 - В описании ранбука можно использовать тэг [wiki]you_url_here[/wiki] для добавления произвольной ссылки на инструкцию
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
 - c - поле с автодополнением SamAccountName группы (group)
 - m - поле с автодополнением e-mail (mail)
 - u - поле с для загрузки файлов, файл будет передан в ранбук как base64 строка (upload)
 - w - скрытое поле будет содержать логин пользователя, который запусил ранбук в WebSCO (who run)
 - r - флаг означает, что параметр обязателен для заполнения (required)

Еще можно использовать * (звёздочку) перед слэшем для обозначения
обязательного параметра.

Для списка и чек-боксов дополнительно перед слэшем в скобках нужно перечислить
параметры через запятую. Например:

1. Выберите тип доступа (admin, guest)*/l

Такое поле превратится в выпадающий список с двумя значениями `admin` и `guest`
и будет обязательным для заполнения.

2. Выберите протокол (HTTP, HTTPS)/rf

В данном случае будет отображено два чек-бокса HTTP и HTTPS и хотя бы один
должен будет отмечен т.к. указан флаг r (аналог звездочки из примера выше).
Выбранный HTTP будет соответствовать взведенному биту 1, а HTTPS
соответственно биту 2. Чек-боксы будет сложно воспроизвести в стандартной
консоли, если по каким-либо причинам придется запускать ранбук из неё.

После завершения настройки нужно загрузить список ранбуков в БД выполнив Sync.
И выполнять загрузку каждый раз после добавления новых и изменения
существующих ранбуков (не забываем про [глюк Оркестратора](https://wiki.it-kb.ru/microsoft-system-center/orchestrator/new-orchestrator-runbook-not-shown-on-scorch-orchestration-console-and-web-rest-api), когда пользователь
не сразу видит новый ранбук и нужно очищать кэш). Загрузка Job'ов не обязательна
и занимает продолжительное время (~20 000 джобов загружаются около 30 минут),
если уж запустили, то надо дождаться окончания загрузки не прерывая и не
перезапуская её.

### Управление правами доступа

**Локальные пользователи являются администраторами** и имеют доступ во все разделы,
могут запускать все ранбуки и управлять правами доступа.

Пользователи домена имеющие доступ к Корневому уровню с правами Выполнения
являются администраторами прав доступ.

Локальные администраторы и пользователи имеющие доступ к Корневому уровню
с правами Выполнения видят скрытые папки.

## Technical information

The `AWX_DONT_PARSE_EXTRA_VARS` parameter disables parsing of variables from the extra_vars parameter.
Only the variable from the Survey will be loaded. If a variable with the same name is present
in both `extra_vars` and the Survey, the variable from the Survey will be loaded into the launch form.
The variable with the name `who_run` will not be displayed in the launch form and the name of the user
who performed the launch will be passed to it at launch. Similar to the `/w` switch in Orchestrator.


Bit `flags` in table `runbooks`

| Bits   | Name                | Description                               |
|--------|---------------------|-------------------------------------------|
| 0x0001 | RBF_DELETED         | Deleted                                   |
| 0x0002 | RBF_HIDED           | Hide from list                            |
| 0x0004 | RBF_TYPE_CUSTOM     | Custom script                             |
| 0x0008 | RBF_TYPE_SCO        | Orchestrator 2016 or earlier runbook      |
| 0x0010 | RBF_TYPE_SCO2022    | Orchestrator 2022 runbook                 |
| 0x0020 | RBF_TYPE_ANSIBLE    | Ansible playbook                          |
| 0x0040 | RBF_TYPE_ANSIBLE_WF | Ansible workflow                          |

Clearing Kerberos authorization tickets after adding a WebSCO service account to an AD group:
```
kdestroy -A -c /tmp/krb5cc_<user_id>
```

Sometime required clear AuthorizationCache after creating new Runbook:
```
USE Orchestrator 
TRUNCATE TABLE [Microsoft.SystemCenter.Orchestrator.Internal].AuthorizationCache
```

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

Add custom script:
```
INSERT INTO w_runbooks (`folder_id`, `guid`,     `name`,             `description`,          `flags`)
VALUES                 (50,          'myscript', 'My custom script', 'This is just a script', 0x0004);
```
where `50` - is a folder ID  
`myscript` - is a directory name in a `custom` folder (`custom/myscript/main.php`)

Jobs run history:
```
SELECT *
FROM websco.w_logs AS l
LEFT JOIN websco.w_users AS u
	ON u.id = l.uid
WHERE l.operation LIKE 'Run:%'
ORDER BY l.`date` DESC;
```

Top runbooks:
```
SELECT r.`name`, COUNT(rj.`id`) AS `run_count`
FROM websco.w_runbooks AS r
LEFT JOIN websco.w_runbooks_jobs AS rj
  ON rj.`pid` = r.`id`
GROUP BY r.`id`
ORDER BY `run_count` DESC;
```

Top users:
```
SELECT u.`login`, COUNT(l.`id`) `run_count`
FROM websco.w_users AS u
LEFT JOIN websco.w_logs AS l
	ON l.uid = u.id AND l.operation LIKE 'Run:%'
GROUP BY u.`id`
ORDER BY `run_count` DESC;
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

Example Nginx config:
```
        location /websco/ {
                index index.php index.html index.htm;
          if (!-e $request_filename){
            rewrite ^/websco/(.*)$ /websco/websco.php?path=$1 last;
          }
        }
```

Convert PFX certificate:

```
openssl pkcs12 -in websco.pfx -clcerts -nokeys -out /etc/ssl/certs/websco.cer
openssl pkcs12 -in websco.pfx -nocerts -nodes -out /etc/ssl/private/websco.key
```

Generate self-signed certificate:

```
sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout /etc/ssl/private/websco.key -out /etc/ssl/certs/websco.cer
```

[What’s the Maximum Size of Parameters?](https://techcommunity.microsoft.com/t5/system-center-blog/orchestrator-quick-tip-what-8217-s-the-maximum-size-of/ba-p/345501)

Increase IIS request limit (HTTP error 413): system.webServer/serverRuntime/uploadReadAheadSize = 10485760  
C:\Program Files (x86)\Microsoft System Center\Orchestrator\Web Service\web.config
```
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
    <system.web>
        <httpRuntime maxRequestLength="10485760" />
    </system.web>
    <location path="Orchestrator2012">
        <system.web>
            <httpRuntime maxRequestLength="10485760" />
        </system.web>
        <system.webServer>
            <httpErrors errorMode="DetailedLocalOnly" />
        </system.webServer>
    </location>
</configuration>
```

C:\Program Files (x86)\Microsoft System Center\Orchestrator\Web Service\Orchestrator2012\web.config
```
<?xml version="1.0" encoding="utf-8"?>
<configuration>
	<system.web>
		<httpRuntime maxRequestLength="10485760" maxQueryStringLength="5000" />
		...
	</system.web>
	<system.serviceModel>
		<bindings>
		   <webHttpBinding>
			<binding 
			  maxReceivedMessageSize="10485760" >
			</binding>  
		   </webHttpBinding>
		</bindings>
		...
	</system.serviceModel>
	...
```

In version 2016 and below of SC Orchestrartor: [Runbooks are executed in 32bit environment and with Powershell Version 2.0](https://learn.microsoft.com/en-us/answers/questions/1349344/orchestrator-powershell) per default.
You can force to use the latest Version in registry with with OnlyUseLatestCLR in path HKLM\SOFTWARE\Wow6432Node\Microsoft\.NETFramework.

```
reg add "HKLM\SOFTWARE\WOW6432Node\Microsoft\.NETFramework" /f /v OnlyUseLatestCLR /t REG_DWORD /d 1
```

[Apache config example](/examples/apache-21-websco-ssl.conf)  
[Nginx config example](/examples/nginx-01-websco.conf)  
[Backup script example](/examples/backup.sh)  
[logrotate config example](/examples/websco)  
[crontab config example](/examples/crontab)  
[inc.config.php and krb5.conf example](/examples/inc.config.php)  
[Best Practice Template](https://automys.com/library/asset/powershell-system-center-orchestrator-practice-template)  
[Runbook template](/examples/_TEMPLATE_Runbook.ps1)  
