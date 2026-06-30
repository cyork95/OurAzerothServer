import pytest
from unittest.mock import patch, MagicMock
import sys
import os

# Ensure the root directory is in the python path to import update_wiki
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), '..')))

from update_wiki import run_cmd, update_wiki

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

@patch('update_wiki.get_config')
@patch('update_wiki.run_cmd')
@patch('builtins.print')
def test_update_wiki_success(mock_print, mock_run_cmd, mock_get_config):
    mock_get_config.return_value = ("key", "ip", "user")

    mock_run_cmd.side_effect = [
        ("Successfully exported\nRemote: Success", ""),
        ("", ""),
        ("", ""),
        ("commit output", ""),
        ("push output", "")
    ]

    update_wiki()

    from unittest.mock import call

    assert mock_run_cmd.call_count == 5
    mock_run_cmd.assert_has_calls([
        call(['ssh', '-i', 'key', 'user@ip', 'python3 /home/coyofroyo/azeroth-server/bin/wiki_exporter.py']),
        call('scp -i "key" user@ip:/home/coyofroyo/azeroth-server/bin/wiki_data.json ./wiki_data.json'),
        call(['git', 'add', 'index.html', 'wiki_data.json']),
        call(['git', 'commit', '-m', 'Auto-update server wiki data']),
        call(['git', 'push'])
    ], any_order=False)

    mock_print.assert_any_call("Done! Wiki has been updated and pushed successfully.")

@patch('update_wiki.get_config')
@patch('update_wiki.run_cmd')
def test_update_wiki_missing_config(mock_run_cmd, mock_get_config):
    mock_get_config.return_value = (None, None, None)

    update_wiki()

    mock_run_cmd.assert_not_called()

@patch('update_wiki.get_config')
@patch('update_wiki.run_cmd')
def test_update_wiki_exporter_error(mock_run_cmd, mock_get_config):
    mock_get_config.return_value = ("key", "ip", "user")

    mock_run_cmd.return_value = ("Some error occurred", "No success message here")

    update_wiki()

    assert mock_run_cmd.call_count == 1
