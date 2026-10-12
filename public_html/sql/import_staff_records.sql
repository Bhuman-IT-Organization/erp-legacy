-- =====================================================
-- SQL Import Script for Staff Records
-- Generated for hr_profile/importxlsx
-- =====================================================

-- =====================================================
-- STEP 1: Insert Departments (if not already existing)
-- =====================================================
INSERT INTO `tbldepartments` (`name`) 
SELECT 'Production' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tbldepartments` WHERE `name` = 'Production');

INSERT INTO `tbldepartments` (`name`) 
SELECT 'Supply Chain' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tbldepartments` WHERE `name` = 'Supply Chain');

INSERT INTO `tbldepartments` (`name`) 
SELECT 'Human Resource' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tbldepartments` WHERE `name` = 'Human Resource');

INSERT INTO `tbldepartments` (`name`) 
SELECT 'Finance' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tbldepartments` WHERE `name` = 'Finance');

INSERT INTO `tbldepartments` (`name`) 
SELECT 'Human Resource and Admin' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tbldepartments` WHERE `name` = 'Human Resource and Admin');

INSERT INTO `tbldepartments` (`name`) 
SELECT 'Management Department' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tbldepartments` WHERE `name` = 'Management Department');

INSERT INTO `tbldepartments` (`name`) 
SELECT 'Quality Assurance' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tbldepartments` WHERE `name` = 'Quality Assurance');

INSERT INTO `tbldepartments` (`name`) 
SELECT 'IT' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tbldepartments` WHERE `name` = 'IT');

-- =====================================================
-- STEP 2: Insert Job Positions (if not already existing)
-- =====================================================
INSERT INTO `tblhr_job_position` (`position_name`, `job_position_description`, `job_p_id`, `position_code`)
SELECT 'Production Manager', '', 0, '' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tblhr_job_position` WHERE `position_name` = 'Production Manager');

INSERT INTO `tblhr_job_position` (`position_name`, `job_position_description`, `job_p_id`, `position_code`)
SELECT 'Lead Operator', '', 0, '' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tblhr_job_position` WHERE `position_name` = 'Lead Operator');

INSERT INTO `tblhr_job_position` (`position_name`, `job_position_description`, `job_p_id`, `position_code`)
SELECT 'Warehouse Keeper', '', 0, '' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tblhr_job_position` WHERE `position_name` = 'Warehouse Keeper');

INSERT INTO `tblhr_job_position` (`position_name`, `job_position_description`, `job_p_id`, `position_code`)
SELECT 'Translator', '', 0, '' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tblhr_job_position` WHERE `position_name` = 'Translator');

INSERT INTO `tblhr_job_position` (`position_name`, `job_position_description`, `job_p_id`, `position_code`)
SELECT 'Cook', '', 0, '' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tblhr_job_position` WHERE `position_name` = 'Cook');

INSERT INTO `tblhr_job_position` (`position_name`, `job_position_description`, `job_p_id`, `position_code`)
SELECT 'Procurement Executive', '', 0, '' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tblhr_job_position` WHERE `position_name` = 'Procurement Executive');

INSERT INTO `tblhr_job_position` (`position_name`, `job_position_description`, `job_p_id`, `position_code`)
SELECT 'Human Resource Executive', '', 0, '' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tblhr_job_position` WHERE `position_name` = 'Human Resource Executive');

INSERT INTO `tblhr_job_position` (`position_name`, `job_position_description`, `job_p_id`, `position_code`)
SELECT 'Electrician', '', 0, '' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tblhr_job_position` WHERE `position_name` = 'Electrician');

INSERT INTO `tblhr_job_position` (`position_name`, `job_position_description`, `job_p_id`, `position_code`)
SELECT 'Supervisor', '', 0, '' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tblhr_job_position` WHERE `position_name` = 'Supervisor');

INSERT INTO `tblhr_job_position` (`position_name`, `job_position_description`, `job_p_id`, `position_code`)
SELECT 'Assistant Operator', '', 0, '' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tblhr_job_position` WHERE `position_name` = 'Assistant Operator');

INSERT INTO `tblhr_job_position` (`position_name`, `job_position_description`, `job_p_id`, `position_code`)
SELECT 'Accountant', '', 0, '' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tblhr_job_position` WHERE `position_name` = 'Accountant');

INSERT INTO `tblhr_job_position` (`position_name`, `job_position_description`, `job_p_id`, `position_code`)
SELECT 'General Manager', '', 0, '' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tblhr_job_position` WHERE `position_name` = 'General Manager');

INSERT INTO `tblhr_job_position` (`position_name`, `job_position_description`, `job_p_id`, `position_code`)
SELECT 'Designer', '', 0, '' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tblhr_job_position` WHERE `position_name` = 'Designer');

INSERT INTO `tblhr_job_position` (`position_name`, `job_position_description`, `job_p_id`, `position_code`)
SELECT 'Quality Inspector', '', 0, '' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tblhr_job_position` WHERE `position_name` = 'Quality Inspector');

