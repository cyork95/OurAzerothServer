import time
import requests
from concurrent.futures import ThreadPoolExecutor

GITHUB_REPO = "cyork95/OurAzerothServer"
GITHUB_TOKEN = "" # we don't have token but we can mock

class MockRes:
    status_code = 200
    def json(self): return {"state": "closed"}

def mock_get(*args, **kwargs):
    time.sleep(0.1) # simulate network
    return MockRes()

requests.get = mock_get

def test_sync(n):
    start = time.time()
    for i in range(n):
        res = requests.get(f"url/{i}")
        res.json()
    print("Sync time:", time.time() - start)

def test_async(n):
    start = time.time()
    with ThreadPoolExecutor(max_workers=10) as executor:
        futures = [executor.submit(requests.get, f"url/{i}") for i in range(n)]
        for f in futures:
            f.result().json()
    print("Async time:", time.time() - start)

test_sync(20)
test_async(20)
