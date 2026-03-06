ALTER TABLE access_requests ADD COLUMN is_read TINYINT(1) DEFAULT 0 AFTER status;
