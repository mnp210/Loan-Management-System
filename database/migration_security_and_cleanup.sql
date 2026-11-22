-- ======================================================================
-- LOAN MANAGEMENT SYSTEM
-- Database Migration: Security Hardening & Template Cleanup
-- ======================================================================

-- 1. Safely remove legacy clinic/doctor artifact 'doctor_id' if present
SET @dbname = DATABASE();
SET @tablename = "users";
SET @columnname = "doctor_id";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  "ALTER TABLE `users` DROP COLUMN `doctor_id`;",
  "SELECT 1;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 2. Upgrade default seed admin password to bcrypt hash (admin123)
UPDATE `users` 
SET `password` = '$2y$10$Bi/g6XN8.IjGx708YnYQd.iiW3Y4aMjgTlmCoVVzfS.JVxTTiDKk.' 
WHERE `username` = 'admin' AND `password` = 'admin123';

-- 3. Add performance indexes (idempotent / safe)
DELIMITER $$
CREATE PROCEDURE AddIndexIfNotExist(IN tblName VARCHAR(64), IN idxName VARCHAR(64), IN colList VARCHAR(128))
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = tblName AND INDEX_NAME = idxName
  ) THEN
    SET @s = CONCAT('ALTER TABLE `', tblName, '` ADD INDEX `', idxName, '` (', colList, ')');
    PREPARE stmt FROM @s;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END$$
DELIMITER ;

CALL AddIndexIfNotExist('loan_list', 'idx_borrower_id', 'borrower_id');
CALL AddIndexIfNotExist('loan_list', 'idx_plan_id', 'plan_id');
CALL AddIndexIfNotExist('loan_list', 'idx_loan_status', 'status');
CALL AddIndexIfNotExist('loan_schedules', 'idx_loan_schedule', 'loan_id, date_due');
CALL AddIndexIfNotExist('payments', 'idx_payment_loan', 'loan_id');

DROP PROCEDURE IF EXISTS AddIndexIfNotExist;
