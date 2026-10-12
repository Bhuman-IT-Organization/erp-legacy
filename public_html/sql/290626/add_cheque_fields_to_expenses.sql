-- Add cheque fields to expenses table
ALTER TABLE tblexpenses 
ADD COLUMN cheque_number VARCHAR(100) NULL,
ADD COLUMN cheque_bank_name VARCHAR(200) NULL,
ADD COLUMN cheque_date DATE NULL;
