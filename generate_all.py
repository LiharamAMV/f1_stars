import re
import sys
import json
import os
from datetime import datetime

sys.stdout.reconfigure(encoding='utf-8')

# 1. DRIVERS
with open(r"C:\Users\leont\.gemini\antigravity\brain\a0bf321f-c7be-47bd-bb7b-811dc3eecda7\.user_uploaded\media_1794069016261_e1b4c17e.txt", "r", encoding="utf-8") as f:
    apostar_html = f.read()

select_d1 = apostar_html.split('id="driver1"')[1].split('</select>')[0]
driver_opts = re.findall(r'<option value="(\d+)"\s*data-headshot="([^"]+)"\s*data-colour="([^"]+)"\s*data-team="([^"]+)"\s*data-fullname="([^"]+)">\s*([^<]+?)\s*</option>', select_d1)

drivers = []
driver_name_to_num = {}
for num, headshot, color, team, fullname, short in driver_opts:
    d = {
        'driver_number': int(num),
        'headshot_url': headshot.strip(),
        'team_colour': color.strip(),
        'team': team.strip(),
        'full_name': fullname.strip(),
        'short_name': short.strip().upper()
    }
    drivers.append(d)
    driver_name_to_num[short.strip().upper()] = int(num)

# 2. PARTICIPANTS
participants = [
    {"id": 1, "nick": "Andrés"},
    {"id": 2, "nick": "Iván"},
    {"id": 3, "nick": "José Carlos"},
    {"id": 4, "nick": "Víctor"}
]
nick_to_id = {p['nick']: p['id'] for p in participants}

# 3. SESSIONS & BETS FROM AJUSTES
with open(r"C:\Users\leont\.gemini\antigravity\brain\a0bf321f-c7be-47bd-bb7b-811dc3eecda7\.user_uploaded\media_1794069016026_e2dbcfe6.txt", "r", encoding="utf-8") as f:
    ajustes_html = f.read()

session_opts = re.findall(r'<option value="(\d+)"\s*>\s*GP\s+([^\s–-]+(?:\s+[^\s–-]+)*?)\s*[–-]\s*(.*?)\s*\((\d{2}/\d{2}/\d{4})\)\s*·\s*(\d+)\s*apuesta\(s\)\s*</option>', ajustes_html)

def parse_session_type(name):
    nl = name.lower()
    if 'carrera sprint' in nl:
        return 'sprintRace'
    elif 'clasificación sprint' in nl or 'clasificacion sprint' in nl or 'sprint qualy' in nl:
        return 'sprintQualy'
    elif 'clasificación' in nl or 'clasificacion' in nl or 'qualy' in nl:
        return 'qualy'
    elif 'carrera' in nl or 'race' in nl:
        return 'race'
    return 'race'

cards = ajustes_html.split('<div class="f1-bets-session-card">')[1:]

# 4. OFFICIAL RESULTS FROM VER_APUESTAS
with open(r"C:\Users\leont\.gemini\antigravity\brain\a0bf321f-c7be-47bd-bb7b-811dc3eecda7\.user_uploaded\media_1794069016298_4d518b9c.txt", "r", encoding="utf-8") as f:
    ver_html = f.read()

ver_blocks = ver_html.split('<div class="f1-session-bets">')[1:]
results_map = {}
for vb in ver_blocks:
    m_h3 = re.search(r'<h3>🏁\s*GP\s+(.*?)\s*[—–-]\s*(.*?)\s*</h3>', vb)
    m_date = re.search(r'<p class="session-date">(.*?)</p>', vb)
    if not m_h3 or not m_date:
        continue
    country = m_h3.group(1).strip()
    stype = m_h3.group(2).strip()
    dt = m_date.group(1).strip()
    
    if 'f1-result-row' in vb:
        res_row = vb.split('class="f1-result-row"')[1].split('</tr>')[0]
        drivers_res = re.findall(r'alt="([^"]+)"', res_row)
        results_map[(country.lower(), stype.lower(), dt)] = drivers_res

