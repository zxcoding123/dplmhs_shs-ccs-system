-- School calendar managed from Admin > School Calendar (admin/calendar_management.php).
-- includes/school-calendar.php creates this table on first use and loads the official
-- Philippine holidays and DepEd school-year dates; this file is for setting it up by hand.
-- Official dates have a preset_key. Edited ones get is_modified = 1, and removed ones
-- get is_hidden = 1 so reloading the presets doesn't bring them back.

CREATE TABLE IF NOT EXISTS `school_calendar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `category` varchar(30) NOT NULL,
  `custom_label` varchar(50) DEFAULT NULL,  -- category = 'custom': the admin's own type name
  `color` char(7) DEFAULT NULL,             -- category = 'custom': hex colour, e.g. #475569
  `source` enum('national','deped','school') NOT NULL DEFAULT 'school',
  `no_classes` tinyint(1) NOT NULL DEFAULT 0,
  `is_public` tinyint(1) NOT NULL DEFAULT 1,
  `preset_key` varchar(60) DEFAULT NULL,
  `is_modified` tinyint(1) NOT NULL DEFAULT 0,
  `is_hidden` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_calendar_preset` (`preset_key`),
  KEY `idx_calendar_dates` (`start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
