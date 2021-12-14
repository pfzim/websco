param (
	$Name = $null,
	$ComputerName = $null
)

# Все входящие параметры указываем в $rb_input

$rb_input = @{
	name = $Name

	ps_server = $ComputerName
	auth_type = 'Default' # Negotiate

	debug_pref = 'SilentlyContinue'  # Change to Continue for show debug messages
}

$DebugPreference = $rb_input.debug_pref
$ErrorActionPreference = 'Stop'

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

		if([string]::IsNullOrWhiteSpace($rb_input.ps_server))
		{
			$result.errors++; $result.messages += 'Error: Undefined computer name';
		}

		if([string]::IsNullOrWhiteSpace($rb_input.name))
		{
			$result.errors++; $result.messages += 'Error: Undefined name';
		}

		if($result.errors -gt 0)
		{
			return $result
		}
		
		# Подключаемся к менеджмент серверу

		#$result = Invoke-Command -ComputerName $rb_input.ps_server -ConfigurationName WebSCO -Authentication Negotiate -ArgumentList @($rb_input) -ScriptBlock {
		$result = Invoke-Command -ComputerName $rb_input.ps_server -Authentication $rb_input.auth_type -ArgumentList @($rb_input) -ScriptBlock {
			param(
				$rb_input
			)
			$DebugPreference = $rb_input.debug_pref
			$ErrorActionPreference = 'Stop'
			try
			{
				$result = @{ errors = 0; warnings = 0; messages = @() }

				$result['data'] = @{
					answer = 'Hello, {0}! My name is {1}.' -f $rb_input.name, $ENV:COMPUTERNAME
				}
				

				return $result
			}
			catch
			{
				$result.errors++; $result.messages += ('ERROR[{0},{1}]: {2}' -f $_.InvocationInfo.ScriptLineNumber, $_.InvocationInfo.OffsetInLine, $_.Exception.Message);
				return $result
			}
		}

		return $result
	}
	catch
	{
		$result.errors++; $result.messages += ('ERROR[{0},{1}]: {2}' -f $_.InvocationInfo.ScriptLineNumber, $_.InvocationInfo.OffsetInLine, $_.Exception.Message);
		return $result
	}
}

# Выполняем ранбук

$result = main -rb_input $rb_input

$result['message'] = $result.messages -Join "`r`n"

# Возврат значений

ConvertTo-Json -InputObject $result -Depth 99

Write-Debug ('Errors: {0}, Warnings: {1}, Messages: {2}' -f $result.errors, $result.warnings, $result.message)