# Parse cards
sessions_list = []
bets_list = []
results_list = []

for card in cards:
    m_h3 = re.search(r'<h3>🏁\s*GP\s+(.*?)\s*[—–-]\s*(.*?)\s*</h3>', card)
    m_date = re.search(r'(\d{2}/\d{2}/\d{4}\s+\d{2}:\d{2})', card)
    if not m_h3 or not m_date:
        continue
    country = m_h3.group(1).strip()
    stype_str = m_h3.group(2).strip()
    dt_str = m_date.group(1).strip()
    date_only = dt_str.split()[0]
    
    # Find matching session_id from session_opts
    found_s_id = None
    for opt in session_opts:
        if opt[1].strip().lower() == country.lower() and opt[2].strip().lower() == stype_str.lower() and opt[3].strip() == date_only:
            found_s_id = int(opt[0])
            break
    
    if not found_s_id:
        print(f"Warning: could not find session_id for {country} {stype_str} {dt_str}")
        continue
        
    is_comp = "completed" in card or "Completada" in card
    
    # Parse date to ISO format YYYY-MM-DD HH:MM:SS
    # dt_str format: DD/MM/YYYY HH:MM
    dt_parts = dt_str.split()
    d_parts = dt_parts[0].split('/')
    iso_date = f"{d_parts[2]}-{d_parts[1]}-{d_parts[0]} {dt_parts[1]}:00"
    
    # Check official results
    res_key = (country.lower(), stype_str.lower(), dt_str)
    official_drivers = results_map.get(res_key, [])
    
    session_obj = {
        'id': found_s_id,
        'season': 2026,
        'country': country,
        'round_name': f"GP {country}",
        'session_type': parse_session_type(stype_str),
        'session_type_str': stype_str,
        'session_date': iso_date,
        'is_completed': 1 if is_comp else 0,
        'official_results': official_drivers
    }
    sessions_list.append(session_obj)
    
    # Add results
    for pos, dname in enumerate(official_drivers, start=1):
        dnum = driver_name_to_num.get(dname.upper())
        if dnum:
            results_list.append({
                'session_id': found_s_id,
                'position': pos,
                'driver_number': dnum
            })
            
    # Parse bets in card
    rows = card.split('<tr>')[1:]
    for r in rows:
        m_id = re.search(r'class="f1-bet-id">(\d+)</td>', r)
        if not m_id:
            continue
        bet_id = int(m_id.group(1))
        
        m_nick = re.search(r'class="f1-bet-nick">\s*<strong>(.*?)</strong>\s*</td>', r)
        nick = m_nick.group(1).strip() if m_nick else ""
        
        driver_alts = re.findall(r'class="f1-bet-driver">.*?alt="([^"]+)".*?</td>', r, re.DOTALL)
        if len(driver_alts) != 3:
            continue
            
        d1_num = driver_name_to_num.get(driver_alts[0].upper())
        d2_num = driver_name_to_num.get(driver_alts[1].upper())
        d3_num = driver_name_to_num.get(driver_alts[2].upper())
        
        m_pts = re.search(r'<strong class="f1-pts">(\d+)\s*pts</strong>', r)
        pts = int(m_pts.group(1)) if m_pts else None
        
        m_bdate = re.search(r'class="f1-bet-date">([^<]+)</td>', r)
        bdate_str = m_bdate.group(1).strip() if m_bdate else ""
        # Format bdate
        if bdate_str:
            bp = bdate_str.split()
            bdp = bp[0].split('/')
            biso = f"{bdp[2]}-{bdp[1]}-{bdp[0]} {bp[1]}:00"
        else:
            biso = iso_date
            
        bets_list.append({
            'id': bet_id,
            'session_id': found_s_id,
            'participant_id': nick_to_id.get(nick, 1),
            'participant_nick': nick,
            'driver1_number': d1_num,
            'driver2_number': d2_num,
            'driver3_number': d3_num,
            'driver1_name': driver_alts[0].upper(),
            'driver2_name': driver_alts[1].upper(),
            'driver3_name': driver_alts[2].upper(),
            'points': pts,
            'created_at': biso
        })