INSERT INTO `tblhr_job_position` (`position_name`, `job_position_description`, `job_p_id`, `position_code`)
SELECT 'HR & Admin Director', '', 0, '' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tblhr_job_position` WHERE `position_name` = 'HR & Admin Director');

INSERT INTO `tblhr_job_position` (`position_name`, `job_position_description`, `job_p_id`, `position_code`)
SELECT 'HR Assistant cum Traslator', '', 0, '' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tblhr_job_position` WHERE `position_name` = 'HR Assistant cum Traslator');

INSERT INTO `tblhr_job_position` (`position_name`, `job_position_description`, `job_p_id`, `position_code`)
SELECT 'Driver', '', 0, '' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tblhr_job_position` WHERE `position_name` = 'Driver');

INSERT INTO `tblhr_job_position` (`position_name`, `job_position_description`, `job_p_id`, `position_code`)
SELECT 'Assisitant IT Manager', '', 0, '' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tblhr_job_position` WHERE `position_name` = 'Assisitant IT Manager');


-- =====================================================
-- STEP 3: Insert Staff Records
-- Password: '123456a@' hashed with bcrypt
-- sex: 'male' for Male
-- status_work: 'working' for Active
-- active: 1 for Active status
-- =====================================================

-- Staff 42001 - Liu Changquan
INSERT INTO `tblstaff` (`email`, `firstname`, `lastname`, `phonenumber`, `password`, `datecreated`, `admin`, `role`, `active`, `is_not_staff`, `hourly_rate`, `two_factor_auth_enabled`, `birthday`, `sex`, `staff_identifi`, `status_work`, `job_position`, `media_path_slug`) 
VALUES (
    'Liu@gmail.com', 'Liu', 'Changquan', '971123456789',
    '$2a$08$YjRmYWJiNzg4NjRhMjMxOeVJl8PjFVVxrOlN8qOhMqEHnGMa3Ga2e',
    '2025-04-01 00:00:00', 0, NULL, 1, 0, 0.00, 0,
    '2000-01-01', 'male', '42001', 'working',
    (SELECT `position_id` FROM `tblhr_job_position` WHERE `position_name` = 'Production Manager' LIMIT 1),
    'liu-changquan'
);

-- Staff 42003 - Fan Silong
INSERT INTO `tblstaff` (`email`, `firstname`, `lastname`, `phonenumber`, `password`, `datecreated`, `admin`, `role`, `active`, `is_not_staff`, `hourly_rate`, `two_factor_auth_enabled`, `birthday`, `sex`, `staff_identifi`, `status_work`, `job_position`, `media_path_slug`) 
VALUES (
    'Fan@gmail.com', 'Fan', 'Silong', '971123456789',
    '$2a$08$YjRmYWJiNzg4NjRhMjMxOeVJl8PjFVVxrOlN8qOhMqEHnGMa3Ga2e',
    '2025-04-01 00:00:00', 0, NULL, 1, 0, 0.00, 0,
    '2000-01-02', 'male', '42003', 'working',
    (SELECT `position_id` FROM `tblhr_job_position` WHERE `position_name` = 'Lead Operator' LIMIT 1),
    'fan-silong'
);

-- Staff 42004 - Pan Wei
INSERT INTO `tblstaff` (`email`, `firstname`, `lastname`, `phonenumber`, `password`, `datecreated`, `admin`, `role`, `active`, `is_not_staff`, `hourly_rate`, `two_factor_auth_enabled`, `birthday`, `sex`, `staff_identifi`, `status_work`, `job_position`, `media_path_slug`) 
VALUES (
    'Pan@gmail.com', 'Pan', 'Wei', '971123456789',
    '$2a$08$YjRmYWJiNzg4NjRhMjMxOeVJl8PjFVVxrOlN8qOhMqEHnGMa3Ga2e',
    '2025-04-01 00:00:00', 0, NULL, 1, 0, 0.00, 0,
    '2000-01-03', 'male', '42004', 'working',
    (SELECT `position_id` FROM `tblhr_job_position` WHERE `position_name` = 'Warehouse Keeper' LIMIT 1),
    'pan-wei'
);

-- Staff 42007 - Ye Yupeng
INSERT INTO `tblstaff` (`email`, `firstname`, `lastname`, `phonenumber`, `password`, `datecreated`, `admin`, `role`, `active`, `is_not_staff`, `hourly_rate`, `two_factor_auth_enabled`, `birthday`, `sex`, `staff_identifi`, `status_work`, `job_position`, `media_path_slug`) 
VALUES (
    'Ye@gmail.com', 'Ye', 'Yupeng', '971123456789',
    '$2a$08$YjRmYWJiNzg4NjRhMjMxOeVJl8PjFVVxrOlN8qOhMqEHnGMa3Ga2e',
    '2025-04-01 00:00:00', 0, NULL, 1, 0, 0.00, 0,
    '2000-01-04', 'male', '42007', 'working',
    (SELECT `position_id` FROM `tblhr_job_position` WHERE `position_name` = 'Lead Operator' LIMIT 1),
    'ye-yupeng'
);

