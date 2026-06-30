import pytest
from unittest.mock import patch, MagicMock
import sys
import os

# Ensure the root directory is in the python path to import update_wiki
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), '..')))

from update_wiki import run_cmd

def test_run_cmd():
    with patch('subprocess.run') as mock_run:
        mock_res = MagicMock()
        mock_res.stdout = "mock_stdout"
        mock_res.stderr = "mock_stderr"
        mock_run.return_value = mock_res

        stdout, stderr = run_cmd(["echo", "test"])

        mock_run.assert_called_once_with(["echo", "test"], shell=False, capture_output=True, text=True)
        assert stdout == "mock_stdout"
        assert stderr == "mock_stderr"
