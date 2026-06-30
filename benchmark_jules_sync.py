import time
import requests
import gspread
from unittest.mock import Mock, patch
from scripts.jules_sync import sync_tracker

# Mock gspread and requests to test performance
def run_benchmark(optimized):
    with patch('scripts.jules_sync.connect_to_sheet') as mock_connect, \
         patch('scripts.jules_sync.requests.get') as mock_get, \
         patch('scripts.jules_sync.requests.post') as mock_post, \
         patch('scripts.jules_sync.GITHUB_TOKEN', 'mock_token'):

        mock_sheet = Mock()
        mock_connect.return_value = mock_sheet

        # Create 20 "in progress" issues
        rows = [
            {"Status": "in progress", "GitHub_Issue_ID": str(i)}
            for i in range(1, 21)
        ]
        mock_sheet.get_all_records.return_value = rows

        def fake_get(*args, **kwargs):
            time.sleep(0.1) # Simulate network delay
            res = Mock()
            res.status_code = 200
            res.json.return_value = {"state": "closed"}
            return res

        mock_get.side_effect = fake_get

        start = time.time()
        sync_tracker()
        end = time.time()
        return end - start

if __name__ == "__main__":
    duration = run_benchmark(False)
    print(f"Original execution time: {duration:.2f} seconds")