-- Staff 42008 - Margulan Amantai
INSERT INTO `tblstaff` (`email`, `firstname`, `lastname`, `phonenumber`, `password`, `datecreated`, `admin`, `role`, `active`, `is_not_staff`, `hourly_rate`, `two_factor_auth_enabled`, `birthday`, `sex`, `staff_identifi`, `status_work`, `job_position`, `media_path_slug`) 
VALUES (
    'Margulan@gmail.com', 'Margulan', 'Amantai', '971123456789',
    '$2a$08$YjRmYWJiNzg4NjRhMjMxOeVJl8PjFVVxrOlN8qOhMqEHnGMa3Ga2e',
    '2025-04-01 00:00:00', 0, NULL, 1, 0, 0.00, 0,
    '2000-01-05', 'male', '42008', 'working',
    (SELECT `position_id` FROM `tblhr_job_position` WHERE `position_name` = 'Translator' LIMIT 1),
    'margulan-amantai'
);

-- Staff 42009 - Yang Xiufei
INSERT INTO `tblstaff` (`email`, `firstname`, `lastname`, `phonenumber`, `password`, `datecreated`, `admin`, `role`, `active`, `is_not_staff`, `hourly_rate`, `two_factor_auth_enabled`, `birthday`, `sex`, `staff_identifi`, `status_work`, `job_position`, `media_path_slug`) 
VALUES (
    'Yang@gmail.com', 'Yang', 'Xiufei', '971123456789',
    '$2a$08$YjRmYWJiNzg4NjRhMjMxOeVJl8PjFVVxrOlN8qOhMqEHnGMa3Ga2e',
    '2025-04-01 00:00:00', 0, NULL, 1, 0, 0.00, 0,
    '2000-01-06', 'male', '42009', 'working',
    (SELECT `position_id` FROM `tblhr_job_position` WHERE `position_name` = 'Cook' LIMIT 1),
    'yang-xiufei'
);

-- Staff 42010 - Nikitha Gireesan
INSERT INTO `tblstaff` (`email`, `firstname`, `lastname`, `phonenumber`, `password`, `datecreated`, `admin`, `role`, `active`, `is_not_staff`, `hourly_rate`, `two_factor_auth_enabled`, `birthday`, `sex`, `staff_identifi`, `status_work`, `job_position`, `media_path_slug`) 
VALUES (
    'Nikitha@gmail.com', 'Nikitha', 'Gireesan', '971123456789',
    '$2a$08$YjRmYWJiNzg4NjRhMjMxOeVJl8PjFVVxrOlN8qOhMqEHnGMa3Ga2e',
    '2025-05-01 00:00:00', 0, NULL, 1, 0, 0.00, 0,
    '2000-01-07', 'male', '42010', 'working',
    (SELECT `position_id` FROM `tblhr_job_position` WHERE `position_name` = 'Procurement Executive' LIMIT 1),
    'nikitha-gireesan'
);

-- Staff 42011 - Mohammed Ajmal
INSERT INTO `tblstaff` (`email`, `firstname`, `lastname`, `phonenumber`, `password`, `datecreated`, `admin`, `role`, `active`, `is_not_staff`, `hourly_rate`, `two_factor_auth_enabled`, `birthday`, `sex`, `staff_identifi`, `status_work`, `job_position`, `media_path_slug`) 
VALUES (
    'Mohammed@gmail.com', 'Mohammed', 'Ajmal', '971123456789',
    '$2a$08$YjRmYWJiNzg4NjRhMjMxOeVJl8PjFVVxrOlN8qOhMqEHnGMa3Ga2e',
    '2025-06-18 00:00:00', 0, NULL, 1, 0, 0.00, 0,
    '2000-01-08', 'male', '42011', 'working',
    (SELECT `position_id` FROM `tblhr_job_position` WHERE `position_name` = 'Human Resource Executive' LIMIT 1),
    'mohammed-ajmal'
);

-- Staff 42018 - Liu Shuhua
INSERT INTO `tblstaff` (`email`, `firstname`, `lastname`, `phonenumber`, `password`, `datecreated`, `admin`, `role`, `active`, `is_not_staff`, `hourly_rate`, `two_factor_auth_enabled`, `birthday`, `sex`, `staff_identifi`, `status_work`, `job_position`, `media_path_slug`) 
VALUES (
    'LiuS@gmail.com', 'Liu', 'Shuhua', '971123456789',
    '$2a$08$YjRmYWJiNzg4NjRhMjMxOeVJl8PjFVVxrOlN8qOhMqEHnGMa3Ga2e',
    '2025-07-01 00:00:00', 0, NULL, 1, 0, 0.00, 0,
    '2000-01-09', 'male', '42018', 'working',
    (SELECT `position_id` FROM `tblhr_job_position` WHERE `position_name` = 'Electrician' LIMIT 1),
    'liu-shuhua'
);

