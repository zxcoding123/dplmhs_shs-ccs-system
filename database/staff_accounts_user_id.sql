-- Link staff_accounts to the shared users table (used for login).
-- Required by admin/processes/admin/staff/{add,edit,delete}.php,
-- admin/change_status.php and app/services/AuthService.php.

ALTER TABLE `staff_accounts`
  ADD COLUMN `user_id` bigint(20) unsigned NULL AFTER `id`;

-- Create a users row for any staff member that doesn't have one yet
INSERT INTO `users` (email, password, role, status, date_created)
SELECT sa.email, sa.password, 'staff', LOWER(sa.status), sa.datetime_added
FROM `staff_accounts` sa
LEFT JOIN `users` u ON u.email = sa.email
WHERE u.id IS NULL;

-- Link each staff account to its users row
UPDATE `staff_accounts` sa
JOIN `users` u ON u.email = sa.email
SET sa.user_id = u.id;

ALTER TABLE `staff_accounts`
  ADD UNIQUE KEY `staff_accounts_user_id_unique` (`user_id`),
  ADD CONSTRAINT `staff_accounts_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
