-- PlanningCo database schema
-- MySQL 5.7+ / MariaDB 10.4+
-- Run via the setup wizard or: mysql -u root -p timetable_system < sql/schema.sql

SET FOREIGN_KEY_CHECKS = 0;

-- Departments (color-coded employee groups)
CREATE TABLE IF NOT EXISTS `departments` (
  `id`    INT          NOT NULL AUTO_INCREMENT,
  `name`  VARCHAR(100) NOT NULL,
  `color` VARCHAR(7)   NOT NULL DEFAULT '#6366f1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Users (admins and employees)
CREATE TABLE IF NOT EXISTS `users` (
  `id`              INT          NOT NULL AUTO_INCREMENT,
  `name`            VARCHAR(100) NOT NULL,
  `email`           VARCHAR(150) NOT NULL,
  `password_hash`   VARCHAR(255) NOT NULL,
  `role`            ENUM('admin','employee') NOT NULL DEFAULT 'employee',
  `department_id`   INT          DEFAULT NULL,
  `hpw`             SMALLINT     NOT NULL DEFAULT 35 COMMENT 'Contracted hours per week',
  `failed_attempts` TINYINT      NOT NULL DEFAULT 0,
  `locked_until`    DATETIME     DEFAULT NULL,
  `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`),
  CONSTRAINT `fk_user_dept`
    FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Shift templates
CREATE TABLE IF NOT EXISTS `shifts` (
  `id`         INT          NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100) NOT NULL,
  `start_time` TIME         NOT NULL,
  `end_time`   TIME         NOT NULL,
  `is_night`   BOOLEAN      NOT NULL DEFAULT FALSE,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Schedule assignments (employee × shift × date)
CREATE TABLE IF NOT EXISTS `schedules` (
  `id`        INT  NOT NULL AUTO_INCREMENT,
  `user_id`   INT  NOT NULL,
  `shift_id`  INT  NOT NULL,
  `work_date` DATE NOT NULL,
  `status`    ENUM('assigned','absent','leave','off') NOT NULL DEFAULT 'assigned',
  PRIMARY KEY (`id`),
  INDEX `idx_schedule_user` (`user_id`),
  INDEX `idx_schedule_date` (`work_date`),
  CONSTRAINT `fk_schedule_user`  FOREIGN KEY (`user_id`)  REFERENCES `users`  (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_schedule_shift` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Leave requests
CREATE TABLE IF NOT EXISTS `leave_requests` (
  `id`         INT      NOT NULL AUTO_INCREMENT,
  `user_id`    INT      NOT NULL,
  `start_date` DATE     NOT NULL,
  `end_date`   DATE     NOT NULL,
  `reason`     TEXT     DEFAULT NULL,
  `status`     ENUM('pending','approved','denied') NOT NULL DEFAULT 'pending',
  `CP`         BOOLEAN  NOT NULL DEFAULT FALSE COMMENT 'Congés Payés',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_leave_user` (`user_id`),
  CONSTRAINT `fk_leave_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit log
CREATE TABLE IF NOT EXISTS `logs` (
  `id`         INT       NOT NULL AUTO_INCREMENT,
  `user_id`    INT       DEFAULT NULL,
  `action`     TEXT      DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