-- Staff 42020 - Nong Chunli
INSERT INTO `tblstaff` (`email`, `firstname`, `lastname`, `phonenumber`, `password`, `datecreated`, `admin`, `role`, `active`, `is_not_staff`, `hourly_rate`, `two_factor_auth_enabled`, `birthday`, `sex`, `staff_identifi`, `status_work`, `job_position`, `media_path_slug`) 
VALUES (
    'Nong@gmail.com', 'Nong', 'Chunli', '971123456789',
    '$2a$08$YjRmYWJiNzg4NjRhMjMxOeVJl8PjFVVxrOlN8qOhMqEHnGMa3Ga2e',
    '2025-07-01 00:00:00', 0, NULL, 1, 0, 0.00, 0,
    '2000-01-10', 'male', '42020', 'working',
    (SELECT `position_id` FROM `tblhr_job_position` WHERE `position_name` = 'Supervisor' LIMIT 1),
    'nong-chunli'
);

-- Staff 42021 - Sun Rang
INSERT INTO `tblstaff` (`email`, `firstname`, `lastname`, `phonenumber`, `password`, `datecreated`, `admin`, `role`, `active`, `is_not_staff`, `hourly_rate`, `two_factor_auth_enabled`, `birthday`, `sex`, `staff_identifi`, `status_work`, `job_position`, `media_path_slug`) 
VALUES (
    'Sun@gmail.com', 'Sun', 'Rang', '971123456789',
    '$2a$08$YjRmYWJiNzg4NjRhMjMxOeVJl8PjFVVxrOlN8qOhMqEHnGMa3Ga2e',
    '2025-06-16 00:00:00', 0, NULL, 1, 0, 0.00, 0,
    '2000-01-11', 'male', '42021', 'working',
    (SELECT `position_id` FROM `tblhr_job_position` WHERE `position_name` = 'Lead Operator' LIMIT 1),
    'sun-rang'
);

-- Staff 42022 - Tang Ximing
INSERT INTO `tblstaff` (`email`, `firstname`, `lastname`, `phonenumber`, `password`, `datecreated`, `admin`, `role`, `active`, `is_not_staff`, `hourly_rate`, `two_factor_auth_enabled`, `birthday`, `sex`, `staff_identifi`, `status_work`, `job_position`, `media_path_slug`) 
VALUES (
    'Tang@gmail.com', 'Tang', 'Ximing', '971123456789',
    '$2a$08$YjRmYWJiNzg4NjRhMjMxOeVJl8PjFVVxrOlN8qOhMqEHnGMa3Ga2e',
    '2025-06-16 00:00:00', 0, NULL, 1, 0, 0.00, 0,
    '2000-01-12', 'male', '42022', 'working',
    (SELECT `position_id` FROM `tblhr_job_position` WHERE `position_name` = 'Assistant Operator' LIMIT 1),
    'tang-ximing'
);

-- Staff 42024 - Wen Qilong
INSERT INTO `tblstaff` (`email`, `firstname`, `lastname`, `phonenumber`, `password`, `datecreated`, `admin`, `role`, `active`, `is_not_staff`, `hourly_rate`, `two_factor_auth_enabled`, `birthday`, `sex`, `staff_identifi`, `status_work`, `job_position`, `media_path_slug`) 
VALUES (
    'Wen@gmail.com', 'Wen', 'Qilong', '971123456789',
    '$2a$08$YjRmYWJiNzg4NjRhMjMxOeVJl8PjFVVxrOlN8qOhMqEHnGMa3Ga2e',
    '2025-07-01 00:00:00', 0, NULL, 1, 0, 0.00, 0,
    '2000-01-13', 'male', '42024', 'working',
    (SELECT `position_id` FROM `tblhr_job_position` WHERE `position_name` = 'Lead Operator' LIMIT 1),
    'wen-qilong'
);

-- Staff 42025 - Syed Mohammed Azher Uddin
INSERT INTO `tblstaff` (`email`, `firstname`, `lastname`, `phonenumber`, `password`, `datecreated`, `admin`, `role`, `active`, `is_not_staff`, `hourly_rate`, `two_factor_auth_enabled`, `birthday`, `sex`, `staff_identifi`, `status_work`, `job_position`, `media_path_slug`) 
VALUES (
    'Syed@gmail.com', 'Syed', 'Mohammed Azher Uddin', '971123456789',
    '$2a$08$YjRmYWJiNzg4NjRhMjMxOeVJl8PjFVVxrOlN8qOhMqEHnGMa3Ga2e',
    '2025-08-04 00:00:00', 0, NULL, 1, 0, 0.00, 0,
    '2000-01-14', 'male', '42025', 'working',
    (SELECT `position_id` FROM `tblhr_job_position` WHERE `position_name` = 'Accountant' LIMIT 1),
    'syed-mohammed-azher-uddin'
);

