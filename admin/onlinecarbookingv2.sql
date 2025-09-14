-- KAYA clean schema (MariaDB/MySQL 10.4+)
SET FOREIGN_KEY_CHECKS=0;

-- ---------- drop views first ----------
DROP VIEW IF EXISTS v_appointments_calendar;
DROP VIEW IF EXISTS v_trip_history;
DROP VIEW IF EXISTS v_fleet_summary;
DROP VIEW IF EXISTS v_driver_daily_time;

-- ---------- drop tables (children first is not required with FKC=0) ----------
DROP TABLE IF EXISTS telemetry_alerts;
DROP TABLE IF EXISTS telemetry_samples;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS booking_runs;
DROP TABLE IF EXISTS booking_offers;
DROP TABLE IF EXISTS booking_events;
DROP TABLE IF EXISTS vehicle_assignments;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS driver_profile;
DROP TABLE IF EXISTS auth_logins;
DROP TABLE IF EXISTS vehicles;

-- legacy/compat
DROP TABLE IF EXISTS tms_report_media;
DROP TABLE IF EXISTS tms_driver_report;
DROP TABLE IF EXISTS tms_vehicle;
DROP TABLE IF EXISTS tms_user_add_driver;
DROP TABLE IF EXISTS tms_user;
DROP TABLE IF EXISTS tms_syslogs;
DROP TABLE IF EXISTS tms_feedback;
DROP TABLE IF EXISTS tms_pwd_resets;
DROP TABLE IF EXISTS tms_audit_log;
DROP TABLE IF EXISTS tms_admin;
DROP TABLE IF EXISTS login_logs;
DROP TABLE IF EXISTS tms_bookings;

