# Set the error handling policy: stop execution on error
$ErrorActionPreference = 'Stop'

# Define a hashtable with input parameters for running the runbook
$rb_input = @{
    runbook_file = '{git_local_folder_path}/runbooks/MyRunbook.ps1'  # Path to the runbook file

    example_param1 = ''  # Example parameter 1
    example_param2 = ''  # Example parameter 2

    ps_server = 'localhost'  # PowerShell server
    ps_user = ''  # User for server connection
    ps_passwd = ''  # Password for server connection

    runbook_server = ''  # Runbook server
    runbook_proc_id = ''  # Runbook process ID
    who_start_runbook = ''  # User who started the runbook

    debug_pref = 'SilentlyContinue'  # Debugging detail level
}

# try-catch block for error handling
try
{
    # Set the debugging detail level
    $DebugPreference = $rb_input.debug_pref

    # Run the runbook, passing input parameters
    $runbook_result_0x4D85EFC5 = & $rb_input.runbook_file -rb_input $rb_input

    # Check if the runbook result is a hashtable and contains 'errors', 'warnings', 'messages' keys
    if($runbook_result_0x4D85EFC5 -and $runbook_result_0x4D85EFC5.GetType().Name -eq 'Hashtable' -and $runbook_result_0x4D85EFC5.ContainsKey('errors') -and $runbook_result_0x4D85EFC5.ContainsKey('warnings') -and $runbook_result_0x4D85EFC5.ContainsKey('messages'))
	{
        # Iterate through the hashtable elements
        $runbook_result_0x4D85EFC5.GetEnumerator() | ForEach-Object {
            # If the key is 'messages' and its value is an array, join the array elements into a string
            if($_.Name -eq 'messages' -and $_.Value.GetType().Name -eq 'Object[]')
			{
                $message = $_.Value -join "`r`n"
            }
			else
			{
                # Otherwise, save the value to a variable named after the key
                Set-Variable -Name $_.Name -Value $_.Value
            }
        }
    }
	else
	{
        # If the result does not match the expected format, throw an exception
        throw New-Object System.Exception('Invalid output. Expected Hashtable.')
    }
}
catch
{
    # In case of an error, save the error code, warnings, and error message
    $errors = 999
    $warnings = 0
    $message = "ERROR[{0},{1}]: {2}`r`n{3}`r`nrunbook output:`r`n{4}" -f $_.InvocationInfo.ScriptLineNumber, $_.InvocationInfo.OffsetInLine, $_.Exception.Message, $_.InvocationInfo.PositionMessage, ($runbook_result_0x4D85EFC5 | Out-String)
}

# Check if there are any errors or warnings
# if (($errors + $warnings) -ne 0)
# {
    # If there are, throw an exception with error and warning details
    # throw New-Object System.Exception("Errors: {0}`r`nWarnings: {1}`r`nMessage:`r`n{2}" -f $errors, $warnings, $message)
# }

# Output debug information about errors, warnings, and messages
# Write-Debug ('Errors: {0}, Warnings: {1}, Messages: {2}' -f $errors, $warnings, $message)