# 5. FUTURE PENDING SESSIONS FROM APOSTAR
apostar_opts = re.findall(r'<option value="(\d+)">\s*GP\s+([^\s–-]+(?:\s+[^\s–-]+)*?)\s*[–-]\s*(.*?)\s*\((\d{2}/\d{2}/\d{4}\s+\d{2}:\d{2})\)\s*</option>', apostar_html)
existing_s_ids = {s['id'] for s in sessions_list}

for s_id_str, country, stype, dt_str in apostar_opts:
    s_id = int(s_id_str)
    if s_id not in existing_s_ids:
        dt_parts = dt_str.split()
        d_parts = dt_parts[0].split('/')
        iso_date = f"{d_parts[2]}-{d_parts[1]}-{d_parts[0]} {dt_parts[1]}:00"
        sessions_list.append({
            'id': s_id,
            'season': 2026,
            'country': country.strip(),
            'round_name': f"GP {country.strip()}",
            'session_type': parse_session_type(stype.strip()),
            'session_type_str': stype.strip(),
            'session_date': iso_date,
            'is_completed': 0,
            'official_results': []
        })
        existing_s_ids.add(s_id)

# 6. CALENDAR
with open(r"C:\Users\leont\.gemini\antigravity\brain\a0bf321f-c7be-47bd-bb7b-811dc3eecda7\.user_uploaded\media_1794069016261_73329320.txt", "r", encoding="utf-8") as f:
    cal_html = f.read()

cal_cards = cal_html.split('<div class="f1-race-card">')[1:]
calendar_list = []
for c in cal_cards:
    round_no = re.search(r'<div class="race-round">(.*?)</div>', c)
    country = re.search(r'<h3>(.*?)</h3>', c)
    circuit = re.search(r'<p class="circuit">(.*?)</p>', c)
    dates = re.search(r'<p class="dates">\s*(.*?)\s*</p>', c, re.DOTALL)
    calendar_list.append({
        'round': round_no.group(1).strip() if round_no else "",
        'country': country.group(1).strip() if country else "",
        'circuit': circuit.group(1).strip() if circuit else "",
        'dates': " ".join(dates.group(1).split()) if dates else ""
    })

print(f"Summary: {len(drivers)} drivers, {len(participants)} participants, {len(sessions_list)} sessions, {len(results_list)} results, {len(bets_list)} bets, {len(calendar_list)} calendar rounds.")

# Sort sessions chronologically
sessions_list.sort(key=lambda x: x['session_date'])

# CREATE DIRECTORIES
os.makedirs(r"c:\F1 Stars\f1_stars\db", exist_ok=True)
os.makedirs(r"c:\F1 Stars\f1_stars\public\data", exist_ok=True)

# WRITE data.json
full_data = {
    'season': 2026,
    'participants': participants,
    'drivers': drivers,
    'sessions': sessions_list,
    'results': results_list,
    'bets': bets_list,
    'calendar': calendar_list
}
with open(r"c:\F1 Stars\f1_stars\public\data\data.json", "w", encoding="utf-8") as f:
    json.dump(full_data, f, ensure_ascii=False, indent=2)

