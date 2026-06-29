import json
import os
import pytest

def test_wiki_data_schema():
    filepath = "wiki_data.json"
    assert os.path.exists(filepath), "wiki_data.json does not exist"

    with open(filepath, "r") as f:
        data = json.load(f)

    expected_keys = {"events", "personalities", "characters", "biographies", "kills", "chats"}
    assert set(data.keys()) == expected_keys, f"Missing or extra keys in wiki_data.json. Expected {expected_keys}, got {set(data.keys())}"

    # Validate Events
    assert isinstance(data["events"], list)
    for event in data["events"]:
        assert "id" in event
        assert "name" in event

    # Validate Personalities
    assert isinstance(data["personalities"], list)
    for personality in data["personalities"]:
        assert "key" in personality
        assert "prompt" in personality

    # Validate Characters
    assert isinstance(data["characters"], list)
    for char in data["characters"]:
        assert "guid" in char
        assert "name" in char
        assert "race" in char
        assert "class" in char
        assert "level" in char
        assert "gender" in char

    # Validate Biographies, Kills, Chats (Dicts)
    for key in ["biographies", "kills", "chats"]:
        assert isinstance(data[key], dict)
        for guid, entries in data[key].items():
            assert isinstance(entries, list)
            # Basic entry check
            if entries and key in ["biographies", "chats"]:
                assert "timestamp" in entries[0]
                assert "type" in entries[0]
                assert "details" in entries[0]
            elif entries and key == "kills":
                assert "name" in entries[0]
                assert "count" in entries[0]
