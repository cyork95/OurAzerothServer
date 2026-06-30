import subprocess
import os

def test_php_soap_logic():
    """
    Runs the PHP-based test script for the SOAP logic in admin_index.php.
    This ensures we use a proper PHP test script for PHP code while still
    integrating with the pytest runner used for the project.
    """
    php_script_path = os.path.join(os.path.dirname(__file__), 'test_admin_soap.php')
    result = subprocess.run(['php', php_script_path], capture_output=True, text=True)

    # Check if the PHP test script exited successfully
    assert result.returncode == 0, f"PHP tests failed:\n{result.stdout}\n{result.stderr}"
