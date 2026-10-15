-- Gallery photos managed from Admin > Gallery Management.
-- The application also creates this table automatically on first use
-- (includes/gallery-store.php), so running this file manually is optional.

CREATE TABLE IF NOT EXISTS gallery_images (
    id INT(11) NOT NULL AUTO_INCREMENT,
    file_name VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    album VARCHAR(100) NOT NULL DEFAULT 'Campus Life',
    caption TEXT NULL,
    status ENUM('Published','Hidden') NOT NULL DEFAULT 'Published',
    uploaded_by INT(11) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_gallery_status (status),
    KEY idx_gallery_album (album)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
