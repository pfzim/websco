# Initialize variables to store error and warning counts
[int] $errors = ''  # Number of errors (integer)
[int] $warnings = ''  # Number of warnings (integer)

# Initialize a variable to store a detailed message
$message = @'

'@

# Check if there are any errors or warnings
if(($errors + $warnings) -ne 0)
{
	# If errors or warnings are present, throw an exception with the detailed message
	throw New-Object System.Exception('Message: {0}' -f $message)
}
