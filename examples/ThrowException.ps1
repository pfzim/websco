[int] $errors = ''
[int] $warnings = ''
$message = @'

'@

if(($errors + $warnings) -ne 0)
{
	throw New-Object System.Exception('Message: {0}' -f $message)
}
