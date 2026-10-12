-- Create default work shift for biometric attendance sync
-- This allows timekeeping page to show actual hours instead of NS

SET FOREIGN_KEY_CHECKS = 0;

-- Step 1: Create default shift type (8am-5pm, 1hr lunch = 8hrs net work)
INSERT INTO `tblshift_type` 
    (`shift_type_name`, `color`, `time_start_work`, `time_end_work`, 
     `start_lunch_break_time`, `end_lunch_break_time`, `description`, `datecreated`, `add_from`)
VALUES 
    ('Standard Shift', '#4CAF50', '08:00', '17:00', 
     '12:00', '13:00', 'Default 8-hour work shift (auto-created for biometric sync)', NOW(), 1);

-- Step 2: Create default work shift (Mon-Fri, covers 2024-2030)
-- shifts_detail: JSON mapping day names to shift_type id
INSERT INTO `tblwork_shift`
    (`shift_code`, `shift_name`, `shift_type`, `add_from`, `date_create`, 
     `from_date`, `to_date`, `shifts_detail`, `type_shiftwork`)
VALUES
    ('WS001', 'Default Work Shift', '1', 1, CURDATE(),
     '2024-01-01', '2030-12-31',
     '{"Mon":1,"Tue":1,"Wed":1,"Thu":1,"Fri":1,"Sat":0,"Sun":0}',
     'day_name');

-- Step 3: Assign ALL active staff to this work shift
-- tblwork_shift_detail: staff_id, date (NULL = applies to all dates), shift_id, work_shift_id
INSERT INTO `tblwork_shift_detail` (`staff_id`, `date`, `shift_id`, `work_shift_id`)
SELECT s.staffid, NULL, 1, 1
FROM `tblstaff` s
WHERE s.active = 1
  AND NOT EXISTS (
    SELECT 1 FROM `tblwork_shift_detail` wsd WHERE wsd.staff_id = s.staffid
  );

SET FOREIGN_KEY_CHECKS = 1;
