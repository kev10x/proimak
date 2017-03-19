ALTER TABLE `timesheet_config` 
ADD `startPage` enum('stopwatch', 'daily', 'weekly', 'calendar', 'simple') NOT NULL DEFAULT 'calendar';