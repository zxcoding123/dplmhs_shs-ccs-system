-- Files and photos sent in chat. app/helpers/ChatAttachments.php creates this table
-- on first use; this file is for setting it up by hand.
-- scope 'dm' -> messages.id, scope 'group' -> group_messages.id (one attachment per message).
-- Files are stored under uploads/chat/ (denied to the web) and served by chat/file.php.

CREATE TABLE IF NOT EXISTS `chat_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `scope` enum('dm','group') NOT NULL,
  `message_id` bigint(20) NOT NULL,
  `kind` enum('image','file') NOT NULL,
  `stored_name` varchar(120) NOT NULL,       -- path under uploads/chat/
  `thumb_name` varchar(120) DEFAULT NULL,    -- small preview for photos
  `original_name` varchar(255) NOT NULL,
  `mime` varchar(120) NOT NULL,
  `size` int(11) NOT NULL,                   -- bytes on disk (after optimising / gzip)
  `original_size` int(11) NOT NULL,          -- bytes as uploaded
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `gzipped` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_chat_attachment_message` (`scope`, `message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
