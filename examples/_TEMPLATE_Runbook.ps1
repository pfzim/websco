<# 
Runbook Template

Description:
This script serves as a template for creating PowerShell runbooks. It is
designed to handle input parameters, execute tasks (locally or remotely), and
return results in a standardized format. The runbook can be chained with other
runbooks, allowing for complex automation workflows.

Input Parameters:
	- errors:    Number of errors from the previous runbook execution (if chained).
	- warnings:  Number of warnings from the previous runbook execution (if chained).
	- message:   Descriptive message about errors or warnings from the previous runbook execution.

Output Parameters:
	- errors:    Total number of errors after execution.
	- warnings:  Total number of warnings after execution.
	- message:   Descriptive message about errors or warnings after execution.
#>

# Stop script execution on errors for better error handling
$ErrorActionPreference = 'Stop'

# Load configuration settings (commented out for now)
#. c:\scripts\settings\config.ps1
#$config = Get-Content -Path {git_local_folder_path}\configs\orchestrator.json -Raw | ConvertFrom-Json

# Define input parameters in a hashtable for better organization and portability.
# This ensures the runbook is self-contained and does not rely on external variables.
$rb_input = @{
	example_param1 = ''  # Example parameter 1 (e.g., a file path or ID)
	example_param2 = ''  # Example parameter 2 (e.g., a configuration value)
	example_mail_to = ''  # Email recipient for notifications
	example_mail_to_admin = 'admin@example.org'  # Admin email for critical notifications

	ps_server = 'localhost'  # PowerShell remote server (default: localhost)
	ps_user = ''  # Username for remote server authentication
	ps_passwd = ''  # Password for remote server authentication

	who_start_runbook = ''  # User or system that initiated the runbook

	#smtp_server = $config.smtp_server  # SMTP server for email notifications (from config)
	#smtp_from = $config.smtp_from  # Sender email address (from config)
	
	debug_pref = 'SilentlyContinue'  # Debug preference: 'SilentlyContinue' (default) or 'Continue' for verbose output
}

# Initialize the result hashtable to store errors, warnings, and messages.
# This is used to track the outcome of the runbook execution.
$result = @{
	errors = [int] 0  # Total number of errors (default: 0)
	warnings = [int] 0  # Total number of warnings (default: 0)
	messages = @(@'
'@) # Array to store descriptive messages (default: empty)
}

# Set the debug preference based on the input parameter
$DebugPreference = $rb_input.debug_pref

<#
Main Function:
This function contains the core logic of the runbook. It performs the following tasks:
1. Validates input parameters.
2. Executes commands locally or remotely.
3. Handles errors and warnings.
4. Returns a standardized result hashtable.
#>
function main($rb_input)
{
	# Trap block to catch critical errors and ensure the process is interrupted gracefully.
	trap
	{
		return @{ errors = 0; warnings = 0; messages = @("Critical error[{0},{1}]: {2}`r`n`r`nProcess interrupted!`r`n" -f $_.InvocationInfo.ScriptLineNumber, $_.InvocationInfo.OffsetInLine, $_.Exception.Message); }
	}

	try
	{
		# Initialize the result hashtable for this runbook execution
		$result = @{ errors = 0; warnings = 0; messages = @() }

		# Validate input parameters
		if([string]::IsNullOrWhiteSpace($rb_input.param1))
		{
			$result.errors++; $result.messages += 'Error: Field param1 is empty';  # Add error message
		}

		# If there are errors, return the result immediately
		if($result.errors -gt 0)
		{
			return $result
		}

		# Create a PSCredential object for remote server authentication
		$ps_creds = (New-Object System.Management.Automation.PSCredential ($rb_input.ps_user, (ConvertTo-SecureString $rb_input.ps_passwd -AsPlainText -Force)))

		# Establish a remote PowerShell session
		$session = New-PSSession -ComputerName $rb_input.ps_server -Credential $ps_creds -Authentication Credssp

		# Execute commands on the remote server
		$result = Invoke-Command -Session $session -ArgumentList @($rb_input) -ScriptBlock {
			param(
				$rb_input  # Pass input parameters to the remote session
			)
			$DebugPreference = $rb_input.debug_pref  # Set debug preference
			$ErrorActionPreference = 'Stop'  # Stop on errors

			try
			{
				# Initialize the result hashtable for the remote execution
				$result = @{ errors = 0; warnings = 0; messages = @() }

				# *** PLACE YOUR CODE HERE ***
				# Add the main logic for the runbook in this section.

				Write-Debug 'PLACE YOUR CODE HERE'  # Debug message for placeholder

				return $result  # Return the result of the remote execution
			}
			catch
			{
				# Handle errors during remote execution
				$result.errors++; $result.messages += ('ERROR[{0},{1}]: {2}' -f $_.InvocationInfo.ScriptLineNumber, $_.InvocationInfo.OffsetInLine, $_.Exception.Message);
				return $result
			}
		}

		# Close the remote session
		Remove-PSSession -Session $session

		return $result  # Return the result of the main function
	}
	catch
	{
		# Handle errors during the main function execution
		$result.errors++; $result.messages += ('ERROR[{0},{1}]: {2}' -f $_.InvocationInfo.ScriptLineNumber, $_.InvocationInfo.OffsetInLine, $_.Exception.Message);
		return $result
	}
}

try
{
	# Execute the runbook only if the previous runbook completed without errors
	if($result.errors -eq 0)
	{
		# Run the main function and capture its output
		$output = main -rb_input $rb_input

		# Combine the results with the previous runbook execution
		$result.errors += $output.errors
		$result.warnings += $output.warnings
		$result.messages += $output.messages

		<# 
		Return custom results (optional)
		if($output.errors -eq 0)
		{
			$data = $output.data  # Example: Return additional data if no errors
		}
		#>
	}

	# Set the exit code for backward compatibility
	$exit_code = 0  # Default: Success
	if ($result.errors -gt 0 -or $result.warnings -gt 0)
	{
		$exit_code = 1  # Indicate errors or warnings
	}

	# Prepare the final output values
	$errors = $result.errors
	$warnings = $result.warnings
	$message = $result.messages -join "`r`n"  # Combine messages into a single string
}
catch
{
	# Handle unexpected errors during the script execution
	$errors = 999
	$warnings = 0
	$message = ("ERROR[{0},{1}]: {2}`r`nmain() output: {3}" -f $_.InvocationInfo.ScriptLineNumber, $_.InvocationInfo.OffsetInLine, $_.Exception.Message, ($output | Out-String))
}

# Output debug information for troubleshooting
Write-Debug ('Errors: {0}, Warnings: {1}, Messages: {2}' -f $errors, $warnings, $message)
