ALTER TABLE `timesheet_assignments` ADD `rate_id` INT DEFAULT '0' NOT NULL ;
ALTER TABLE `timesheet_user` DROP `allowed_realms` ;
ALTER TABLE `timesheet_user` DROP `bill_rate` ;
ALTER TABLE `timesheet_user` DROP `phone` ;