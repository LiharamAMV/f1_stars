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


-- SEED DATA

-- Participants
INSERT INTO participants (id, nick) VALUES (1, 'Andrés');
INSERT INTO participants (id, nick) VALUES (2, 'Iván');
INSERT INTO participants (id, nick) VALUES (3, 'José Carlos');
INSERT INTO participants (id, nick) VALUES (4, 'Víctor');

-- Drivers
INSERT INTO drivers (driver_number, full_name, short_name, team, team_colour, headshot_url) VALUES (23, 'Alexander ALBON', 'ALBON', 'Williams', '1868DB', 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/A/ALEALB01_Alexander_Albon/alealb01.png.transform/1col/image.png');
INSERT INTO drivers (driver_number, full_name, short_name, team, team_colour, headshot_url) VALUES (14, 'Fernando ALONSO', 'ALONSO', 'Aston Martin', '229971', 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/F/FERALO01_Fernando_Alonso/feralo01.png.transform/1col/image.png');
INSERT INTO drivers (driver_number, full_name, short_name, team, team_colour, headshot_url) VALUES (12, 'Kimi ANTONELLI', 'ANTONELLI', 'Mercedes', '00D7B6', 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/K/ANDANT01_Kimi_Antonelli/andant01.png.transform/1col/image.png');
INSERT INTO drivers (driver_number, full_name, short_name, team, team_colour, headshot_url) VALUES (87, 'Oliver BEARMAN', 'BEARMAN', 'Haas F1 Team', '9C9FA2', 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/O/OLIBEA01_Oliver_Bearman/olibea01.png.transform/1col/image.png');
INSERT INTO drivers (driver_number, full_name, short_name, team, team_colour, headshot_url) VALUES (5, 'Gabriel BORTOLETO', 'BORTOLETO', 'Audi', 'F50537', 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/G/GABBOR01_Gabriel_Bortoleto/gabbor01.png.transform/1col/image.png');
INSERT INTO drivers (driver_number, full_name, short_name, team, team_colour, headshot_url) VALUES (77, 'Valtteri BOTTAS', 'BOTTAS', 'Cadillac', '909090', 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/V/VALBOT01_Valtteri_Bottas/valbot01.png.transform/1col/image.png');
INSERT INTO drivers (driver_number, full_name, short_name, team, team_colour, headshot_url) VALUES (43, 'Franco COLAPINTO', 'COLAPINTO', 'Alpine', '00A1E8', 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/F/FRACOL01_Franco_Colapinto/fracol01.png.transform/1col/image.png');
INSERT INTO drivers (driver_number, full_name, short_name, team, team_colour, headshot_url) VALUES (10, 'Pierre GASLY', 'GASLY', 'Alpine', '00A1E8', 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/P/PIEGAS01_Pierre_Gasly/piegas01.png.transform/1col/image.png');
INSERT INTO drivers (driver_number, full_name, short_name, team, team_colour, headshot_url) VALUES (6, 'Isack HADJAR', 'HADJAR', 'Red Bull Racing', '4781D7', 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/I/ISAHAD01_Isack_Hadjar/isahad01.png.transform/1col/image.png');
INSERT INTO drivers (driver_number, full_name, short_name, team, team_colour, headshot_url) VALUES (44, 'Lewis HAMILTON', 'HAMILTON', 'Ferrari', 'ED1131', 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/L/LEWHAM01_Lewis_Hamilton/lewham01.png.transform/1col/image.png');
INSERT INTO drivers (driver_number, full_name, short_name, team, team_colour, headshot_url) VALUES (27, 'Nico HULKENBERG', 'HULKENBERG', 'Audi', 'F50537', 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/N/NICHUL01_Nico_Hulkenberg/nichul01.png.transform/1col/image.png');
INSERT INTO drivers (driver_number, full_name, short_name, team, team_colour, headshot_url) VALUES (30, 'Liam LAWSON', 'LAWSON', 'Racing Bulls', '6C98FF', 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/L/LIALAW01_Liam_Lawson/lialaw01.png.transform/1col/image.png');
INSERT INTO drivers (driver_number, full_name, short_name, team, team_colour, headshot_url) VALUES (16, 'Charles LECLERC', 'LECLERC', 'Ferrari', 'ED1131', 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/C/CHALEC01_Charles_Leclerc/chalec01.png.transform/1col/image.png');
INSERT INTO drivers (driver_number, full_name, short_name, team, team_colour, headshot_url) VALUES (41, 'Arvid LINDBLAD', 'LINDBLAD', 'Racing Bulls', '6C98FF', 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/A/ARVLIN01_Arvid_Lindblad/arvlin01.png.transform/1col/image.png');
INSERT INTO drivers (driver_number, full_name, short_name, team, team_colour, headshot_url) VALUES (1, 'Lando NORRIS', 'NORRIS', 'McLaren', 'F47600', 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/L/LANNOR01_Lando_Norris/lannor01.png.transform/1col/image.png');
INSERT INTO drivers (driver_number, full_name, short_name, team, team_colour, headshot_url) VALUES (31, 'Esteban OCON', 'OCON', 'Haas F1 Team', '9C9FA2', 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/E/ESTOCO01_Esteban_Ocon/estoco01.png.transform/1col/image.png');
INSERT INTO drivers (driver_number, full_name, short_name, team, team_colour, headshot_url) VALUES (11, 'Sergio PEREZ', 'PEREZ', 'Cadillac', '909090', 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/S/SERPER01_Sergio_Perez/serper01.png.transform/1col/image.png');
INSERT INTO drivers (driver_number, full_name, short_name, team, team_colour, headshot_url) VALUES (81, 'Oscar PIASTRI', 'PIASTRI', 'McLaren', 'F47600', 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/O/OSCPIA01_Oscar_Piastri/oscpia01.png.transform/1col/image.png');
INSERT INTO drivers (driver_number, full_name, short_name, team, team_colour, headshot_url) VALUES (63, 'George RUSSELL', 'RUSSELL', 'Mercedes', '00D7B6', 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/G/GEORUS01_George_Russell/georus01.png.transform/1col/image.png');
INSERT INTO drivers (driver_number, full_name, short_name, team, team_colour, headshot_url) VALUES (55, 'Carlos SAINZ', 'SAINZ', 'Williams', '1868DB', 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/C/CARSAI01_Carlos_Sainz/carsai01.png.transform/1col/image.png');
INSERT INTO drivers (driver_number, full_name, short_name, team, team_colour, headshot_url) VALUES (18, 'Lance STROLL', 'STROLL', 'Aston Martin', '229971', 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/L/LANSTR01_Lance_Stroll/lanstr01.png.transform/1col/image.png');
INSERT INTO drivers (driver_number, full_name, short_name, team, team_colour, headshot_url) VALUES (3, 'Max VERSTAPPEN', 'VERSTAPPEN', 'Red Bull Racing', '4781D7', 'https://media.formula1.com/d_driver_fallback_image.png/content/dam/fom-website/drivers/M/MAXVER01_Max_Verstappen/maxver01.png.transform/1col/image.png');

-- Sessions
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (98, 2026, 'GP Australia', 'Australia', 'qualy', 'Clasificación', '2026-03-07 05:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (97, 2026, 'GP Australia', 'Australia', 'race', 'Carrera', '2026-03-08 04:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (101, 2026, 'GP China', 'China', 'sprintQualy', 'Clasificación Sprint', '2026-03-13 07:30:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (102, 2026, 'GP China', 'China', 'sprintRace', 'Carrera Sprint', '2026-03-14 03:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (100, 2026, 'GP China', 'China', 'qualy', 'Clasificación', '2026-03-14 07:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (99, 2026, 'GP China', 'China', 'race', 'Carrera', '2026-03-15 07:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (104, 2026, 'GP Japan', 'Japan', 'qualy', 'Clasificación', '2026-03-28 06:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (103, 2026, 'GP Japan', 'Japan', 'race', 'Carrera', '2026-03-29 05:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (114, 2026, 'GP USA', 'USA', 'sprintQualy', 'Clasificación Sprint', '2026-05-02 13:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (116, 2026, 'GP USA', 'USA', 'sprintRace', 'Carrera Sprint', '2026-05-02 16:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (106, 2026, 'GP United States', 'United States', 'qualy', 'Clasificación', '2026-05-02 20:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (105, 2026, 'GP United States', 'United States', 'race', 'Carrera', '2026-05-03 17:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (204, 2026, 'GP Canada', 'Canada', 'sprintQualy', 'Clasificación Sprint', '2026-05-22 20:30:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (203, 2026, 'GP Canada', 'Canada', 'sprintRace', 'Carrera Sprint', '2026-05-23 16:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (109, 2026, 'GP Canada', 'Canada', 'qualy', 'Clasificación', '2026-05-23 20:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (108, 2026, 'GP Canada', 'Canada', 'race', 'Carrera', '2026-05-24 20:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (216, 2026, 'GP Monaco', 'Monaco', 'qualy', 'Clasificación', '2026-06-06 14:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (217, 2026, 'GP Monaco', 'Monaco', 'race', 'Carrera', '2026-06-07 13:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (120, 2026, 'GP Spain', 'Spain', 'qualy', 'Clasificación', '2026-06-13 14:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (118, 2026, 'GP Spain', 'Spain', 'race', 'Carrera', '2026-06-14 13:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (127, 2026, 'GP Austria', 'Austria', 'qualy', 'Clasificación', '2026-06-27 14:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (126, 2026, 'GP Austria', 'Austria', 'race', 'Carrera', '2026-06-28 13:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (208, 2026, 'GP United Kingdom', 'United Kingdom', 'sprintQualy', 'Clasificación Sprint', '2026-07-03 15:30:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (206, 2026, 'GP United Kingdom', 'United Kingdom', 'sprintRace', 'Carrera Sprint', '2026-07-04 11:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (130, 2026, 'GP United Kingdom', 'United Kingdom', 'qualy', 'Clasificación', '2026-07-04 15:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (129, 2026, 'GP United Kingdom', 'United Kingdom', 'race', 'Carrera', '2026-07-05 14:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (134, 2026, 'GP Belgium', 'Belgium', 'qualy', 'Clasificación', '2026-07-18 14:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (132, 2026, 'GP Belgium', 'Belgium', 'race', 'Carrera', '2026-07-19 13:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (138, 2026, 'GP Hungary', 'Hungary', 'qualy', 'Clasificación', '2026-07-25 14:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (136, 2026, 'GP Hungary', 'Hungary', 'race', 'Carrera', '2026-07-26 13:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (211, 2026, 'GP Netherlands', 'Netherlands', 'sprintQualy', 'Clasificación Sprint', '2026-08-21 14:30:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (210, 2026, 'GP Netherlands', 'Netherlands', 'sprintRace', 'Carrera Sprint', '2026-08-22 10:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (146, 2026, 'GP Netherlands', 'Netherlands', 'qualy', 'Clasificación', '2026-08-22 14:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (144, 2026, 'GP Netherlands', 'Netherlands', 'race', 'Carrera', '2026-08-23 13:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (150, 2026, 'GP Italy', 'Italy', 'qualy', 'Clasificación', '2026-09-05 14:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (148, 2026, 'GP Italy', 'Italy', 'race', 'Carrera', '2026-09-06 13:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (154, 2026, 'GP Spain', 'Spain', 'qualy', 'Clasificación', '2026-09-12 14:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (152, 2026, 'GP Spain', 'Spain', 'race', 'Carrera', '2026-09-13 13:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (162, 2026, 'GP Azerbaijan', 'Azerbaijan', 'qualy', 'Clasificación', '2026-09-25 12:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (160, 2026, 'GP Azerbaijan', 'Azerbaijan', 'race', 'Carrera', '2026-09-26 11:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (222, 2026, 'GP Malasia', 'Malasia', 'qualy', 'Clasificación', '2026-10-03 08:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (224, 2026, 'GP Malasia', 'Malasia', 'race', 'Carrera', '2026-10-04 07:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (214, 2026, 'GP Singapore', 'Singapore', 'sprintQualy', 'Clasificación Sprint', '2026-10-09 12:30:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (213, 2026, 'GP Singapore', 'Singapore', 'sprintRace', 'Carrera Sprint', '2026-10-10 09:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (166, 2026, 'GP Singapore', 'Singapore', 'qualy', 'Clasificación', '2026-10-10 13:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (164, 2026, 'GP Singapore', 'Singapore', 'race', 'Carrera', '2026-10-11 12:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (169, 2026, 'GP United States', 'United States', 'qualy', 'Clasificación', '2026-10-24 21:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (168, 2026, 'GP United States', 'United States', 'race', 'Carrera', '2026-10-25 20:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (172, 2026, 'GP Mexico', 'Mexico', 'qualy', 'Clasificación', '2026-10-31 21:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (171, 2026, 'GP Mexico', 'Mexico', 'race', 'Carrera', '2026-11-01 20:00:00', 1);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (180, 2026, 'GP Brazil', 'Brazil', 'qualy', 'Clasificación', '2026-11-07 18:00:00', 0);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (178, 2026, 'GP Brazil', 'Brazil', 'race', 'Carrera', '2026-11-08 17:00:00', 0);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (183, 2026, 'GP United States', 'United States', 'qualy', 'Clasificación', '2026-11-21 04:00:00', 0);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (181, 2026, 'GP United States', 'United States', 'race', 'Carrera', '2026-11-22 04:00:00', 0);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (187, 2026, 'GP Qatar', 'Qatar', 'qualy', 'Clasificación', '2026-11-28 18:00:00', 0);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (185, 2026, 'GP Qatar', 'Qatar', 'race', 'Carrera', '2026-11-29 16:00:00', 0);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (190, 2026, 'GP United Arab Emirates', 'United Arab Emirates', 'qualy', 'Clasificación', '2026-12-05 14:00:00', 0);
INSERT INTO sessions (id, season, round_name, country, session_type, session_type_str, session_date, is_completed) VALUES (189, 2026, 'GP United Arab Emirates', 'United Arab Emirates', 'race', 'Carrera', '2026-12-06 13:00:00', 0);

-- Results
INSERT INTO results (session_id, position, driver_number) VALUES (171, 1, 3);
INSERT INTO results (session_id, position, driver_number) VALUES (171, 2, 44);
INSERT INTO results (session_id, position, driver_number) VALUES (171, 3, 12);
INSERT INTO results (session_id, position, driver_number) VALUES (172, 1, 44);
INSERT INTO results (session_id, position, driver_number) VALUES (172, 2, 63);
INSERT INTO results (session_id, position, driver_number) VALUES (172, 3, 16);
INSERT INTO results (session_id, position, driver_number) VALUES (168, 1, 12);
INSERT INTO results (session_id, position, driver_number) VALUES (168, 2, 16);
INSERT INTO results (session_id, position, driver_number) VALUES (168, 3, 3);
INSERT INTO results (session_id, position, driver_number) VALUES (169, 1, 12);
INSERT INTO results (session_id, position, driver_number) VALUES (169, 2, 3);
INSERT INTO results (session_id, position, driver_number) VALUES (169, 3, 6);
INSERT INTO results (session_id, position, driver_number) VALUES (164, 1, 3);
INSERT INTO results (session_id, position, driver_number) VALUES (164, 2, 16);
INSERT INTO results (session_id, position, driver_number) VALUES (164, 3, 12);
INSERT INTO results (session_id, position, driver_number) VALUES (166, 1, 3);
INSERT INTO results (session_id, position, driver_number) VALUES (166, 2, 16);
INSERT INTO results (session_id, position, driver_number) VALUES (166, 3, 44);
INSERT INTO results (session_id, position, driver_number) VALUES (213, 1, 3);
INSERT INTO results (session_id, position, driver_number) VALUES (213, 2, 44);
INSERT INTO results (session_id, position, driver_number) VALUES (213, 3, 16);
INSERT INTO results (session_id, position, driver_number) VALUES (214, 1, 3);
INSERT INTO results (session_id, position, driver_number) VALUES (214, 2, 63);
INSERT INTO results (session_id, position, driver_number) VALUES (214, 3, 16);
INSERT INTO results (session_id, position, driver_number) VALUES (224, 1, 3);
INSERT INTO results (session_id, position, driver_number) VALUES (224, 2, 12);
INSERT INTO results (session_id, position, driver_number) VALUES (224, 3, 44);
INSERT INTO results (session_id, position, driver_number) VALUES (222, 1, 3);
INSERT INTO results (session_id, position, driver_number) VALUES (222, 2, 44);
INSERT INTO results (session_id, position, driver_number) VALUES (222, 3, 6);
INSERT INTO results (session_id, position, driver_number) VALUES (160, 1, 63);
INSERT INTO results (session_id, position, driver_number) VALUES (160, 2, 3);
INSERT INTO results (session_id, position, driver_number) VALUES (160, 3, 6);
INSERT INTO results (session_id, position, driver_number) VALUES (162, 1, 63);
INSERT INTO results (session_id, position, driver_number) VALUES (162, 2, 16);
INSERT INTO results (session_id, position, driver_number) VALUES (162, 3, 81);
INSERT INTO results (session_id, position, driver_number) VALUES (152, 1, 12);
INSERT INTO results (session_id, position, driver_number) VALUES (152, 2, 3);
INSERT INTO results (session_id, position, driver_number) VALUES (152, 3, 1);
INSERT INTO results (session_id, position, driver_number) VALUES (154, 1, 1);
INSERT INTO results (session_id, position, driver_number) VALUES (154, 2, 12);
INSERT INTO results (session_id, position, driver_number) VALUES (154, 3, 3);
INSERT INTO results (session_id, position, driver_number) VALUES (148, 1, 12);
INSERT INTO results (session_id, position, driver_number) VALUES (148, 2, 63);
INSERT INTO results (session_id, position, driver_number) VALUES (148, 3, 3);
INSERT INTO results (session_id, position, driver_number) VALUES (150, 1, 10);
INSERT INTO results (session_id, position, driver_number) VALUES (150, 2, 63);
INSERT INTO results (session_id, position, driver_number) VALUES (150, 3, 81);
INSERT INTO results (session_id, position, driver_number) VALUES (144, 1, 1);
INSERT INTO results (session_id, position, driver_number) VALUES (144, 2, 12);
INSERT INTO results (session_id, position, driver_number) VALUES (144, 3, 63);
INSERT INTO results (session_id, position, driver_number) VALUES (146, 1, 1);
INSERT INTO results (session_id, position, driver_number) VALUES (146, 2, 63);
INSERT INTO results (session_id, position, driver_number) VALUES (146, 3, 12);
INSERT INTO results (session_id, position, driver_number) VALUES (210, 1, 63);
INSERT INTO results (session_id, position, driver_number) VALUES (210, 2, 16);
INSERT INTO results (session_id, position, driver_number) VALUES (210, 3, 1);
INSERT INTO results (session_id, position, driver_number) VALUES (211, 1, 63);
INSERT INTO results (session_id, position, driver_number) VALUES (211, 2, 1);
INSERT INTO results (session_id, position, driver_number) VALUES (211, 3, 16);
INSERT INTO results (session_id, position, driver_number) VALUES (136, 1, 1);
INSERT INTO results (session_id, position, driver_number) VALUES (136, 2, 3);
INSERT INTO results (session_id, position, driver_number) VALUES (136, 3, 12);
INSERT INTO results (session_id, position, driver_number) VALUES (138, 1, 1);
INSERT INTO results (session_id, position, driver_number) VALUES (138, 2, 44);
INSERT INTO results (session_id, position, driver_number) VALUES (138, 3, 16);
INSERT INTO results (session_id, position, driver_number) VALUES (132, 1, 12);
INSERT INTO results (session_id, position, driver_number) VALUES (132, 2, 16);
INSERT INTO results (session_id, position, driver_number) VALUES (132, 3, 3);
INSERT INTO results (session_id, position, driver_number) VALUES (134, 1, 12);
INSERT INTO results (session_id, position, driver_number) VALUES (134, 2, 3);
INSERT INTO results (session_id, position, driver_number) VALUES (134, 3, 1);
INSERT INTO results (session_id, position, driver_number) VALUES (129, 1, 16);
INSERT INTO results (session_id, position, driver_number) VALUES (129, 2, 63);
INSERT INTO results (session_id, position, driver_number) VALUES (129, 3, 44);
INSERT INTO results (session_id, position, driver_number) VALUES (130, 1, 12);
INSERT INTO results (session_id, position, driver_number) VALUES (130, 2, 16);
INSERT INTO results (session_id, position, driver_number) VALUES (130, 3, 44);
INSERT INTO results (session_id, position, driver_number) VALUES (206, 1, 12);
INSERT INTO results (session_id, position, driver_number) VALUES (206, 2, 44);
INSERT INTO results (session_id, position, driver_number) VALUES (206, 3, 1);
INSERT INTO results (session_id, position, driver_number) VALUES (208, 1, 44);
INSERT INTO results (session_id, position, driver_number) VALUES (208, 2, 12);
INSERT INTO results (session_id, position, driver_number) VALUES (208, 3, 3);
INSERT INTO results (session_id, position, driver_number) VALUES (126, 1, 63);
INSERT INTO results (session_id, position, driver_number) VALUES (126, 2, 3);
INSERT INTO results (session_id, position, driver_number) VALUES (126, 3, 12);
INSERT INTO results (session_id, position, driver_number) VALUES (127, 1, 63);
INSERT INTO results (session_id, position, driver_number) VALUES (127, 2, 16);
INSERT INTO results (session_id, position, driver_number) VALUES (127, 3, 44);
INSERT INTO results (session_id, position, driver_number) VALUES (118, 1, 44);
INSERT INTO results (session_id, position, driver_number) VALUES (118, 2, 63);
INSERT INTO results (session_id, position, driver_number) VALUES (118, 3, 1);
INSERT INTO results (session_id, position, driver_number) VALUES (120, 1, 63);
INSERT INTO results (session_id, position, driver_number) VALUES (120, 2, 44);
INSERT INTO results (session_id, position, driver_number) VALUES (120, 3, 12);
INSERT INTO results (session_id, position, driver_number) VALUES (217, 1, 12);
INSERT INTO results (session_id, position, driver_number) VALUES (217, 2, 44);
INSERT INTO results (session_id, position, driver_number) VALUES (217, 3, 6);
INSERT INTO results (session_id, position, driver_number) VALUES (216, 1, 12);
INSERT INTO results (session_id, position, driver_number) VALUES (216, 2, 3);
INSERT INTO results (session_id, position, driver_number) VALUES (216, 3, 44);
INSERT INTO results (session_id, position, driver_number) VALUES (108, 1, 12);
INSERT INTO results (session_id, position, driver_number) VALUES (108, 2, 44);
INSERT INTO results (session_id, position, driver_number) VALUES (108, 3, 3);
INSERT INTO results (session_id, position, driver_number) VALUES (109, 1, 63);
INSERT INTO results (session_id, position, driver_number) VALUES (109, 2, 12);
INSERT INTO results (session_id, position, driver_number) VALUES (109, 3, 1);
INSERT INTO results (session_id, position, driver_number) VALUES (203, 1, 63);
INSERT INTO results (session_id, position, driver_number) VALUES (203, 2, 1);
INSERT INTO results (session_id, position, driver_number) VALUES (203, 3, 12);
INSERT INTO results (session_id, position, driver_number) VALUES (204, 1, 63);
INSERT INTO results (session_id, position, driver_number) VALUES (204, 2, 12);
INSERT INTO results (session_id, position, driver_number) VALUES (204, 3, 1);
INSERT INTO results (session_id, position, driver_number) VALUES (105, 1, 12);
INSERT INTO results (session_id, position, driver_number) VALUES (105, 2, 1);
INSERT INTO results (session_id, position, driver_number) VALUES (105, 3, 81);
INSERT INTO results (session_id, position, driver_number) VALUES (106, 1, 12);
INSERT INTO results (session_id, position, driver_number) VALUES (106, 2, 3);
INSERT INTO results (session_id, position, driver_number) VALUES (106, 3, 16);
INSERT INTO results (session_id, position, driver_number) VALUES (116, 1, 1);
INSERT INTO results (session_id, position, driver_number) VALUES (116, 2, 81);
INSERT INTO results (session_id, position, driver_number) VALUES (116, 3, 16);
INSERT INTO results (session_id, position, driver_number) VALUES (114, 1, 1);
INSERT INTO results (session_id, position, driver_number) VALUES (114, 2, 12);
INSERT INTO results (session_id, position, driver_number) VALUES (114, 3, 81);
INSERT INTO results (session_id, position, driver_number) VALUES (103, 1, 12);
INSERT INTO results (session_id, position, driver_number) VALUES (103, 2, 81);
INSERT INTO results (session_id, position, driver_number) VALUES (103, 3, 16);
INSERT INTO results (session_id, position, driver_number) VALUES (104, 1, 12);
INSERT INTO results (session_id, position, driver_number) VALUES (104, 2, 63);
INSERT INTO results (session_id, position, driver_number) VALUES (104, 3, 81);
INSERT INTO results (session_id, position, driver_number) VALUES (99, 1, 12);
INSERT INTO results (session_id, position, driver_number) VALUES (99, 2, 63);
INSERT INTO results (session_id, position, driver_number) VALUES (99, 3, 44);
INSERT INTO results (session_id, position, driver_number) VALUES (100, 1, 12);
INSERT INTO results (session_id, position, driver_number) VALUES (100, 2, 63);
INSERT INTO results (session_id, position, driver_number) VALUES (100, 3, 44);
INSERT INTO results (session_id, position, driver_number) VALUES (102, 1, 63);
INSERT INTO results (session_id, position, driver_number) VALUES (102, 2, 16);
INSERT INTO results (session_id, position, driver_number) VALUES (102, 3, 44);
INSERT INTO results (session_id, position, driver_number) VALUES (101, 1, 63);
INSERT INTO results (session_id, position, driver_number) VALUES (101, 2, 12);
INSERT INTO results (session_id, position, driver_number) VALUES (101, 3, 1);
INSERT INTO results (session_id, position, driver_number) VALUES (97, 1, 63);
INSERT INTO results (session_id, position, driver_number) VALUES (97, 2, 12);
INSERT INTO results (session_id, position, driver_number) VALUES (97, 3, 16);
INSERT INTO results (session_id, position, driver_number) VALUES (98, 1, 63);
INSERT INTO results (session_id, position, driver_number) VALUES (98, 2, 63);
INSERT INTO results (session_id, position, driver_number) VALUES (98, 3, 12);

-- Bets
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (227, 180, 1, 3, 44, 12, NULL, '2026-11-07 16:14:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (226, 171, 1, 63, 44, 16, 5, '2026-11-01 20:02:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (224, 171, 2, 44, 16, 3, 4, '2026-11-01 19:39:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (223, 171, 3, 63, 3, 12, 5, '2026-11-01 17:58:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (225, 171, 4, 63, 44, 3, 4, '2026-11-01 19:43:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (219, 172, 1, 12, 1, 16, 3, '2026-10-31 19:12:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (222, 172, 2, 16, 12, 1, 1, '2026-10-31 20:35:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (221, 172, 3, 12, 16, 3, 4, '2026-10-31 20:03:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (220, 172, 4, 12, 3, 1, 1, '2026-10-31 19:56:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (218, 168, 1, 3, 12, 16, 5, '2026-10-25 18:25:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (217, 168, 2, 12, 3, 6, 5, '2026-10-25 09:15:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (214, 168, 3, 3, 12, 44, 5, '2026-10-24 22:05:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (216, 168, 4, 3, 12, 6, 3, '2026-10-24 23:53:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (212, 169, 1, 3, 44, 16, 4, '2026-10-24 19:35:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (215, 169, 2, 16, 12, 44, 3, '2026-10-24 21:00:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (211, 169, 3, 3, 44, 12, 3, '2026-10-24 19:15:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (213, 169, 4, 3, 12, 44, 5, '2026-10-24 19:47:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (209, 164, 1, 3, 16, 44, 6, '2026-10-11 11:24:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (210, 164, 2, 3, 44, 12, 6, '2026-10-11 11:31:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (208, 164, 3, 3, 44, 12, 6, '2026-10-11 08:04:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (207, 164, 4, 3, 16, 12, 9, '2026-10-10 21:55:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (206, 166, 1, 3, 63, 44, 6, '2026-10-10 13:14:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (205, 166, 2, 44, 3, 63, 3, '2026-10-10 12:56:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (203, 166, 3, 3, 44, 12, 7, '2026-10-10 12:33:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (204, 166, 4, 3, 12, 44, 7, '2026-10-10 12:45:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (200, 213, 1, 3, 63, 16, 6, '2026-10-10 05:56:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (201, 213, 2, 3, 16, 63, 5, '2026-10-10 07:29:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (199, 213, 3, 3, 63, 81, 3, '2026-10-09 20:47:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (202, 213, 4, 3, 63, 1, 3, '2026-10-10 08:34:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (197, 214, 1, 63, 3, 12, 4, '2026-10-09 11:30:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (196, 214, 2, 63, 12, 44, 2, '2026-10-09 11:00:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (195, 214, 3, 12, 3, 1, 3, '2026-10-09 10:57:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (198, 214, 4, 3, 12, 63, 5, '2026-10-09 11:34:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (191, 224, 1, 3, 12, 63, 6, '2026-10-03 13:15:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (193, 224, 2, 3, 12, 16, 8, '2026-10-03 21:48:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (194, 224, 3, 3, 12, 44, 9, '2026-10-03 23:21:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (192, 224, 4, 3, 12, 1, 6, '2026-10-03 16:27:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (188, 222, 1, 3, 63, 6, 6, '2026-10-02 21:08:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (189, 222, 2, 12, 3, 16, 3, '2026-10-03 07:11:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (186, 222, 3, 12, 3, 63, 2, '2026-10-02 20:06:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (187, 222, 4, 12, 3, 63, 2, '2026-10-02 20:30:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (184, 160, 1, 63, 81, 1, 3, '2026-09-26 10:46:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (183, 160, 2, 63, 1, 12, 4, '2026-09-26 10:44:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (181, 160, 3, 63, 12, 16, 5, '2026-09-26 10:22:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (182, 160, 4, 63, 12, 1, 3, '2026-09-26 10:23:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (178, 162, 1, 63, 3, 12, 3, '2026-09-25 10:50:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (180, 162, 2, 3, 44, 63, 1, '2026-09-25 11:43:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (179, 162, 3, 12, 63, 1, 3, '2026-09-25 11:15:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (177, 162, 4, 3, 12, 63, 1, '2026-09-25 10:43:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (176, 152, 1, 1, 12, 63, 4, '2026-09-13 12:36:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (175, 152, 2, 12, 1, 3, 7, '2026-09-13 12:28:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (174, 152, 3, 12, 1, 44, 5, '2026-09-12 22:31:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (173, 152, 4, 1, 12, 3, 5, '2026-09-12 15:12:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (171, 154, 1, 12, 16, 1, 3, '2026-09-12 13:59:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (170, 154, 2, 12, 1, 16, 5, '2026-09-12 13:17:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (169, 154, 3, 12, 16, 44, 4, '2026-09-12 12:14:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (172, 154, 4, 12, 1, 16, 5, '2026-09-12 14:11:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (167, 148, 1, 81, 63, 16, 3, '2026-09-06 10:49:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (168, 148, 2, 63, 44, 3, 5, '2026-09-06 12:39:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (165, 148, 3, 63, 16, 44, 2, '2026-09-05 20:59:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (166, 148, 4, 63, 16, 10, 2, '2026-09-06 09:03:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (162, 150, 1, 63, 44, 1, 2, '2026-09-05 12:34:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (163, 150, 2, 44, 3, 1, 0, '2026-09-05 13:14:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (161, 150, 3, 63, 16, 1, 3, '2026-09-05 11:18:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (164, 150, 4, 1, 63, 3, 3, '2026-09-05 13:39:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (159, 144, 1, 63, 1, 12, 5, '2026-08-23 12:41:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (158, 144, 2, 1, 12, 63, 9, '2026-08-23 12:33:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (157, 144, 3, 63, 12, 44, 6, '2026-08-23 09:04:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (156, 144, 4, 1, 12, 81, 6, '2026-08-23 08:56:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (153, 146, 1, 63, 12, 1, 5, '2026-08-22 12:44:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (155, 146, 2, 1, 63, 12, 9, '2026-08-22 13:28:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (152, 146, 3, 12, 1, 63, 5, '2026-08-22 12:41:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (154, 146, 4, 12, 63, 1, 5, '2026-08-22 12:48:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (151, 210, 1, 63, 16, 1, 9, '2026-08-22 08:32:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (150, 210, 2, 63, 1, 12, 7, '2026-08-22 08:30:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (149, 210, 3, 1, 63, 16, 5, '2026-08-21 17:54:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (148, 210, 4, 1, 63, 16, 5, '2026-08-21 17:14:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (144, 211, 1, 12, 1, 16, 6, '2026-08-21 11:35:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (146, 211, 2, 12, 44, 1, 2, '2026-08-21 13:49:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (145, 211, 3, 12, 1, 44, 3, '2026-08-21 12:28:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (147, 211, 4, 12, 3, 16, 3, '2026-08-21 14:11:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (143, 136, 1, 16, 1, 3, 4, '2026-07-26 12:47:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (141, 136, 2, 1, 44, 81, 3, '2026-07-26 12:03:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (139, 136, 3, 1, 44, 16, 5, '2026-07-26 10:58:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (142, 136, 4, 1, 16, 3, 6, '2026-07-26 12:46:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (136, 138, 1, 12, 44, 16, 6, '2026-07-25 13:23:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (135, 138, 2, 12, 44, 1, 4, '2026-07-25 11:46:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (134, 138, 3, 44, 12, 1, 4, '2026-07-25 11:44:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (137, 138, 4, 1, 12, 16, 7, '2026-07-25 13:26:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (130, 132, 1, 12, 63, 3, 6, '2026-07-18 20:30:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (133, 132, 2, 12, 3, 16, 7, '2026-07-19 12:54:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (132, 132, 3, 12, 63, 44, 5, '2026-07-19 10:16:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (131, 132, 4, 12, 3, 16, 7, '2026-07-18 23:45:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (126, 134, 1, 12, 1, 63, 7, '2026-07-18 13:27:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (127, 134, 2, 12, 3, 63, 8, '2026-07-18 13:27:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (129, 134, 3, 12, 63, 44, 4, '2026-07-18 13:51:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (128, 134, 4, 12, 3, 1, 9, '2026-07-18 13:32:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (123, 129, 1, 12, 63, 44, 6, '2026-07-04 21:33:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (125, 129, 2, 12, 44, 1, 4, '2026-07-05 13:39:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (122, 129, 3, 12, 44, 63, 4, '2026-07-04 21:21:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (124, 129, 4, 12, 44, 16, 3, '2026-07-05 13:14:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (120, 130, 1, 12, 63, 44, 7, '2026-07-04 13:47:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (121, 130, 2, 44, 12, 3, 3, '2026-07-04 14:06:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (119, 130, 3, 12, 44, 63, 7, '2026-07-04 11:41:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (118, 130, 4, 12, 44, 1, 5, '2026-07-04 11:30:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (117, 206, 1, 3, 12, 44, 4, '2026-07-04 10:33:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (114, 206, 2, 44, 3, 12, 3, '2026-07-03 19:21:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (116, 206, 3, 44, 12, 3, 4, '2026-07-04 05:50:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (115, 206, 4, 3, 44, 12, 4, '2026-07-03 21:04:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (113, 208, 1, 63, 44, 12, 4, '2026-07-03 15:02:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (112, 208, 2, 44, 12, 63, 7, '2026-07-03 14:14:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (110, 208, 3, 12, 44, 63, 5, '2026-07-03 10:16:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (111, 208, 4, 12, 44, 3, 7, '2026-07-03 13:43:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (109, 126, 1, 63, 16, 44, 4, '2026-06-28 12:31:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (108, 126, 2, 12, 44, 63, 2, '2026-06-28 12:28:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (107, 126, 3, 63, 44, 12, 6, '2026-06-28 09:08:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (106, 126, 4, 16, 63, 44, 3, '2026-06-28 00:15:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (103, 127, 1, 63, 12, 44, 7, '2026-06-27 12:27:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (104, 127, 2, 12, 63, 44, 5, '2026-06-27 13:02:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (102, 127, 3, 12, 44, 63, 3, '2026-06-27 12:05:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (105, 127, 4, 12, 44, 81, 2, '2026-06-27 13:05:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (99, 118, 1, 63, 12, 1, 5, '2026-06-14 11:19:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (101, 118, 2, 12, 44, 63, 4, '2026-06-14 12:31:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (98, 118, 3, 12, 63, 44, 4, '2026-06-14 09:48:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (100, 118, 4, 12, 44, 63, 4, '2026-06-14 12:24:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (94, 120, 1, 63, 12, 1, 7, '2026-06-13 11:32:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (96, 120, 2, 12, 81, 63, 2, '2026-06-13 12:31:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (95, 120, 3, 12, 63, 81, 3, '2026-06-13 12:18:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (97, 120, 4, 12, 1, 16, 2, '2026-06-13 13:14:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (93, 217, 1, 12, 44, 1, 6, '2026-06-07 11:34:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (92, 217, 2, 1, 12, 44, 4, '2026-06-07 11:10:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (91, 217, 3, 12, 1, 44, 5, '2026-06-07 10:31:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (90, 217, 4, 1, 12, 44, 4, '2026-06-07 02:35:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (88, 216, 1, 16, 12, 44, 5, '2026-06-06 11:39:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (89, 216, 2, 12, 16, 44, 7, '2026-06-06 11:48:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (87, 216, 3, 16, 12, 44, 5, '2026-06-06 09:15:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (86, 216, 4, 12, 1, 63, 3, '2026-06-05 10:18:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (84, 108, 1, 63, 1, 81, 0, '2026-05-24 19:35:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (85, 108, 2, 12, 63, 1, 3, '2026-05-24 19:50:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (83, 108, 3, 63, 12, 44, 4, '2026-05-24 13:44:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (82, 108, 4, 12, 63, 1, 3, '2026-05-24 11:57:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (78, 109, 1, 63, 1, 12, 7, '2026-05-23 18:50:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (80, 109, 2, 12, 44, 63, 3, '2026-05-23 19:25:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (79, 109, 3, 63, 1, 1, 8, '2026-05-23 18:54:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (81, 109, 4, 12, 1, 16, 4, '2026-05-23 22:33:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (76, 203, 1, 63, 12, 1, 7, '2026-05-23 14:55:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (75, 203, 2, 12, 1, 63, 5, '2026-05-23 12:49:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (74, 203, 3, 12, 1, 63, 5, '2026-05-23 09:25:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (77, 203, 4, 12, 1, 63, 5, '2026-05-23 14:59:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (72, 204, 1, 12, 63, 1, 7, '2026-05-22 19:20:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (73, 204, 2, 12, 63, 1, 7, '2026-05-22 19:50:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (70, 204, 3, 12, 63, 1, 7, '2026-05-22 10:28:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (71, 204, 4, 1, 12, 1, 7, '2026-05-22 19:03:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (69, 105, 1, 1, 12, 16, 4, '2026-05-03 16:25:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (68, 105, 2, 12, 1, 1, 8, '2026-05-03 14:05:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (66, 105, 3, 16, 12, 1, 4, '2026-05-02 21:59:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (67, 105, 4, 1, 12, 16, 4, '2026-05-03 11:31:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (63, 106, 1, 12, 1, 81, 4, '2026-05-02 18:13:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (64, 106, 2, 1, 12, 81, 2, '2026-05-02 19:00:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (62, 106, 3, 1, 16, 12, 3, '2026-05-02 16:30:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (65, 106, 4, 1, 81, 16, 3, '2026-05-02 19:52:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (60, 116, 1, 1, 81, 16, 9, '2026-05-02 14:51:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (61, 116, 2, 1, 16, 12, 5, '2026-05-02 15:34:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (58, 116, 3, 1, 12, 16, 6, '2026-05-02 09:23:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (59, 116, 4, 12, 1, 81, 4, '2026-05-02 13:03:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (57, 114, 1, 63, 12, 16, 5, '2026-05-01 19:34:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (55, 114, 2, 63, 1, 12, 4, '2026-05-01 19:19:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (54, 114, 3, 16, 63, 1, 1, '2026-05-01 17:33:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (56, 114, 4, 12, 63, 81, 5, '2026-05-01 19:21:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (51, 103, 1, 63, 12, 16, 5, '2026-03-28 20:44:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (53, 103, 2, 12, 81, 63, 8, '2026-03-28 23:05:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (50, 103, 3, 12, 63, 16, 7, '2026-03-28 11:40:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (52, 103, 4, 12, 63, 44, 4, '2026-03-28 21:16:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (47, 104, 1, 63, 44, 12, 3, '2026-03-27 22:05:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (46, 104, 2, 12, 81, 63, 7, '2026-03-27 21:28:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (49, 104, 3, 12, 63, 16, 8, '2026-03-27 22:35:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (48, 104, 4, 63, 12, 16, 6, '2026-03-27 22:12:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (44, 99, 1, 63, 12, 44, 7, '2026-03-14 21:06:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (45, 99, 2, 12, 63, 16, 8, '2026-03-14 21:48:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (43, 99, 3, 63, 16, 12, 4, '2026-03-14 09:54:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (39, 99, 4, 63, 12, 16, 6, '2026-03-14 09:05:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (38, 100, 1, 63, 12, 16, 6, '2026-03-13 21:40:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (36, 100, 2, 63, 12, 44, 7, '2026-03-13 10:24:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (34, 100, 3, 63, 12, 16, 6, '2026-03-13 08:16:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (40, 100, 4, 63, 12, 16, 6, '2026-03-14 09:46:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (37, 102, 1, 63, 12, 44, 6, '2026-03-13 21:39:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (35, 102, 2, 63, 12, 1, 5, '2026-03-13 09:48:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (33, 102, 3, 63, 12, 44, 6, '2026-03-13 08:16:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (42, 102, 4, 63, 12, 16, 5, '2026-03-14 09:51:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (31, 101, 1, 1, 63, 16, 3, '2026-03-12 23:32:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (32, 101, 2, 63, 12, 1, 9, '2026-03-13 06:36:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (30, 101, 3, 63, 16, 12, 5, '2026-03-12 13:39:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (29, 101, 4, 63, 12, 44, 8, '2026-03-12 09:38:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (26, 97, 1, 16, 63, 81, 3, '2026-03-07 19:47:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (28, 97, 2, 63, 16, 81, 5, '2026-03-07 21:37:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (25, 97, 3, 63, 12, 16, 9, '2026-03-07 09:07:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (27, 97, 4, 12, 81, 1, 2, '2026-03-07 19:53:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (22, 98, 1, 63, 81, 16, 5, '2026-03-06 19:59:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (21, 98, 2, 44, 81, 1, 0, '2026-03-06 19:23:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (23, 98, 3, 16, 1, 44, 0, '2026-03-06 20:24:00');
INSERT INTO bets (id, session_id, participant_id, driver1_number, driver2_number, driver3_number, points, created_at) VALUES (24, 98, 4, 1, 16, 12, 3, '2026-03-06 22:11:00');

-- Settings
INSERT INTO settings (key, value) VALUES ('season', '2026');