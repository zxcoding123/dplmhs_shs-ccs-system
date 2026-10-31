-- DepEd grading + grade locking. Safe to run more than once.

-- 1. Many pages read students.fullName, which the table no longer has
--    (the gradebook, auto-grader, Class Grades page and student sidebar fail without it).
--    Computed from the name parts, so it can never drift out of sync.
ALTER TABLE `students`
  ADD COLUMN IF NOT EXISTS `fullName` VARCHAR(400)
  AS (CONCAT_WS(' ', `first_name`, NULLIF(`middle_name`, ''), `last_name`)) VIRTUAL;

-- 2. New grade rows start as "not graded yet" instead of the old college failing grade 5
ALTER TABLE `student_grades`
  MODIFY `midterm_grade` TEXT NOT NULL DEFAULT 'N/A',
  MODIFY `final_grade` TEXT NOT NULL DEFAULT 'N/A',
  MODIFY `overall_grade` TEXT NOT NULL DEFAULT 'N/A';

-- 3. Who submitted, accepted, returned or unlocked a class's grades, and why
CREATE TABLE IF NOT EXISTS `grade_reviews` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `class_id` INT(11) NOT NULL,
  `action` ENUM('submit','accept','return','unlock') NOT NULL,
  `reason` VARCHAR(500) NOT NULL DEFAULT '',
  `actor_type` VARCHAR(20) NOT NULL,
  `actor_name` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_class` (`class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
