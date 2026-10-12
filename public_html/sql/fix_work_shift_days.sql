-- Populate work shift day details
-- number: 1=Mon, 2=Tue, 3=Wed, 4=Thu, 5=Fri, 6=Sat, 7=Sun
-- staff_id=0 means applies to all staff
-- shift_id=1 = Standard Shift type
-- work_shift_id=1 = Default Work Shift

SET FOREIGN_KEY_CHECKS = 0;

-- Clear any existing entries for work_shift_id=1
DELETE FROM `tblwork_shift_detail_number_day` WHERE `work_shift_id` = 1;
DELETE FROM `tblwork_shift_detail_day_name` WHERE `work_shift_id` = 1;

-- Insert Mon-Fri into number_day table (used by timekeeping page)
INSERT INTO `tblwork_shift_detail_number_day` (`staff_id`, `number`, `shift_id`, `work_shift_id`) VALUES
(0, 1, 1, 1),  -- Monday
(0, 2, 1, 1),  -- Tuesday
(0, 3, 1, 1),  -- Wednesday
(0, 4, 1, 1),  -- Thursday
(0, 5, 1, 1);  -- Friday

-- Insert Mon-Fri into day_name table as well
INSERT INTO `tblwork_shift_detail_day_name` (`staff_id`, `day_name`, `shift_id`, `work_shift_id`) VALUES
(0, 'Mon', 1, 1),
(0, 'Tue', 1, 1),
(0, 'Wed', 1, 1),
(0, 'Thu', 1, 1),
(0, 'Fri', 1, 1);

-- Update work_shift to use 'by_number_day' type so the correct detail table is used
UPDATE `tblwork_shift` SET `type_shiftwork` = 'by_number_day' WHERE `id` = 1;

SET FOREIGN_KEY_CHECKS = 1;