-- =========================
-- Core accounts / vehicles
-- =========================
CREATE TABLE accounts (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  role           ENUM('admin','driver','client') NOT NULL,
  name           VARCHAR(120) NOT NULL,
  email          VARCHAR(190) NOT NULL,
  password_hash  VARCHAR(255) NOT NULL,
  phone          VARCHAR(32) DEFAULT NULL,
  is_active      TINYINT(1) NOT NULL DEFAULT 1,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_accounts_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE auth_logins (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  account_id INT UNSIGNED NOT NULL,
  ip_addr    VARBINARY(16) DEFAULT NULL,
  user_agent VARCHAR(255) DEFAULT NULL,
  login_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_login_account (account_id, login_at),
  CONSTRAINT fk_auth_login_account FOREIGN KEY (account_id) REFERENCES accounts(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE vehicles (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name          VARCHAR(120) NOT NULL,
  plate_no      VARCHAR(64)  NOT NULL,
  category      ENUM('bus','sedan','suv','van','other') NOT NULL DEFAULT 'other',
  seat_capacity SMALLINT UNSIGNED DEFAULT NULL,
  status        ENUM('available','in_use','maintenance','inactive') NOT NULL DEFAULT 'available',
  picture       VARCHAR(255) DEFAULT NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_vehicles_plate (plate_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE vehicle_assignments (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  vehicle_id  INT UNSIGNED NOT NULL,
  driver_id   INT UNSIGNED NOT NULL,
  assigned_by INT UNSIGNED DEFAULT NULL,
  start_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  end_at      DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_va_vehicle (vehicle_id, start_at, end_at),
  KEY idx_va_driver  (driver_id, start_at, end_at),
  KEY fk_va_admin (assigned_by),
  CONSTRAINT fk_va_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_va_driver FOREIGN KEY (driver_id) REFERENCES accounts(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_va_admin FOREIGN KEY (assigned_by) REFERENCES accounts(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- Bookings + workflow
-- =========================
CREATE TABLE bookings (
  id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_type       ENUM('admin','personal') NOT NULL,
  created_by         INT UNSIGNED NOT NULL,  -- admin or driver account id
  client_id          INT UNSIGNED DEFAULT NULL,
  driver_id          INT UNSIGNED DEFAULT NULL,
  vehicle_id         INT UNSIGNED DEFAULT NULL,
  pax                TINYINT UNSIGNED NOT NULL DEFAULT 1,
  contact_name       VARCHAR(120) DEFAULT NULL,
  contact_phone      VARCHAR(32)  DEFAULT NULL,
  pickup_point       VARCHAR(255) NOT NULL,
  dropoff_point      VARCHAR(255) NOT NULL,
  pickup_lat         DECIMAL(10,7) DEFAULT NULL,
  pickup_lng         DECIMAL(10,7) DEFAULT NULL,
  dropoff_lat        DECIMAL(10,7) DEFAULT NULL,
  dropoff_lng        DECIMAL(10,7) DEFAULT NULL,
  scheduled_start_at DATETIME NOT NULL,
  scheduled_end_at   DATETIME DEFAULT NULL,
  status             ENUM('pending','awaiting_driver','accepted','rejected','cancelled','in_progress','completed') NOT NULL DEFAULT 'pending',
  payment_status     ENUM('unpaid','paid','partial') NOT NULL DEFAULT 'unpaid',
  notes              TEXT DEFAULT NULL,
  created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_book_driver  (driver_id, status, scheduled_start_at),
  KEY idx_book_vehicle (vehicle_id, status, scheduled_start_at),
  KEY idx_book_creator (created_by, booking_type),
  KEY idx_book_status_time (status, scheduled_start_at),
  KEY fk_book_client (client_id),
  CONSTRAINT fk_book_creator FOREIGN KEY (created_by) REFERENCES accounts(id) ON UPDATE CASCADE,
  CONSTRAINT fk_book_client  FOREIGN KEY (client_id)  REFERENCES accounts(id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_book_driver  FOREIGN KEY (driver_id)  REFERENCES accounts(id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_book_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE booking_events (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_id INT UNSIGNED NOT NULL,
  actor_id   INT UNSIGNED DEFAULT NULL,
  actor_role ENUM('admin','driver','system') NOT NULL,
  event_type ENUM('create','assign','accept','reject','admin_approve_reject','admin_deny_reject','start_trip','complete_trip','cancel','restore','update') NOT NULL,
  details    LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(details)),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_be_booking (booking_id, created_at),
  KEY fk_be_actor (actor_id),
  CONSTRAINT fk_be_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_be_actor   FOREIGN KEY (actor_id)   REFERENCES accounts(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE booking_offers (
  id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_id         INT UNSIGNED NOT NULL,
  driver_id          INT UNSIGNED NOT NULL,
  offered_by         INT UNSIGNED NOT NULL, -- admin account id
  response           ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  response_at        DATETIME DEFAULT NULL,
  driver_reason      VARCHAR(255) DEFAULT NULL,
  admin_verification ENUM('pending','approved','denied') NOT NULL DEFAULT 'pending',
  verified_by        INT UNSIGNED DEFAULT NULL,
  verified_at        DATETIME DEFAULT NULL,
  verification_notes VARCHAR(255) DEFAULT NULL,
  created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_offer (booking_id, driver_id),
  KEY idx_offer_driver (driver_id, response),
  KEY fk_offer_offered_by (offered_by),
  KEY fk_offer_verified_by (verified_by),
  CONSTRAINT fk_offer_booking    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_offer_driver     FOREIGN KEY (driver_id)  REFERENCES accounts(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_offer_offered_by FOREIGN KEY (offered_by) REFERENCES accounts(id) ON UPDATE CASCADE,
  CONSTRAINT fk_offer_verified_by FOREIGN KEY (verified_by) REFERENCES accounts(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE booking_runs (
  booking_id        INT UNSIGNED NOT NULL,
  vehicle_id        INT UNSIGNED DEFAULT NULL,
  driver_id         INT UNSIGNED DEFAULT NULL,
  pickup_button_at  DATETIME DEFAULT NULL,
  dropoff_button_at DATETIME DEFAULT NULL,
  odo_start_km      DECIMAL(10,1) DEFAULT NULL,
  odo_end_km        DECIMAL(10,1) DEFAULT NULL,
  distance_km       DECIMAL(10,2) DEFAULT NULL,
  fuel_used_liters  DECIMAL(10,2) DEFAULT NULL,
  duration_seconds  INT UNSIGNED DEFAULT NULL,
  PRIMARY KEY (booking_id),
  KEY idx_run_vehicle (vehicle_id),
  KEY idx_run_driver (driver_id),
  CONSTRAINT fk_run_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_run_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_run_driver  FOREIGN KEY (driver_id)  REFERENCES accounts(id)  ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  recipient_id INT UNSIGNED NOT NULL,
  type         ENUM('trip_assigned','trip_rejected','admin_decision','obd_alert','system') NOT NULL,
  payload      LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(payload)),
  is_read      TINYINT(1) NOT NULL DEFAULT 0,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_notif_recipient (recipient_id, is_read, created_at),
  CONSTRAINT fk_notif_recipient FOREIGN KEY (recipient_id) REFERENCES accounts(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE driver_profile (
  account_id     INT UNSIGNED NOT NULL,
  license_no     VARCHAR(64) DEFAULT NULL,
  address        VARCHAR(255) DEFAULT NULL,
  notes          TEXT DEFAULT NULL,
  current_status ENUM('available','on_trip','off') NOT NULL DEFAULT 'available',
  hired_at       DATE DEFAULT NULL,
  PRIMARY KEY (account_id),
  CONSTRAINT fk_driver_profile_account FOREIGN KEY (account_id) REFERENCES accounts(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Telemetry (Shane’s side)
CREATE TABLE telemetry_samples (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_id     INT UNSIGNED DEFAULT NULL,
  vehicle_id     INT UNSIGNED NOT NULL,
  driver_id      INT UNSIGNED DEFAULT NULL,
  recorded_at    DATETIME NOT NULL,
  lat            DECIMAL(10,7) DEFAULT NULL,
  lng            DECIMAL(10,7) DEFAULT NULL,
  speed_kph      DECIMAL(6,2)  DEFAULT NULL,
  fuel_level_pct DECIMAL(5,2)  DEFAULT NULL,
  odometer_km    DECIMAL(10,1) DEFAULT NULL,
  raw_obd        LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(raw_obd)),
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_ts_vehicle_time (vehicle_id, recorded_at),
  KEY idx_ts_booking (booking_id),
  KEY fk_ts_driver (driver_id),
  CONSTRAINT fk_ts_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_ts_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_ts_driver  FOREIGN KEY (driver_id)  REFERENCES accounts(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE telemetry_alerts (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  sample_id  INT UNSIGNED DEFAULT NULL,
  booking_id INT UNSIGNED DEFAULT NULL,
  vehicle_id INT UNSIGNED NOT NULL,
  driver_id  INT UNSIGNED DEFAULT NULL,
  alert_code VARCHAR(40) DEFAULT NULL,
  level      ENUM('info','warn','error','critical') NOT NULL DEFAULT 'warn',
  message    VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_ta_vehicle (vehicle_id, created_at),
  KEY fk_ta_sample (sample_id),
  KEY fk_ta_driver (driver_id),
  KEY fk_ta_booking (booking_id),
  CONSTRAINT fk_ta_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_ta_sample  FOREIGN KEY (sample_id)  REFERENCES telemetry_samples(id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_ta_driver  FOREIGN KEY (driver_id)  REFERENCES accounts(id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_ta_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================
-- Legacy / compat
-- ================
CREATE TABLE tms_admin (
  a_id   INT NOT NULL AUTO_INCREMENT,
  a_name VARCHAR(200) NOT NULL,
  a_email VARCHAR(200) NOT NULL,
  a_pwd  VARCHAR(200) NOT NULL,
  PRIMARY KEY (a_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tms_audit_log (
  id INT NOT NULL AUTO_INCREMENT,
  actor_type ENUM('admin','driver') NOT NULL,
  actor_id INT NOT NULL,
  action VARCHAR(50) NOT NULL,
  booking_u_id INT NOT NULL,
  details LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(details)),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tms_vehicle (
  v_id INT NOT NULL AUTO_INCREMENT,
  v_name VARCHAR(200) NOT NULL,
  v_reg_no VARCHAR(200) NOT NULL,
  v_pass_no VARCHAR(200) NOT NULL,
  v_driver VARCHAR(200) NOT NULL,
  v_category VARCHAR(200) NOT NULL,
  v_dpic VARCHAR(200) NOT NULL,
  v_status VARCHAR(200) NOT NULL,
  PRIMARY KEY (v_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- IMPORTANT: make d_u_id UNSIGNED to match FKs that reference it
CREATE TABLE tms_user_add_driver (
  d_u_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  u_id   INT(50) NOT NULL,
  u_fname VARCHAR(50) NOT NULL,
  u_lname VARCHAR(50) NOT NULL,
  u_phone VARCHAR(32) DEFAULT NULL,
  u_addr  TEXT NOT NULL,
  u_car_type TEXT NOT NULL,
  u_car_regno TEXT NOT NULL,
  u_car_bookdate TEXT NOT NULL,
  u_car_book_status TEXT NOT NULL,
  u_category TEXT NOT NULL,
  u_email TEXT NOT NULL,
  u_pwd   TEXT NOT NULL,
  createdat DATE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (d_u_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tms_user (
  u_id INT NOT NULL AUTO_INCREMENT,
  u_fname VARCHAR(200) NOT NULL,
  u_lname VARCHAR(200) NOT NULL,
  u_car_pax MEDIUMTEXT NOT NULL,
  u_phone VARCHAR(32) DEFAULT NULL,
  u_addr VARCHAR(200) NOT NULL,
  u_category VARCHAR(200) NOT NULL,
  u_email VARCHAR(200) NOT NULL,
  u_pwd VARCHAR(255) NOT NULL,
  u_car_type VARCHAR(200) NOT NULL,
  u_car_driver MEDIUMTEXT NOT NULL,
  u_car_regno VARCHAR(200) NOT NULL,
  u_car_bookdate VARCHAR(200) NOT NULL,
  u_car_pickup VARCHAR(250) NOT NULL,
  u_car_destination VARCHAR(250) NOT NULL,
  u_car_book_status VARCHAR(200) NOT NULL,
  u_car_date MEDIUMTEXT NOT NULL,
  u_car_time MEDIUMTEXT NOT NULL,
  createdat INT(255) NOT NULL DEFAULT UNIX_TIMESTAMP(),
  u_car_createdat INT(255) NOT NULL DEFAULT UNIX_TIMESTAMP(),
  PRIMARY KEY (u_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Driver daily report (kept)
CREATE TABLE tms_driver_report (
  report_id INT NOT NULL AUTO_INCREMENT,
  driver_id INT(10) UNSIGNED NOT NULL,
  vehicle_id INT DEFAULT NULL,
  trip_date DATE NOT NULL,
  shift_start DATETIME DEFAULT NULL,
  shift_end   DATETIME DEFAULT NULL,
  odometer_start INT DEFAULT NULL,
  odometer_end   INT DEFAULT NULL,
  total_km DECIMAL(8,1) DEFAULT NULL,
  fuel_used_liters DECIMAL(8,2) DEFAULT NULL,
  route_from VARCHAR(120) DEFAULT NULL,
  route_to   VARCHAR(120) DEFAULT NULL,
  pickups INT DEFAULT NULL,
  dropoffs INT DEFAULT NULL,
  passengers_moved INT DEFAULT NULL,
  incident_level ENUM('OK','Minor','Major') NOT NULL DEFAULT 'OK',
  status ENUM('Pending','Verified','Rejected') NOT NULL DEFAULT 'Pending',
  notes TEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  verified_by INT DEFAULT NULL,
  verified_at DATETIME DEFAULT NULL,
  PRIMARY KEY (report_id),
  KEY idx_trip_date (trip_date),
  KEY idx_status (status),
  KEY idx_driver (driver_id),
  KEY fk_report_vehicle (vehicle_id),
  CONSTRAINT fk_report_driver FOREIGN KEY (driver_id) REFERENCES tms_user_add_driver(d_u_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_report_vehicle FOREIGN KEY (vehicle_id) REFERENCES tms_vehicle(v_id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tms_report_media (
  media_id INT NOT NULL AUTO_INCREMENT,
  report_id INT NOT NULL,
  path VARCHAR(255) NOT NULL,
  caption VARCHAR(120) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (media_id),
  KEY fk_media_report (report_id),
  CONSTRAINT fk_media_report FOREIGN KEY (report_id) REFERENCES tms_driver_report(report_id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tms_syslogs (
  l_id INT NOT NULL AUTO_INCREMENT,
  u_id VARCHAR(200) NOT NULL,
  u_email VARCHAR(200) NOT NULL,
  u_ip VARBINARY(200) NOT NULL,
  u_city VARCHAR(200) NOT NULL,
  u_country VARCHAR(200) NOT NULL,
  pickup_point MEDIUMTEXT NOT NULL,
  dropoff_point MEDIUMTEXT NOT NULL,
  driver_id MEDIUMTEXT NOT NULL,
  booking_date MEDIUMTEXT NOT NULL,
  u_logintime TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (l_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tms_feedback (
  f_id INT NOT NULL AUTO_INCREMENT,
  f_uname VARCHAR(200) NOT NULL,
  f_content LONGTEXT NOT NULL,
  f_status VARCHAR(200) NOT NULL,
  PRIMARY KEY (f_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tms_pwd_resets (
  r_id INT NOT NULL AUTO_INCREMENT,
  r_email VARCHAR(200) NOT NULL,
  PRIMARY KEY (r_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_logs (
  id INT NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  user_type VARCHAR(50) NOT NULL,
  login_time TEXT NOT NULL,
  createdat DATE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Legacy simple bookings table (still used by some pages)
CREATE TABLE tms_bookings (
  booking_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  client_id  INT UNSIGNED NOT NULL,
  booking_type ENUM('admin','personal') NOT NULL DEFAULT 'admin',
  created_by_driver_id INT(10) UNSIGNED DEFAULT NULL,  -- << matches UNSIGNED driver PK
  driver_id  INT UNSIGNED DEFAULT NULL,
  vehicle_id INT UNSIGNED DEFAULT NULL,
  pickup_point VARCHAR(255) NOT NULL,
  dropoff_point VARCHAR(255) NOT NULL,
  pickup_lat  DECIMAL(10,7) DEFAULT NULL,
  pickup_lng  DECIMAL(10,7) DEFAULT NULL,
  dropoff_lat DECIMAL(10,7) DEFAULT NULL,
  dropoff_lng DECIMAL(10,7) DEFAULT NULL,
  contact_phone VARCHAR(30) DEFAULT NULL,
  seats_reserved TINYINT UNSIGNED DEFAULT 1,
  scheduled_at DATETIME DEFAULT NULL,
  booking_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status ENUM('pending','accepted','declined','cancelled','completed') NOT NULL DEFAULT 'pending',
  payment_status ENUM('unpaid','paid','partial') NOT NULL DEFAULT 'unpaid',
  notes TEXT DEFAULT NULL,
  PRIMARY KEY (booking_id),
  KEY idx_client (client_id),
  KEY idx_status (status),
  KEY idx_driver_status_time (driver_id, status, scheduled_at),
  KEY idx_type_status_time (booking_type, status, scheduled_at),
  KEY idx_created_by (created_by_driver_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- FK from legacy table to legacy driver table (types NOW match)
ALTER TABLE tms_bookings
  ADD CONSTRAINT fk_bookings_driver_creator
    FOREIGN KEY (created_by_driver_id)
    REFERENCES tms_user_add_driver(d_u_id)
    ON DELETE SET NULL ON UPDATE CASCADE;

-- (We do NOT add a FK from tms_bookings.driver_id because your sample data uses ids
-- that don't exist in tms_user_add_driver; leaving it nullable prevents import failures.)

-- ==================
-- Views + Trigger
-- ==================
CREATE VIEW v_appointments_calendar AS
SELECT
  b.id AS booking_id,
  CONCAT(UCASE(b.booking_type),' • ', b.pickup_point,' → ', b.dropoff_point) AS title,
  b.scheduled_start_at AS start_at,
  b.scheduled_end_at   AS end_at,
  b.status, b.driver_id, b.vehicle_id
FROM bookings b;

CREATE VIEW v_trip_history AS
SELECT
  b.id AS booking_id, b.booking_type, b.created_by, b.driver_id, b.vehicle_id,
  b.pax, b.contact_name, b.contact_phone, b.pickup_point, b.dropoff_point,
  b.scheduled_start_at, b.scheduled_end_at, b.status, b.payment_status, b.notes,
  b.created_at, b.updated_at
FROM bookings b
WHERE b.status IN ('completed','cancelled');

CREATE VIEW v_fleet_summary AS
SELECT
  (SELECT COUNT(*) FROM vehicles)                                              AS total_vehicles,
  (SELECT COUNT(*) FROM vehicles WHERE status='available')                     AS vehicles_available,
  (SELECT COUNT(*) FROM vehicles WHERE status='in_use')                        AS vehicles_in_use,
  (SELECT COUNT(*) FROM vehicles WHERE status='maintenance')                   AS vehicles_maintenance,
  (SELECT COUNT(*) FROM vehicles WHERE status='inactive')                      AS vehicles_inactive,
  (SELECT COUNT(*) FROM bookings WHERE DATE(scheduled_start_at)=CURDATE())     AS trips_today,
  (SELECT COUNT(*) FROM bookings WHERE status='in_progress')                   AS trips_in_progress,
  (SELECT COUNT(DISTINCT driver_id) FROM bookings
     WHERE DATE(scheduled_start_at)=CURDATE()
       AND status IN ('accepted','in_progress','completed'))                   AS drivers_active_today;

CREATE VIEW v_driver_daily_time AS
SELECT
  br.driver_id,
  CAST(COALESCE(br.pickup_button_at, br.dropoff_button_at) AS DATE) AS service_date,
  MIN(br.pickup_button_at)  AS time_in,
  MAX(br.dropoff_button_at) AS time_out,
  SUM(br.duration_seconds)  AS total_seconds
FROM booking_runs br
GROUP BY br.driver_id, CAST(COALESCE(br.pickup_button_at, br.dropoff_button_at) AS DATE);

DELIMITER $$
CREATE TRIGGER trg_offer_accept AFTER UPDATE ON booking_offers
FOR EACH ROW
BEGIN
  IF NEW.response='accepted' AND OLD.response <> 'accepted' THEN
    UPDATE bookings SET status='accepted', updated_at=NOW() WHERE id=NEW.booking_id;
    INSERT INTO booking_events(booking_id, actor_id, actor_role, event_type, details)
    VALUES(NEW.booking_id, NEW.driver_id, 'driver', 'accept', JSON_OBJECT('offer_id', NEW.id));
    INSERT INTO notifications(recipient_id, type, payload)
    SELECT offered_by, 'trip_assigned', JSON_OBJECT('booking_id', NEW.booking_id, 'response','accepted')
    FROM booking_offers WHERE id=NEW.id;
  END IF;

  IF NEW.response='rejected' AND OLD.response <> 'rejected' THEN
    UPDATE bookings SET status='rejected', updated_at=NOW() WHERE id=NEW.booking_id;
    INSERT INTO booking_events(booking_id, actor_id, actor_role, event_type, details)
    VALUES(NEW.booking_id, NEW.driver_id, 'driver', 'reject', JSON_OBJECT('offer_id', NEW.id, 'reason', NEW.driver_reason));
    INSERT INTO notifications(recipient_id, type, payload)
    SELECT offered_by, 'trip_rejected', JSON_OBJECT('booking_id', NEW.booking_id, 'reason', NEW.driver_reason)
    FROM booking_offers WHERE id=NEW.id;
  END IF;

  IF NEW.admin_verification='approved' AND OLD.admin_verification <> 'approved' THEN
    UPDATE bookings SET status='cancelled', updated_at=NOW() WHERE id=NEW.booking_id AND status='rejected';
    INSERT INTO booking_events(booking_id, actor_id, actor_role, event_type, details)
    VALUES(NEW.booking_id, NEW.verified_by, 'admin', 'admin_approve_reject', JSON_OBJECT('offer_id', NEW.id));
    INSERT INTO notifications(recipient_id, type, payload)
    SELECT driver_id, 'admin_decision', JSON_OBJECT('booking_id', NEW.booking_id, 'decision','approved')
    FROM booking_offers WHERE id=NEW.id;
  END IF;

  IF NEW.admin_verification='denied' AND OLD.admin_verification <> 'denied' THEN
    UPDATE bookings SET status='awaiting_driver', updated_at=NOW() WHERE id=NEW.booking_id AND status='rejected';
    INSERT INTO booking_events(booking_id, actor_id, actor_role, event_type, details)
    VALUES(NEW.booking_id, NEW.verified_by, 'admin', 'admin_deny_reject', JSON_OBJECT('offer_id', NEW.id));
    INSERT INTO notifications(recipient_id, type, payload)
    SELECT driver_id, 'admin_decision', JSON_OBJECT('booking_id', NEW.booking_id, 'decision','denied')
    FROM booking_offers WHERE id=NEW.id;
  END IF;
END$$
DELIMITER ;

-- ==================
-- Seed minimal data
-- ==================
INSERT INTO accounts(role,name,email,password_hash,is_active) VALUES
('admin','Admin','admin@gmail.com','$2y$10$fAIUbxhK/sEWluSFpNbTUeMeQYjKoToz9anTnD4YK7dOP9u7acJWO',1),
('driver','Shane Lopez','shaaane@mail.com','$dummy$legacy$',1);

INSERT INTO driver_profile(account_id, license_no, address, current_status)
VALUES (2,'123','taga san fernando, pampanga','available');

INSERT INTO vehicles(name,plate_no,category,seat_capacity,status,picture) VALUES
('Euro Bond','CA7766','bus',50,'in_use','images.jpg'),
('Honda Accord','CA2077','bus',5,'in_use',NULL),
('Volkswagen Passat','CA1690','sedan',5,'available','volkswagen-passat-500.jpg'),
('Nissan Rogue','CA1001','suv',7,'available','Nissan_Rogue_SV_2021.jpg'),
('Subaru Legacy','CA7700','bus',5,'available',NULL);

-- Legacy admin
INSERT INTO tms_admin(a_id,a_name,a_email,a_pwd) VALUES
(3,'','admin@gmail.com','$2y$10$fAIUbxhK/sEWluSFpNbTUeMeQYjKoToz9anTnD4YK7dOP9u7acJWO');

-- Legacy driver row (UNSIGNED PK now)
INSERT INTO tms_user_add_driver(d_u_id,u_id,u_fname,u_lname,u_phone,u_addr,u_car_type,u_car_regno,u_car_bookdate,u_car_book_status,u_category,u_email,u_pwd,createdat,is_archived)
VALUES (8,0,'Shane','Lopez','09446872447','taga san fernando, pampanga','Bus','123','', 'Available','Driver','shaaane@mail.com','', CURDATE(), 0);

-- Sample normalized booking (admin-created, awaiting driver)
INSERT INTO bookings(booking_type,created_by,client_id,driver_id,vehicle_id,pax,contact_name,contact_phone,pickup_point,dropoff_point,scheduled_start_at,status)
VALUES ('admin',1,NULL,NULL,NULL,3,NULL,'+639171234567','100 Main St, Town','Airport Terminal 1','2025-08-12 14:00:00','awaiting_driver');

-- Sample legacy booking (kept null created_by_driver_id to satisfy FK)
INSERT INTO tms_bookings(client_id, booking_type, created_by_driver_id, driver_id, vehicle_id, pickup_point, dropoff_point, contact_phone, seats_reserved, scheduled_at, status, payment_status)
VALUES (2,'admin',NULL, NULL, 3,'100 Main St, Town','Airport Terminal 1','+639171234567',3,'2025-08-12 14:00:00','pending','unpaid');

SET FOREIGN_KEY_CHECKS=1;
