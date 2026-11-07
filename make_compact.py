import json

with open(r"c:\F1 Stars\f1_stars\public\data\data.json", "r", encoding="utf-8") as f:
    data = json.load(f)

statements = []

# 1. PARTICIPANTS
statements.append("""CREATE TABLE IF NOT EXISTS participants (
    id INTEGER PRIMARY KEY,
    nick TEXT NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);""")

part_values = []
for p in data['participants']:
    part_values.append(f"({p['id']}, '{p['nick']}')")
statements.append(f"INSERT OR REPLACE INTO participants (id, nick) VALUES {', '.join(part_values)};")

# 2. DRIVERS
statements.append("""CREATE TABLE IF NOT EXISTS drivers (
    driver_number INTEGER PRIMARY KEY,
    full_name TEXT NOT NULL,
    short_name TEXT NOT NULL,
    team TEXT NOT NULL,
    team_colour TEXT NOT NULL,
    headshot_url TEXT NOT NULL
);""")

driver_values = []
for d in data['drivers']:
    fn = d['full_name'].replace("'", "''")
    tm = d['team'].replace("'", "''")
    driver_values.append(f"({d['driver_number']}, '{fn}', '{d['short_name']}', '{tm}', '{d['team_colour']}', '{d['headshot_url']}')")
statements.append(f"INSERT OR REPLACE INTO drivers (driver_number, full_name, short_name, team, team_colour, headshot_url) VALUES {', '.join(driver_values)};")

# 3. SESSIONS
statements.append("""CREATE TABLE IF NOT EXISTS sessions (
    id INTEGER PRIMARY KEY,
    season INTEGER NOT NULL DEFAULT 2026,
    round_name TEXT NOT NULL,
    country TEXT NOT NULL,
    session_type TEXT NOT NULL,
    session_type_str TEXT NOT NULL,
    session_date DATETIME NOT NULL,
    openf1_session_key INTEGER,
    is_completed INTEGER NOT NULL DEFAULT 0
);""")

session_values = []
for s in data['sessions']:
    rn = s['round_name'].replace("'", "''")
    ct = s['country'].replace("'", "''")
    st = s['session_type_str'].replace("'", "''")
    session_values.append(f"({s['id']}, {s['season']}, '{rn}', '{ct}', '{s['session_type']}', '{st}', '{s['session_date']}', {s['is_completed']})")
statements.append(f"INSERT OR REPLACE INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES {', '.join(session_values)};")

# 4. RESULTS
statements.append("""CREATE TABLE IF NOT EXISTS results (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id INTEGER NOT NULL,
    position INTEGER NOT NULL,
    driver_number INTEGER NOT NULL
);""")

res_values = []
for r in data['results']:
    res_values.append(f"({r['session_id']}, {r['position']}, {r['driver_number']})")
statements.append(f"INSERT INTO results (session_id, position, driver_number) VALUES {', '.join(res_values)};")

# 5. BETS
statements.append("""CREATE TABLE IF NOT EXISTS bets (
    id INTEGER PRIMARY KEY,
    session_id INTEGER NOT NULL,
    participant_id INTEGER NOT NULL,
    driver1_number INTEGER NOT NULL,
    driver2_number INTEGER NOT NULL,
    driver3_number INTEGER NOT NULL,
    points INTEGER DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);""")

bet_values = []
for b in data['bets']:
    pts = str(b['points']) if b['points'] is not None else "NULL"
    bet_values.append(f"({b['id']}, {b['session_id']}, {b['participant_id']}, {b['driver1_number']}, {b['driver2_number']}, {b['driver3_number']}, {pts}, '{b['created_at']}')")
statements.append(f"INSERT OR REPLACE INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES {', '.join(bet_values)};")

# 6. SETTINGS
statements.append("""CREATE TABLE IF NOT EXISTS settings (
    key TEXT PRIMARY KEY,
    value TEXT NOT NULL
);""")
statements.append("INSERT OR REPLACE INTO settings (key, value) VALUES ('season', '2026');")

print(f"Total compact statements: {len(statements)}")
with open(r"c:\F1 Stars\f1_stars\db\compact_statements.sql", "w", encoding="utf-8") as f:
    for i, stmt in enumerate(statements, 1):
        f.write(f"-- PASO {i}\n{stmt}\n\n")

print("Written compact_statements.sql successfully!")
