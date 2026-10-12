-- Backfill vendornote in POs from PR's rq_description
-- For all POs that have empty vendornote but their linked PR has rq_description
UPDATE tblpur_orders po 
INNER JOIN tblpur_request pr ON pr.id = po.pur_request 
SET po.vendornote = pr.rq_description 
WHERE (po.vendornote IS NULL OR po.vendornote = '') 
AND pr.rq_description IS NOT NULL 
AND pr.rq_description != '';
