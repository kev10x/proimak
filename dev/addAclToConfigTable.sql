ALTER TABLE `timesheet_config` 
ADD `aclStopwatch` enum('None', 'Basic', 'Mgr', 'Admin') NOT NULL DEFAULT 'Basic',
ADD `aclDaily` enum('None', 'Basic', 'Mgr', 'Admin') NOT NULL DEFAULT 'Basic',
ADD `aclWeekly` enum('None', 'Basic', 'Mgr', 'Admin') NOT NULL DEFAULT 'Basic',
ADD `aclCalendar` enum('None', 'Basic', 'Mgr', 'Admin') NOT NULL DEFAULT 'Basic',
ADD `aclSimple` enum('None', 'Basic', 'Mgr', 'Admin') NOT NULL DEFAULT 'Basic',
ADD `aclClients` enum('None', 'Basic', 'Mgr', 'Admin') NOT NULL DEFAULT 'Mgr',
ADD `aclProjects` enum('None', 'Basic', 'Mgr', 'Admin') NOT NULL DEFAULT 'Mgr',
ADD `aclTasks` enum('None', 'Basic', 'Mgr', 'Admin') NOT NULL DEFAULT 'Mgr',
ADD `aclReports` enum('None', 'Basic', 'Mgr', 'Admin') NOT NULL DEFAULT 'Mgr';