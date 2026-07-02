import mysql.connector
import json
import os

db_config = {
    'host': 'localhost',
    'user': 'acore',
    'password': 'acore',
    'charset': 'utf8mb4'
}

class DateTimeEncoder(json.JSONEncoder):
    def default(self, obj):
        import datetime
        if isinstance(obj, (datetime.date, datetime.datetime)):
            return obj.isoformat()
        return super().default(obj)

def export_data():
    conn = mysql.connector.connect(**db_config)
    cursor = conn.cursor(dictionary=True)
    
    # 1. Fetch Game Events
    print("Fetching game events...")
    cursor.execute("USE acore_world")
    cursor.execute("""
        SELECT eventEntry AS id, description AS name
        FROM game_event
        WHERE description IS NOT NULL AND description != '' AND eventEntry > 0
        ORDER BY eventEntry ASC
    """)
    events = cursor.fetchall()
    
    # 2. Fetch Ollama Personalities
    print("Fetching Ollama personalities...")
    cursor.execute("USE acore_characters")
    cursor.execute("SELECT `key`, prompt FROM mod_ollama_chat_personality_templates ORDER BY `key` ASC")
    personalities = cursor.fetchall()
    
    # 3. Fetch Characters List
    print("Fetching characters list...")
    cursor.execute("SELECT guid, account, name, race, class, level, gender FROM characters ORDER BY name ASC")
    characters = cursor.fetchall()
    
    # Identify real players (account >= 100)
    real_player_guids = [char['guid'] for char in characters if char['account'] >= 100]
    
    # Remove account field from public character list for privacy
    for char in characters:
        char.pop('account', None)

    # 3.5. Fetch active auctions
    print("Fetching active auctions...")
    cursor.execute("""
        SELECT 
            ah.id AS auction_id,
            it.name,
            it.Quality AS quality,
            it.RequiredLevel AS required_level,
            it.ItemLevel AS item_level,
            it.class,
            it.subclass,
            ii.count AS quantity,
            ah.buyoutprice AS buyout,
            ah.startbid AS start_bid,
            ah.lastbid AS last_bid,
            ah.time AS time_left,
            c.name AS owner_name
        FROM acore_characters.auctionhouse ah
        JOIN acore_characters.item_instance ii ON ah.itemguid = ii.guid
        JOIN acore_world.item_template it ON ii.itemEntry = it.entry
        JOIN acore_characters.characters c ON ah.itemowner = c.guid
        ORDER BY ah.id DESC
    """)
    auctions = cursor.fetchall()
    
    # 3.6. Fetch human accounts and characters mapping
    print("Fetching human accounts and characters...")
    cursor.execute("""
        SELECT 
            a.id AS account_id,
            a.username,
            c.guid,
            c.name,
            c.race,
            c.class,
            c.level,
            c.gender
        FROM acore_characters.characters c
        JOIN acore_auth.account a ON c.account = a.id
        WHERE a.id >= 100 
          AND a.username NOT LIKE 'RNDBOT%'
          AND a.username NOT LIKE 'BOT_%'
        ORDER BY a.username ASC, c.level DESC, c.name ASC
    """)
    human_characters = cursor.fetchall()
    
    # 4. Fetch details for real players
    biographies = {}
    kills = {}
    chats = {}
    
    for guid in real_player_guids:
        print(f"Fetching details for real player GUID {guid}...")
        
        # Biography
        cursor.execute("""
            SELECT timestamp, event_type AS type, event_details AS details 
            FROM custom_autobiography 
            WHERE character_guid = %s 
            ORDER BY timestamp DESC LIMIT 200
        """, (guid,))
        biographies[guid] = cursor.fetchall()
        
        # Kills
        cursor.execute("""
            SELECT creature_name AS name, kill_count AS count 
            FROM custom_kill_tracker 
            WHERE character_guid = %s 
            ORDER BY count DESC LIMIT 100
        """, (guid,))
        kills[guid] = cursor.fetchall()
        
        # Chats (exclude whispering secret channels if any, keep general/party/guild)
        cursor.execute("""
            SELECT timestamp, chat_type AS type, 
                   CONCAT(IF(receiver_name IS NOT NULL, CONCAT('To ', receiver_name, ': '), ''), message) AS details 
            FROM custom_chat_log 
            WHERE sender_guid = %s 
            ORDER BY timestamp DESC LIMIT 200
        """, (guid,))
        chats[guid] = cursor.fetchall()
        
    cursor.close()
    conn.close()
    
    # Build final structure
    wiki_data = {
        'events': events,
        'personalities': personalities,
        'characters': characters,
        'auctions': auctions,
        'human_characters': human_characters,
        'biographies': biographies,
        'kills': kills,
        'chats': chats
    }
    
    # Save to JSON
    output_path = '/home/coyofroyo/azeroth-server/bin/wiki_data.json'
    with open(output_path, 'w', encoding='utf-8') as f:
        json.dump(wiki_data, f, ensure_ascii=False, indent=2, cls=DateTimeEncoder)
    print(f"Successfully exported wiki data to {output_path}!")

if __name__ == '__main__':
    export_data()
