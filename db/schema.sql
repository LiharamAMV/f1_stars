-- Cloudflare D1 Database Schema for F1 Stars

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