# WRITE schema.sql
schema_sql = """-- Cloudflare D1 Database Schema for F1 Stars

DROP TABLE IF EXISTS bets;
DROP TABLE IF EXISTS results;
DROP TABLE IF EXISTS sessions;
DROP TABLE IF EXISTS drivers;
DROP TABLE IF EXISTS participants;
DROP TABLE IF EXISTS settings;

CREATE TABLE participants (
    id INTEGER PRIMARY KEY,
    nick TEXT NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE drivers (
    driver_number INTEGER PRIMARY KEY,
    full_name TEXT NOT NULL,
    short_name TEXT NOT NULL,
    team TEXT NOT NULL,
    team_colour TEXT NOT NULL,
    headshot_url TEXT NOT NULL
);

CREATE TABLE sessions (
    id INTEGER PRIMARY KEY,
    season INTEGER NOT NULL DEFAULT 2026,
    round_name TEXT NOT NULL,
    country TEXT NOT NULL,
    session_type TEXT NOT NULL,
    session_type_str TEXT NOT NULL,
    session_date DATETIME NOT NULL,
    openf1_session_key INTEGER,
    is_completed INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE results (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id INTEGER NOT NULL,
    position INTEGER NOT NULL,
    driver_number INTEGER NOT NULL,
    FOREIGN KEY (session_id) REFERENCES sessions(id),
    FOREIGN KEY (driver_number) REFERENCES drivers(driver_number)
);

CREATE TABLE bets (
    id INTEGER PRIMARY KEY,
    session_id INTEGER NOT NULL,
    participant_id INTEGER NOT NULL,
    driver1_number INTEGER NOT NULL,
    driver2_number INTEGER NOT NULL,
    driver3_number INTEGER NOT NULL,
    points INTEGER DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES sessions(id),
    FOREIGN KEY (participant_id) REFERENCES participants(id)
);

CREATE TABLE settings (
    key TEXT PRIMARY KEY,
    value TEXT NOT NULL
);
"""
with open(r"c:\F1 Stars\f1_stars\db\schema.sql", "w", encoding="utf-8") as f:
    f.write(schema_sql)

# WRITE seed.sql
sql_lines = [schema_sql, "\n-- SEED DATA\n"]

# Insert participants
sql_lines.append("-- Participants")
for p in participants:
    sql_lines.append(f"INSERT INTO participants (id, nick) VALUES ({p['id']}, '{p['nick']}');")

# Insert drivers
sql_lines.append("\n-- Drivers")
for d in drivers:
    fn = d['full_name'].replace("'", "''")
    tm = d['team'].replace("'", "''")
    sql_lines.append(f"INSERT INTO drivers (driver_number, full_name, short_name, team, team_colour, headshot_url) VALUES ({d['driver_number']}, '{fn}', '{d['short_name']}', '{tm}', '{d['team_colour']}', '{d['headshot_url']}');")

# Insert sessions
sql_lines.append("\n-- Sessions")
for s in sessions_list:
    rn = s['round_name'].replace("'", "''")
    ct = s['country'].replace("'", "''")
    st = s['session_type_str'].replace("'", "''")
    sql_lines.append(f"INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES ({s['id']}, {s['season']}, '{rn}', '{ct}', '{s['session_type']}', '{st}', '{s['session_date']}', {s['is_completed']});")

# Insert results
sql_lines.append("\n-- Results")
for r in results_list:
    sql_lines.append(f"INSERT INTO results (session_id, position, driver_number) VALUES ({r['session_id']}, {r['position']}, {r['driver_number']});")

# Insert bets
sql_lines.append("\n-- Bets")
for b in bets_list:
    pts_val = str(b['points']) if b['points'] is not None else "NULL"
    sql_lines.append(f"INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES ({b['id']}, {b['session_id']}, {b['participant_id']}, {b['driver1_number']}, {b['driver2_number']}, {b['driver3_number']}, {pts_val}, '{b['created_at']}');")

# Insert settings
sql_lines.append("\n-- Settings")
sql_lines.append("INSERT INTO settings (key, value) VALUES ('season', '2026');")

with open(r"c:\F1 Stars\f1_stars\db\seed.sql", "w", encoding="utf-8") as f:
    f.write("\n".join(sql_lines))

print("Successfully written schema.sql, seed.sql and initial-data.json!")