-- Staff 42026 - Dilnar Bakyt
INSERT INTO `tblstaff` (`email`, `firstname`, `lastname`, `phonenumber`, `password`, `datecreated`, `admin`, `role`, `active`, `is_not_staff`, `hourly_rate`, `two_factor_auth_enabled`, `birthday`, `sex`, `staff_identifi`, `status_work`, `job_position`, `media_path_slug`) 
VALUES (
    'Dilnar@gmail.com', 'Dilnar', 'Bakyt', '971123456789',
    '$2a$08$YjRmYWJiNzg4NjRhMjMxOeVJl8PjFVVxrOlN8qOhMqEHnGMa3Ga2e',
    '2025-09-16 00:00:00', 0, NULL, 1, 0, 0.00, 0,
    '2000-01-15', 'male', '42026', 'working',
    (SELECT `position_id` FROM `tblhr_job_position` WHERE `position_name` = 'Translator' LIMIT 1),
    'dilnar-bakyt'
);

-- Staff 42027 - Fang Xidong
INSERT INTO `tblstaff` (`email`, `firstname`, `lastname`, `phonenumber`, `password`, `datecreated`, `admin`, `role`, `active`, `is_not_staff`, `hourly_rate`, `two_factor_auth_enabled`, `birthday`, `sex`, `staff_identifi`, `status_work`, `job_position`, `media_path_slug`) 
VALUES (
    'Fang@gmail.com', 'Fang', 'Xidong', '971123456789',
    '$2a$08$YjRmYWJiNzg4NjRhMjMxOeVJl8PjFVVxrOlN8qOhMqEHnGMa3Ga2e',
    '2025-09-01 00:00:00', 0, NULL, 1, 0, 0.00, 0,
    '2000-01-16', 'male', '42027', 'working',
    (SELECT `position_id` FROM `tblhr_job_position` WHERE `position_name` = 'General Manager' LIMIT 1),
    'fang-xidong'
);

-- Staff 42029 - Yuming Wang
INSERT INTO `tblstaff` (`email`, `firstname`, `lastname`, `phonenumber`, `password`, `datecreated`, `admin`, `role`, `active`, `is_not_staff`, `hourly_rate`, `two_factor_auth_enabled`, `birthday`, `sex`, `staff_identifi`, `status_work`, `job_position`, `media_path_slug`) 
VALUES (
    'Yuming@gmail.com', 'Yuming', 'Wang', '971123456789',
    '$2a$08$YjRmYWJiNzg4NjRhMjMxOeVJl8PjFVVxrOlN8qOhMqEHnGMa3Ga2e',
    '2025-09-19 00:00:00', 0, NULL, 1, 0, 0.00, 0,
    '2000-01-17', 'male', '42029', 'working',
    (SELECT `position_id` FROM `tblhr_job_position` WHERE `position_name` = 'Designer' LIMIT 1),
    'yuming-wang'
);

-- Staff 42030 - li Wanli
INSERT INTO `tblstaff` (`email`, `firstname`, `lastname`, `phonenumber`, `password`, `datecreated`, `admin`, `role`, `active`, `is_not_staff`, `hourly_rate`, `two_factor_auth_enabled`, `birthday`, `sex`, `staff_identifi`, `status_work`, `job_position`, `media_path_slug`) 
VALUES (
    'li@gmail.com', 'li', 'Wanli', '971123456789',
    '$2a$08$YjRmYWJiNzg4NjRhMjMxOeVJl8PjFVVxrOlN8qOhMqEHnGMa3Ga2e',
    '2025-09-19 00:00:00', 0, NULL, 1, 0, 0.00, 0,
    '2000-01-18', 'male', '42030', 'working',
    (SELECT `position_id` FROM `tblhr_job_position` WHERE `position_name` = 'Lead Operator' LIMIT 1),
    'li-wanli'
);

-- Staff 42035 - Lilly Emy Thomas
INSERT INTO `tblstaff` (`email`, `firstname`, `lastname`, `phonenumber`, `password`, `datecreated`, `admin`, `role`, `active`, `is_not_staff`, `hourly_rate`, `two_factor_auth_enabled`, `birthday`, `sex`, `staff_identifi`, `status_work`, `job_position`, `media_path_slug`) 
VALUES (
    'Lilly@gmail.com', 'Lilly', 'Emy Thomas', '971123456789',
    '$2a$08$YjRmYWJiNzg4NjRhMjMxOeVJl8PjFVVxrOlN8qOhMqEHnGMa3Ga2e',
    '2025-12-01 00:00:00', 0, NULL, 1, 0, 0.00, 0,
    '2000-01-19', 'male', '42035', 'working',
    (SELECT `position_id` FROM `tblhr_job_position` WHERE `position_name` = 'Quality Inspector' LIMIT 1),
    'lilly-emy-thomas'
);

