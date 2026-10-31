-- Prepares csms_system for years of data (MariaDB 10.4+). Safe to run more than once.
--
-- 1. Indexes on the tables that grow every school day. Before this, attendance,
--    notifications, messages, grades and submissions had only a primary key, so every
--    lookup read the whole table. Each index matches the WHERE clauses the code uses.
-- 2. Every table moves to utf8mb4, so names with ñ and emoji in chat are stored
--    reliably (database/connection.php already connects as utf8mb4).
--
-- Rough size for one school (~2,000 students, ~8 classes each, ~180 class days):
-- attendance grows by ~3 million rows a year. MariaDB handles that easily with these
-- indexes; without them, attendance pages slow down month after month.
--
-- Back up first:  mysqldump -u root csms_system > csms_backup.sql
-- Then run:       mysql -u root csms_system < database/optimize_for_scale.sql

-- ---------------------------------------------------------------- 1. Indexes

-- Attendance: by class (+status), by meeting (+status), one student in one meeting
ALTER TABLE `attendance`
  ADD INDEX IF NOT EXISTS `idx_attendance_class` (`class_id`, `status`),
  ADD INDEX IF NOT EXISTS `idx_attendance_meeting` (`meeting_id`, `status`),
  ADD INDEX IF NOT EXISTS `idx_attendance_student` (`student_id`, `meeting_id`);

ALTER TABLE `classes_meetings`
  ADD INDEX IF NOT EXISTS `idx_meetings_class_date` (`class_id`, `date`);

ALTER TABLE `students_enrollments`
  ADD INDEX IF NOT EXISTS `idx_enrollments_class` (`class_id`, `student_id`),
  ADD INDEX IF NOT EXISTS `idx_enrollments_student` (`student_id`);

-- Grades are almost always read for one student in one class
ALTER TABLE `student_grades`
  ADD INDEX IF NOT EXISTS `idx_grades_class_student` (`class_id`, `student_id`);

ALTER TABLE `activities`
  ADD INDEX IF NOT EXISTS `idx_activities_class_type` (`class_id`, `type`(20));

ALTER TABLE `activity_submissions`
  ADD INDEX IF NOT EXISTS `idx_submissions_activity` (`activity_id`, `student_id`),
  ADD INDEX IF NOT EXISTS `idx_submissions_student` (`student_id`);

-- Student number lookups (students.student_id is used in most student queries)
ALTER TABLE `students`
  ADD INDEX IF NOT EXISTS `idx_students_number` (`student_id`);

-- Notifications: a user's list (newest first via the primary key) and unread counts
ALTER TABLE `student_notifications`
  ADD INDEX IF NOT EXISTS `idx_student_notif_user` (`user_id`, `status`);
ALTER TABLE `staff_notifications`
  ADD INDEX IF NOT EXISTS `idx_staff_notif_user` (`user_id`, `status`);
ALTER TABLE `admin_notifications`
  ADD INDEX IF NOT EXISTS `idx_admin_notif_status` (`status`);

-- Chat: status was TEXT (not indexable); the app only writes 'unread' / 'read'
ALTER TABLE `messages`
  MODIFY `status` varchar(10) NOT NULL DEFAULT 'unread',
  ADD INDEX IF NOT EXISTS `idx_messages_pair` (`sender_id`, `sender_type`, `receiver_id`, `receiver_type`),
  ADD INDEX IF NOT EXISTS `idx_messages_inbox` (`receiver_id`, `receiver_type`, `status`);

-- "Remember me" logins look tokens up by hash on every visit
ALTER TABLE `remember_tokens`
  ADD INDEX IF NOT EXISTS `idx_remember_hash` (`token_hash`),
  ADD INDEX IF NOT EXISTS `idx_remember_user` (`user_id`);

-- ---------------------------------------------------------------- 2. utf8mb4

ALTER DATABASE `csms_system` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

ALTER TABLE `activities` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `activity_attachments` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `admin` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `admin_notes` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `admin_notifications` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `admin_reminders` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `archived_semesters` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `attendance` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `class_students` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `classes` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `classes_meetings` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `current_semester` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `learning_resources` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `lectures` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `login_attempts` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `messages` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `remember_tokens` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `semester` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `staff_accounts` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `staff_notifications` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `student_notifications` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `students` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `students_enrollments` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `subjects` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `subjects_meetings` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `subjects_schedules` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `teacher_notes` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `teacher_reminders` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `users` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
