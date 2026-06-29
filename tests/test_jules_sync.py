import os
import sys
import pytest
from unittest.mock import MagicMock, patch

# Add the scripts directory to the path so we can import jules_sync
sys.path.append(os.path.join(os.path.dirname(__file__), "..", "scripts"))

# Mock environment variable before importing
with patch.dict(os.environ, {"GITHUB_TOKEN": "fake_token"}):
    import jules_sync

def test_github_issue_payload():
    title = "Test Bug"
    body = "Test Details"
    labels = ["jules", "bug"]

    with patch("requests.post") as mock_post:
        mock_res = MagicMock()
        mock_res.status_code = 201
        mock_res.json.return_value = {"number": 123}
        mock_post.return_value = mock_res

        issue_num = jules_sync.create_github_issue(title, body, labels)

        assert issue_num == 123
        mock_post.assert_called_once()
        args, kwargs = mock_post.call_args
        assert kwargs["json"]["title"] == title
        assert kwargs["json"]["labels"] == labels

@patch("jules_sync.connect_to_sheet")
@patch("jules_sync.create_github_issue")
def test_sync_tracker_logic(mock_create_issue, mock_connect):
    # Mock Sheet Data
    mock_sheet = MagicMock()
    mock_connect.return_value = mock_sheet

    mock_sheet.get_all_records.return_value = [
        {
            "Title": "New Bug",
            "Description": "It crashes",
            "Type": "Bug",
            "Priority": "High",
            "Status": "Pending",
            "GitHub_Issue_ID": ""
        }
    ]

    mock_create_issue.return_value = 456

    jules_sync.sync_tracker()

    # Check if create_github_issue was called for the pending bug
    mock_create_issue.assert_called_once()
    assert mock_create_issue.call_args[0][0] == "New Bug"

    # Check if sheet was updated
    # update_cell(row, col, value)
    # row 2 (idx 0 + 2), col 6 (Status), col 7 (Issue ID)
    mock_sheet.update_cell.assert_any_call(2, 6, "In Progress")
    mock_sheet.update_cell.assert_any_call(2, 7, "456")

@patch("jules_sync.connect_to_sheet")
@patch("requests.get")
def test_sync_tracker_closure(mock_get, mock_connect):
    mock_sheet = MagicMock()
    mock_connect.return_value = mock_sheet

    mock_sheet.get_all_records.return_value = [
        {
            "Title": "Old Bug",
            "Description": "Fixed already",
            "Type": "Bug",
            "Priority": "Low",
            "Status": "In Progress",
            "GitHub_Issue_ID": "789"
        }
    ]

    # Mock GitHub response for a closed issue
    mock_res = MagicMock()
    mock_res.status_code = 200
    mock_res.json.return_value = {"state": "closed"}
    mock_get.return_value = mock_res

    jules_sync.sync_tracker()

    # Status should be updated to 'Fixed'
    mock_sheet.update_cell.assert_called_with(2, 6, "Fixed")
