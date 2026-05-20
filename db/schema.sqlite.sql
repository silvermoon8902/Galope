-- Galope - esquema de base de datos (SQLite, solo pruebas locales)

CREATE TABLE users (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  name          TEXT NOT NULL,
  email         TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  role          TEXT NOT NULL DEFAULT 'player'  CHECK (role IN ('player','admin')),
  status        TEXT NOT NULL DEFAULT 'active'  CHECK (status IN ('active','suspended')),
  created_at    TEXT NOT NULL
);

CREATE TABLE races (
  id                   INTEGER PRIMARY KEY AUTOINCREMENT,
  name                 TEXT NOT NULL,
  racetrack            TEXT NOT NULL,
  distance_m           INTEGER NOT NULL DEFAULT 0,
  scheduled_at         TEXT NOT NULL,
  predictions_close_at TEXT NOT NULL,
  status               TEXT NOT NULL DEFAULT 'scheduled' CHECK (status IN ('scheduled','open','finished')),
  created_at           TEXT NOT NULL
);

CREATE TABLE horses (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  race_id    INTEGER NOT NULL REFERENCES races(id) ON DELETE CASCADE,
  number     INTEGER NOT NULL,
  name       TEXT NOT NULL,
  jockey     TEXT NOT NULL DEFAULT '',
  form       TEXT NOT NULL DEFAULT '',
  created_at TEXT NOT NULL
);

CREATE TABLE predictions (
  id             INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id        INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  race_id        INTEGER NOT NULL REFERENCES races(id) ON DELETE CASCADE,
  pick1_horse_id INTEGER NOT NULL,
  pick2_horse_id INTEGER NOT NULL,
  pick3_horse_id INTEGER NOT NULL,
  points_awarded INTEGER,
  created_at     TEXT NOT NULL,
  updated_at     TEXT NOT NULL,
  UNIQUE (user_id, race_id)
);

CREATE TABLE race_results (
  id              INTEGER PRIMARY KEY AUTOINCREMENT,
  race_id         INTEGER NOT NULL UNIQUE REFERENCES races(id) ON DELETE CASCADE,
  first_horse_id  INTEGER NOT NULL,
  second_horse_id INTEGER NOT NULL,
  third_horse_id  INTEGER NOT NULL,
  source          TEXT NOT NULL DEFAULT 'manual',
  entered_by      INTEGER,
  entered_at      TEXT NOT NULL
);

CREATE TABLE scoring_rules (
  rule_key    TEXT PRIMARY KEY,
  label       TEXT NOT NULL,
  description TEXT NOT NULL DEFAULT '',
  points      INTEGER NOT NULL DEFAULT 0,
  sort_order  INTEGER NOT NULL DEFAULT 0,
  updated_at  TEXT NOT NULL
);

CREATE TABLE settings (
  setting_key   TEXT PRIMARY KEY,
  setting_value TEXT NOT NULL DEFAULT ''
);

CREATE INDEX idx_horses_race ON horses (race_id);
CREATE INDEX idx_pred_race   ON predictions (race_id);
CREATE INDEX idx_pred_user   ON predictions (user_id);
