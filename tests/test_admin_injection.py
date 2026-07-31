import subprocess
import os

def test_admin_bot_add_injection():
    php_script_path = os.path.join(os.path.dirname(__file__), 'test_admin_injection.php')
    result = subprocess.run(['php', php_script_path], capture_output=True, text=True)
    assert result.returncode == 0, f"PHP tests failed:\n{result.stdout}\n{result.stderr}"