-- Staff 42036 - Amrutha Alukkal
INSERT INTO `tblstaff` (`email`, `firstname`, `lastname`, `phonenumber`, `password`, `datecreated`, `admin`, `role`, `active`, `is_not_staff`, `hourly_rate`, `two_factor_auth_enabled`, `birthday`, `sex`, `staff_identifi`, `status_work`, `job_position`, `media_path_slug`) 
VALUES (
    'Amrutha@gmail.com', 'Amrutha', 'Alukkal', '971123456789',
    '$2a$08$YjRmYWJiNzg4NjRhMjMxOeVJl8PjFVVxrOlN8qOhMqEHnGMa3Ga2e',
    '2025-12-01 00:00:00', 0, NULL, 1, 0, 0.00, 0,
    '2000-01-20', 'male', '42036', 'working',
    (SELECT `position_id` FROM `tblhr_job_position` WHERE `position_name` = 'Quality Inspector' LIMIT 1),
    'amrutha-alukkal'
);

-- Staff 42038 - Sasini Sanjula Kumari
INSERT INTO `tblstaff` (`email`, `firstname`, `lastname`, `phonenumber`, `password`, `datecreated`, `admin`, `role`, `active`, `is_not_staff`, `hourly_rate`, `two_factor_auth_enabled`, `birthday`, `sex`, `staff_identifi`, `status_work`, `job_position`, `media_path_slug`) 
VALUES (
    'Sasini@gmail.com', 'Sasini', 'Sanjula Kumari', '971123456789',
    '$2a$08$YjRmYWJiNzg4NjRhMjMxOeVJl8PjFVVxrOlN8qOhMqEHnGMa3Ga2e',
    '2026-01-01 00:00:00', 0, NULL, 1, 0, 0.00, 0,
    '2000-01-21', 'male', '42038', 'working',
    (SELECT `position_id` FROM `tblhr_job_position` WHERE `position_name` = 'Quality Inspector' LIMIT 1),
    'sasini-sanjula-kumari'
);

-- Staff 42039 - Amjad Nezami
INSERT INTO `tblstaff` (`email`, `firstname`, `lastname`, `phonenumber`, `password`, `datecreated`, `admin`, `role`, `active`, `is_not_staff`, `hourly_rate`, `two_factor_auth_enabled`, `birthday`, `sex`, `staff_identifi`, `status_work`, `job_position`, `media_path_slug`) 
VALUES (
    'Amjad@gmail.com', 'Amjad', 'Nezami', '971123456789',
    '$2a$08$YjRmYWJiNzg4NjRhMjMxOeVJl8PjFVVxrOlN8qOhMqEHnGMa3Ga2e',
    '2026-03-02 00:00:00', 0, NULL, 1, 0, 0.00, 0,
    '2000-01-22', 'male', '42039', 'working',
    (SELECT `position_id` FROM `tblhr_job_position` WHERE `position_name` = 'HR & Admin Director' LIMIT 1),
    'amjad-nezami'
);

-- Staff 42040 - Zhannat Meirambay
INSERT INTO `tblstaff` (`email`, `firstname`, `lastname`, `phonenumber`, `password`, `datecreated`, `admin`, `role`, `active`, `is_not_staff`, `hourly_rate`, `two_factor_auth_enabled`, `birthday`, `sex`, `staff_identifi`, `status_work`, `job_position`, `media_path_slug`) 
VALUES (
    'Zhannat@gmail.com', 'Zhannat', 'Meirambay', '971123456789',
    '$2a$08$YjRmYWJiNzg4NjRhMjMxOeVJl8PjFVVxrOlN8qOhMqEHnGMa3Ga2e',
    '2026-03-02 00:00:00', 0, NULL, 1, 0, 0.00, 0,
    '2000-01-23', 'male', '42040', 'working',
    (SELECT `position_id` FROM `tblhr_job_position` WHERE `position_name` = 'HR Assistant cum Traslator' LIMIT 1),
    'zhannat-meirambay'
);

-- Staff 42041 - FIRDAVS ATOBEKOV
INSERT INTO `tblstaff` (`email`, `firstname`, `lastname`, `phonenumber`, `password`, `datecreated`, `admin`, `role`, `active`, `is_not_staff`, `hourly_rate`, `two_factor_auth_enabled`, `birthday`, `sex`, `staff_identifi`, `status_work`, `job_position`, `media_path_slug`) 
VALUES (
    'FIRDAVS@gmail.com', 'FIRDAVS', 'ATOBEKOV', '971123456789',
    '$2a$08$YjRmYWJiNzg4NjRhMjMxOeVJl8PjFVVxrOlN8qOhMqEHnGMa3Ga2e',
    '2026-03-02 00:00:00', 0, NULL, 1, 0, 0.00, 0,
    '2000-01-24', 'male', '42041', 'working',
    (SELECT `position_id` FROM `tblhr_job_position` WHERE `position_name` = 'Driver' LIMIT 1),
    'firdavs-atobekov'
);

