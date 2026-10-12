-- Update all staff passwords to Pass@123 except admin (staffid=1)
UPDATE tblstaff SET password = '$2y$10$QnN1GckfwIBCuBs7usjiferINOLj/JfcNeM2HtRu29DWuFX2xE4VC' WHERE staffid != 1;
