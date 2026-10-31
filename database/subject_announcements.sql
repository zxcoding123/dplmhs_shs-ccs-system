-- Subject announcements posted by teachers from staff/subject_activities.php
-- and shown on the student dashboard and students/subject_announcements.php.
-- Safe to run more than once.

CREATE TABLE IF NOT EXISTS `subject_announcements` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `subject_id` INT(11) NOT NULL,
  `teacher_id` INT(11) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_subject_announcements_subject` (`subject_id`, `created_at`),
  KEY `idx_subject_announcements_teacher` (`teacher_id`),
  CONSTRAINT `fk_subject_announcements_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `staff_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