-- Staff 42042 - Waseem Muhammed Amin
INSERT INTO `tblstaff` (`email`, `firstname`, `lastname`, `phonenumber`, `password`, `datecreated`, `admin`, `role`, `active`, `is_not_staff`, `hourly_rate`, `two_factor_auth_enabled`, `birthday`, `sex`, `staff_identifi`, `status_work`, `job_position`, `media_path_slug`) 
VALUES (
    'Waseem@gmail.com', 'Waseem', 'Muhammed Amin', '971123456789',
    '$2a$08$YjRmYWJiNzg4NjRhMjMxOeVJl8PjFVVxrOlN8qOhMqEHnGMa3Ga2e',
    '2026-03-02 00:00:00', 0, NULL, 1, 0, 0.00, 0,
    '2000-01-25', 'male', '42042', 'working',
    (SELECT `position_id` FROM `tblhr_job_position` WHERE `position_name` = 'Assisitant IT Manager' LIMIT 1),
    'waseem-muhammed-amin'
);


-- =====================================================
-- STEP 4: Assign Staff to Departments
-- =====================================================

-- 42001 Liu Changquan -> Production
INSERT INTO `tblstaff_departments` (`staffid`, `departmentid`)
SELECT s.staffid, d.departmentid FROM `tblstaff` s, `tbldepartments` d
WHERE s.staff_identifi = '42001' AND d.name = 'Production';

-- 42003 Fan Silong -> Production
INSERT INTO `tblstaff_departments` (`staffid`, `departmentid`)
SELECT s.staffid, d.departmentid FROM `tblstaff` s, `tbldepartments` d
WHERE s.staff_identifi = '42003' AND d.name = 'Production';

-- 42004 Pan Wei -> Supply Chain
INSERT INTO `tblstaff_departments` (`staffid`, `departmentid`)
SELECT s.staffid, d.departmentid FROM `tblstaff` s, `tbldepartments` d
WHERE s.staff_identifi = '42004' AND d.name = 'Supply Chain';

-- 42007 Ye Yupeng -> Production
INSERT INTO `tblstaff_departments` (`staffid`, `departmentid`)
SELECT s.staffid, d.departmentid FROM `tblstaff` s, `tbldepartments` d
WHERE s.staff_identifi = '42007' AND d.name = 'Production';

-- 42008 Margulan Amantai -> Human Resource
INSERT INTO `tblstaff_departments` (`staffid`, `departmentid`)
SELECT s.staffid, d.departmentid FROM `tblstaff` s, `tbldepartments` d
WHERE s.staff_identifi = '42008' AND d.name = 'Human Resource';

-- 42009 Yang Xiufei -> Human Resource
INSERT INTO `tblstaff_departments` (`staffid`, `departmentid`)
SELECT s.staffid, d.departmentid FROM `tblstaff` s, `tbldepartments` d
WHERE s.staff_identifi = '42009' AND d.name = 'Human Resource';

-- 42010 Nikitha Gireesan -> Supply Chain
INSERT INTO `tblstaff_departments` (`staffid`, `departmentid`)
SELECT s.staffid, d.departmentid FROM `tblstaff` s, `tbldepartments` d
WHERE s.staff_identifi = '42010' AND d.name = 'Supply Chain';

-- 42011 Mohammed Ajmal -> Human Resource
INSERT INTO `tblstaff_departments` (`staffid`, `departmentid`)
SELECT s.staffid, d.departmentid FROM `tblstaff` s, `tbldepartments` d
WHERE s.staff_identifi = '42011' AND d.name = 'Human Resource';

-- 42018 Liu Shuhua -> Production
INSERT INTO `tblstaff_departments` (`staffid`, `departmentid`)
SELECT s.staffid, d.departmentid FROM `tblstaff` s, `tbldepartments` d
WHERE s.staff_identifi = '42018' AND d.name = 'Production';

-- 42020 Nong Chunli -> Production
INSERT INTO `tblstaff_departments` (`staffid`, `departmentid`)
SELECT s.staffid, d.departmentid FROM `tblstaff` s, `tbldepartments` d
WHERE s.staff_identifi = '42020' AND d.name = 'Production';

-- 42021 Sun Rang -> Production
INSERT INTO `tblstaff_departments` (`staffid`, `departmentid`)
SELECT s.staffid, d.departmentid FROM `tblstaff` s, `tbldepartments` d
WHERE s.staff_identifi = '42021' AND d.name = 'Production';

