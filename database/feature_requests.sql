-- Ideas & Improvements board (ideas.php, managed from admin/ideas_management.php).
-- includes/feature-requests.php creates these tables on first use; this file is for
-- setting them up by hand.
-- author_id / voter_id are the id within the role table (admin.id, staff_accounts.id,
-- students.student_id), so they are stored as text together with the role.
-- vote_count is a cached total of feature_request_votes, kept for sorting.

CREATE TABLE IF NOT EXISTS `feature_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `details` text DEFAULT NULL,
  `category` varchar(20) NOT NULL DEFAULT 'feature',  -- feature | improvement | bug | other
  `status` varchar(20) NOT NULL DEFAULT 'open',       -- open | under_review | planned | in_progress | done | declined
  `author_role` varchar(10) NOT NULL,                 -- admin | staff | student
  `author_id` varchar(50) NOT NULL,
  `author_name` varchar(150) DEFAULT NULL,
  `admin_response` text DEFAULT NULL,
  `responded_at` datetime DEFAULT NULL,
  `vote_count` int(11) NOT NULL DEFAULT 0,
  `is_hidden` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_requests_status` (`status`, `is_hidden`),
  KEY `idx_requests_author` (`author_role`, `author_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `feature_request_votes` (
  `request_id` int(11) NOT NULL,
  `voter_role` varchar(10) NOT NULL,
  `voter_id` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`request_id`, `voter_role`, `voter_id`),
  CONSTRAINT `fk_request_votes_request` FOREIGN KEY (`request_id`) REFERENCES `feature_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
