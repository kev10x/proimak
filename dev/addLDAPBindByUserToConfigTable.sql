ALTER TABLE `timesheet_config` 
ADD `LDAPBindByUser` tinyint(4) NOT NULL DEFAULT '0' AFTER `LDAPBindPassword` ;