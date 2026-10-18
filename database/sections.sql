-- Sections managed from Admin > Sections (admin/section_management.php).
-- code is STRAND-GRADE+NAME (e.g. STEM-11A) and is what staff_advising.class_advising,
-- classes.name and announcements.class_name store.

CREATE TABLE IF NOT EXISTS `sections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `strand` varchar(20) NOT NULL,
  `grade` char(2) NOT NULL,
  `name` varchar(20) NOT NULL,
  `code` varchar(50) NOT NULL,
  `room` varchar(50) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_section_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Starting sections: A and B for every strand and grade
INSERT IGNORE INTO `sections` (`strand`, `grade`, `name`, `code`) VALUES
('STEM', '11', 'A', 'STEM-11A'), ('STEM', '11', 'B', 'STEM-11B'), ('STEM', '12', 'A', 'STEM-12A'), ('STEM', '12', 'B', 'STEM-12B'),
('ABM', '11', 'A', 'ABM-11A'), ('ABM', '11', 'B', 'ABM-11B'), ('ABM', '12', 'A', 'ABM-12A'), ('ABM', '12', 'B', 'ABM-12B'),
('HUMSS', '11', 'A', 'HUMSS-11A'), ('HUMSS', '11', 'B', 'HUMSS-11B'), ('HUMSS', '12', 'A', 'HUMSS-12A'), ('HUMSS', '12', 'B', 'HUMSS-12B'),
('GAS', '11', 'A', 'GAS-11A'), ('GAS', '11', 'B', 'GAS-11B'), ('GAS', '12', 'A', 'GAS-12A'), ('GAS', '12', 'B', 'GAS-12B'),
('TVL', '11', 'A', 'TVL-11A'), ('TVL', '11', 'B', 'TVL-11B'), ('TVL', '12', 'A', 'TVL-12A'), ('TVL', '12', 'B', 'TVL-12B');
