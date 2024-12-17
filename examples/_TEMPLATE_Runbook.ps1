<# Runbook template

	На вход ранбук принимает параметры:

		errors            - количество возникших ошибок (из результата запуска предыдущего ранбука)
		warnings          - количество возникших предупреждений (из результата запуска предыдущего ранбука)
		message           - текстовое описание ошибок и предупреждений (из результата запуска предыдущего ранбука)

	На выходе ранбук возвращает следующие параметры:

		errors   - количество возникших ошибок
		warnings - количество возникших предупреждений
		message  - текстовое описание ошибок и предупреждений

#>

$ErrorActionPreference = 'Stop'

#. c:\scripts\settings\config.ps1
#$config = Get-Content -Path {git_path}\configs\orchestrator.json -Raw | ConvertFrom-Json

# Все входящие параметры указываем в $rb_input,
# чтобы в основном блоке не было никаких внешних переменных.
# Тем самым ранбук становится системонезависимым, универсальным и переносимым.

$rb_input = @{
	example_param1 = ''
	example_param2 = ''
	example_mail_to = ''
	example_mail_to_admin = 'admin@example.org'
	ps_server = 'localhost'
	ps_user = ''
	ps_passwd = ''
	who_start_runbook = ''
	#smtp_server = $config.smtp_server
	#smtp_from = $config.smtp_from
	
	debug_pref = 'SilentlyContinue'  # Change to Continue for show debug messages
}

# Если ранбуки запускаются цепочкой, то результат выполнения предыдущего
# ранбука указываем здесь

$result = @{
	errors = [int] 0
	warnings = [int] 0
	messages = @(@'

'@)
}

$DebugPreference = $rb_input.debug_pref

# Основной блок ранбука

function main($rb_input)
{
	trap
	{
		return @{ errors = 0; warnings = 0; messages = @("Critical error[{0},{1}]: {2}`r`n`r`nProcess interrupted!`r`n" -f $_.InvocationInfo.ScriptLineNumber, $_.InvocationInfo.OffsetInLine, $_.Exception.Message); }
	}

	try
	{
		$result = @{ errors = 0; warnings = 0; messages = @() }

		# Проверка корректности заполнения полей

		if([string]::IsNullOrWhiteSpace($rb_input.param1))
		{
			$result.errors++; $result.messages += 'Ошибка: Не заполнено поле param1';
		}

		if($result.errors -gt 0)
		{
			return $result
		}

		$ps_creds = (New-Object System.Management.Automation.PSCredential ($rb_input.ps_user, (ConvertTo-SecureString $rb_input.ps_passwd -AsPlainText -Force)))

		$session = New-PSSession -ComputerName $rb_input.ps_server -Credential $ps_creds -Authentication Credssp
		$result = Invoke-Command -Session $session -ArgumentList @($rb_input) -ScriptBlock {
			param(
				$rb_input
			)
			$DebugPreference = $rb_input.debug_pref
			$ErrorActionPreference = 'Stop'
			try
			{
				$result = @{ errors = 0; warnings = 0; messages = @() }

				# *** PUT YOU CODE HERE ***

				Write-Debug 'PUT YOU CODE HERE'

				return $result
			}
			catch
			{
				$result.errors++; $result.messages += ('ERROR[{0},{1}]: {2}' -f $_.InvocationInfo.ScriptLineNumber, $_.InvocationInfo.OffsetInLine, $_.Exception.Message);
				return $result
			}
		}

		Remove-PSSession -Session $session

		return $result
	}
	catch
	{
		$result.errors++; $result.messages += ('ERROR[{0},{1}]: {2}' -f $_.InvocationInfo.ScriptLineNumber, $_.InvocationInfo.OffsetInLine, $_.Exception.Message);
		return $result
	}
}


# Выполняем ранбук, только если предыдущий завершился без ошибок

if($result.errors -eq 0)
{
	$output = main -rb_input $rb_input

	# Объединяем результат с предыдущим ранбуком

	$result.errors += $output.errors
	$result.warnings += $output.warnings
	$result.messages += $output.messages

	<# Return custom results
	if($output.errors -eq 0)
	{
		$data = $output.data
	}
	#>
}

# Код выхода для обратной совместимости

$exit_code = 0
if($result.errors -gt 0 -or $result.warnings -gt 0)
{
	$exit_code = 1
}

# Возврат значений

$errors = $result.errors
$warnings = $result.warnings
$message = $result.messages -join "`r`n"

Write-Debug ('Errors: {0}, Warnings: {1}, Messages: {2}' -f $errors, $warnings, $message)
