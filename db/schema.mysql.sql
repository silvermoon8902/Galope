-- Galope - esquema de base de datos (MySQL 5.7+ / 8.x)

CREATE TABLE users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(120)  NOT NULL,
  email         VARCHAR(190)  NOT NULL UNIQUE,
  password_hash VARCHAR(255)  NOT NULL,
  role          ENUM('player','admin')      NOT NULL DEFAULT 'player',
  status        ENUM('active','suspended')  NOT NULL DEFAULT 'active',
  created_at    DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE races (
  id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name                 VARCHAR(160) NOT NULL,
  racetrack            VARCHAR(160) NOT NULL,
  distance_m           INT UNSIGNED NOT NULL DEFAULT 0,
  scheduled_at         DATETIME NOT NULL,
  predictions_close_at DATETIME NOT NULL,
  status               ENUM('scheduled','open','finished') NOT NULL DEFAULT 'scheduled',
  created_at           DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE horses (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  race_id    INT UNSIGNED NOT NULL,
  number     INT UNSIGNED NOT NULL,
  name       VARCHAR(120) NOT NULL,
  jockey     VARCHAR(120) NOT NULL DEFAULT '',
  form       VARCHAR(40)  NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_horses_race FOREIGN KEY (race_id) REFERENCES races(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE predictions (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id        INT UNSIGNED NOT NULL,
  race_id        INT UNSIGNED NOT NULL,
  pick1_horse_id INT UNSIGNED NOT NULL,
  pick2_horse_id INT UNSIGNED NOT NULL,
  pick3_horse_id INT UNSIGNED NOT NULL,
  points_awarded INT NULL,
  created_at     DATETIME NOT NULL,
  updated_at     DATETIME NOT NULL,
  CONSTRAINT uniq_user_race UNIQUE (user_id, race_id),
  CONSTRAINT fk_pred_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_pred_race FOREIGN KEY (race_id) REFERENCES races(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE race_results (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  race_id         INT UNSIGNED NOT NULL UNIQUE,
  first_horse_id  INT UNSIGNED NOT NULL,
  second_horse_id INT UNSIGNED NOT NULL,
  third_horse_id  INT UNSIGNED NOT NULL,
  source          VARCHAR(20) NOT NULL DEFAULT 'manual',
  entered_by      INT UNSIGNED NULL,
  entered_at      DATETIME NOT NULL,
  CONSTRAINT fk_result_race FOREIGN KEY (race_id) REFERENCES races(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE scoring_rules (
  rule_key    VARCHAR(40)  PRIMARY KEY,
  label       VARCHAR(160) NOT NULL,
  description VARCHAR(255) NOT NULL DEFAULT '',
  points      INT NOT NULL DEFAULT 0,
  sort_order  INT NOT NULL DEFAULT 0,
  updated_at  DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE settings (
  setting_key   VARCHAR(60) PRIMARY KEY,
  setting_value VARCHAR(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_horses_race ON horses (race_id);
CREATE INDEX idx_pred_race   ON predictions (race_id);
CREATE INDEX idx_pred_user   ON predictions (user_id);