-- 42022 Tang Ximing -> Production
INSERT INTO `tblstaff_departments` (`staffid`, `departmentid`)
SELECT s.staffid, d.departmentid FROM `tblstaff` s, `tbldepartments` d
WHERE s.staff_identifi = '42022' AND d.name = 'Production';

-- 42024 Wen Qilong -> Production
INSERT INTO `tblstaff_departments` (`staffid`, `departmentid`)
SELECT s.staffid, d.departmentid FROM `tblstaff` s, `tbldepartments` d
WHERE s.staff_identifi = '42024' AND d.name = 'Production';

-- 42025 Syed Mohammed Azher Uddin -> Finance
INSERT INTO `tblstaff_departments` (`staffid`, `departmentid`)
SELECT s.staffid, d.departmentid FROM `tblstaff` s, `tbldepartments` d
WHERE s.staff_identifi = '42025' AND d.name = 'Finance';

-- 42026 Dilnar Bakyt -> Human Resource and Admin
INSERT INTO `tblstaff_departments` (`staffid`, `departmentid`)
SELECT s.staffid, d.departmentid FROM `tblstaff` s, `tbldepartments` d
WHERE s.staff_identifi = '42026' AND d.name = 'Human Resource and Admin';

-- 42027 Fang Xidong -> Management Department
INSERT INTO `tblstaff_departments` (`staffid`, `departmentid`)
SELECT s.staffid, d.departmentid FROM `tblstaff` s, `tbldepartments` d
WHERE s.staff_identifi = '42027' AND d.name = 'Management Department';

-- 42029 Yuming Wang -> Production
INSERT INTO `tblstaff_departments` (`staffid`, `departmentid`)
SELECT s.staffid, d.departmentid FROM `tblstaff` s, `tbldepartments` d
WHERE s.staff_identifi = '42029' AND d.name = 'Production';

-- 42030 li Wanli -> Production
INSERT INTO `tblstaff_departments` (`staffid`, `departmentid`)
SELECT s.staffid, d.departmentid FROM `tblstaff` s, `tbldepartments` d
WHERE s.staff_identifi = '42030' AND d.name = 'Production';

-- 42035 Lilly Emy Thomas -> Quality Assurance
INSERT INTO `tblstaff_departments` (`staffid`, `departmentid`)
SELECT s.staffid, d.departmentid FROM `tblstaff` s, `tbldepartments` d
WHERE s.staff_identifi = '42035' AND d.name = 'Quality Assurance';

-- 42036 Amrutha Alukkal -> Quality Assurance
INSERT INTO `tblstaff_departments` (`staffid`, `departmentid`)
SELECT s.staffid, d.departmentid FROM `tblstaff` s, `tbldepartments` d
WHERE s.staff_identifi = '42036' AND d.name = 'Quality Assurance';

-- 42038 Sasini Sanjula Kumari -> Quality Assurance
INSERT INTO `tblstaff_departments` (`staffid`, `departmentid`)
SELECT s.staffid, d.departmentid FROM `tblstaff` s, `tbldepartments` d
WHERE s.staff_identifi = '42038' AND d.name = 'Quality Assurance';

-- 42039 Amjad Nezami -> Human Resource and Admin
INSERT INTO `tblstaff_departments` (`staffid`, `departmentid`)
SELECT s.staffid, d.departmentid FROM `tblstaff` s, `tbldepartments` d
WHERE s.staff_identifi = '42039' AND d.name = 'Human Resource and Admin';

-- 42040 Zhannat Meirambay -> Human Resource and Admin
INSERT INTO `tblstaff_departments` (`staffid`, `departmentid`)
SELECT s.staffid, d.departmentid FROM `tblstaff` s, `tbldepartments` d
WHERE s.staff_identifi = '42040' AND d.name = 'Human Resource and Admin';

-- 42041 FIRDAVS ATOBEKOV -> Human Resource and Admin
INSERT INTO `tblstaff_departments` (`staffid`, `departmentid`)
SELECT s.staffid, d.departmentid FROM `tblstaff` s, `tbldepartments` d
WHERE s.staff_identifi = '42041' AND d.name = 'Human Resource and Admin';

-- 42042 Waseem Muhammed Amin -> IT
INSERT INTO `tblstaff_departments` (`staffid`, `departmentid`)
SELECT s.staffid, d.departmentid FROM `tblstaff` s, `tbldepartments` d
WHERE s.staff_identifi = '42042' AND d.name = 'IT';

-- =====================================================
-- NOTES:
-- 1. Default password for all staff: 123456a@ (bcrypt hashed)
-- 2. All staff are set as Active (active=1, status_work='working')
-- 3. Staff 42018 (Liu Shuhua) email changed to LiuS@gmail.com to avoid duplicate with 42001
-- 4. datecreated = Date of Joining from the spreadsheet
-- 5. job_position references tblhr_job_position.position_id via subquery
-- 6. Departments are linked via tblstaff_departments junction table
-- =====================================================
