-- Profile picture filename for staff (stored in uploads/profile_pictures/).
-- Used by staff/sidebar.php and staff/update_details.php.

ALTER TABLE `staff_accounts`
  ADD COLUMN `avatar` varchar(255) NULL AFTER `gender`;
