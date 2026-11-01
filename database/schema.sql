PRAGMA foreign_keys = ON;
CREATE TABLE IF NOT EXISTS services (
    id INTEGER PRIMARY KEY,
    slug TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    category TEXT NOT NULL,
    price INTEGER NOT NULL CHECK (price > 0),
    duration INTEGER NOT NULL CHECK (duration > 0),
    image TEXT NOT NULL,
    alt TEXT NOT NULL,
    tag TEXT NOT NULL,
    summary TEXT NOT NULL,
    description TEXT NOT NULL,
    includes_json TEXT NOT NULL,
    prepare TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS appointment_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    reference TEXT NOT NULL UNIQUE,
    request_key TEXT NOT NULL UNIQUE,
    service_id INTEGER NOT NULL REFERENCES services(id),
    full_name TEXT NOT NULL,
    email TEXT NOT NULL,
    preferred_date TEXT NOT NULL,
    preferred_time TEXT NOT NULL CHECK (preferred_time IN ('09:00', '10:00', '11:00', '14:00', '15:00', '16:00')),
    quoted_price INTEGER NOT NULL CHECK (quoted_price > 0),
    status TEXT NOT NULL DEFAULT 'requested' CHECK (status = 'requested'),
    created_at TEXT NOT NULL
);
