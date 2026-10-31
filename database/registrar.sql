-- Registrar role: accounts, student LRN and school-year enrollment records.
-- Safe to run more than once.

-- 1. New login role
ALTER TABLE `users`
  MODIFY `role` ENUM('admin','staff','student','registrar') NOT NULL;

-- 2. Registrar profile (email, password and active/inactive live in `users`)
CREATE TABLE IF NOT EXISTS `registrar_accounts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT(20) UNSIGNED NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `middle_name` VARCHAR(100) NOT NULL DEFAULT '',
  `last_name` VARCHAR(100) NOT NULL,
  `fullName` VARCHAR(255) NOT NULL,
  `phone_number` VARCHAR(20) NOT NULL DEFAULT '',
  `gender` VARCHAR(20) NOT NULL DEFAULT '',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_registrar_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. DepEd Learner Reference Number (12 digits), optional until the registrar fills it in
ALTER TABLE `students`
  ADD COLUMN IF NOT EXISTS `lrn` VARCHAR(12) NULL DEFAULT NULL AFTER `student_id`;
CREATE UNIQUE INDEX IF NOT EXISTS `uniq_students_lrn` ON `students` (`lrn`);

-- 4. One row per student per school year and term.
--    student_row_id is students.id (the row key, as in students_advising).
--    school_year is "2026-2027"; term is "1st Semester" / "2nd Semester".
--    semester_id points to the admin's semester row when there is one; the
--    school_year + term text keeps the record readable if that row is deleted.
CREATE TABLE IF NOT EXISTS `enrollment_records` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `student_row_id` INT(11) NOT NULL,
  `school_year` VARCHAR(9) NOT NULL,
  `term` VARCHAR(30) NOT NULL,
  `semester_id` INT(11) NULL DEFAULT NULL,
  `strand` VARCHAR(20) NOT NULL,
  `grade_level` VARCHAR(20) NOT NULL,
  `section_code` VARCHAR(50) NULL DEFAULT NULL,
  `admission_type` ENUM('new','continuing','transferee','returnee') NOT NULL DEFAULT 'continuing',
  `status` ENUM('enrolled','completed','dropped','transferred_out','graduated') NOT NULL DEFAULT 'enrolled',
  `remarks` VARCHAR(255) NOT NULL DEFAULT '',
  `recorded_by_type` VARCHAR(20) NOT NULL DEFAULT '',
  `recorded_by_name` VARCHAR(255) NOT NULL DEFAULT '',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_student_term` (`student_row_id`, `school_year`, `term`),
  KEY `idx_term` (`school_year`, `term`),
  KEY `idx_section` (`section_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. Documents students ask the registrar for (certificates, SF9, SF10)
CREATE TABLE IF NOT EXISTS `document_requests` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `student_row_id` INT(11) NOT NULL,
  `document` ENUM('coe','good_moral','sf9','sf10') NOT NULL,
  `school_year` VARCHAR(9) NULL DEFAULT NULL,
  `purpose` VARCHAR(100) NOT NULL,
  `copies` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `notes` VARCHAR(500) NOT NULL DEFAULT '',
  `status` ENUM('pending','processing','ready','released','declined') NOT NULL DEFAULT 'pending',
  `registrar_note` VARCHAR(500) NOT NULL DEFAULT '',
  `handled_by` VARCHAR(255) NOT NULL DEFAULT '',
  `requested_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_student` (`student_row_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 6. Student IDs are assigned by the system (S.Y. start year + 5-digit sequence,
--    e.g. 202600001) and must never repeat: chat, attendance and grades key on them.
CREATE UNIQUE INDEX IF NOT EXISTS `uniq_students_number` ON `students` (`student_id`);
