-- Your module's tables. Import AFTER shared/shared_schema.sql, into the database rc_campus.
-- Module: P27 Bill Tracking System (Roll: 24020568036 - Pratham)
-- Table Prefix: bill_

DROP TABLE IF EXISTS bill_movements;
DROP TABLE IF EXISTS bill_records;

CREATE TABLE bill_records (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bill_no           VARCHAR(50) NOT NULL UNIQUE,
  vendor_name       VARCHAR(150) NOT NULL,
  vendor_gstin      VARCHAR(15) NOT NULL,
  vendor_invoice_no VARCHAR(50) NOT NULL,
  department_id     INT UNSIGNED NOT NULL,
  amount            DECIMAL(12,2) NOT NULL,
  bill_date         DATE NOT NULL,
  description       TEXT NOT NULL,
  status            ENUM('received','verified','processing','paid','returned') NOT NULL DEFAULT 'received',
  current_desk      VARCHAR(100) NOT NULL DEFAULT 'Verification Desk',
  submitted_by      INT UNSIGNED NOT NULL,
  created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        DATETIME NULL,
  FOREIGN KEY (submitted_by) REFERENCES users(id),
  FOREIGN KEY (department_id) REFERENCES departments(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bill_movements (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bill_id     INT UNSIGNED NOT NULL,
  from_desk   VARCHAR(100) NOT NULL,
  to_desk     VARCHAR(100) NOT NULL,
  action      ENUM('submitted','verified','forwarded','processed','paid','returned') NOT NULL,
  remarks     TEXT NULL,
  action_by   INT UNSIGNED NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (bill_id) REFERENCES bill_records(id) ON DELETE CASCADE,
  FOREIGN KEY (action_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10 Realistic Bills mapped to Real Departments and Real Staff in shared_schema.sql
INSERT INTO bill_records (id, bill_no, vendor_name, vendor_gstin, vendor_invoice_no, department_id, amount, bill_date, description, status, current_desk, submitted_by, created_at) VALUES
  (1, 'BILL/2026-27/0001', 'Microsys Solutions India Ltd', '07AAACM3421K1Z2', 'MSI/26/1042', 1, 84500.00, '2026-10-04', 'Supply of 15 HP Laser Printers and toner cartridges for Lab 3.', 'paid', 'Disbursed (Accounts)', 26, '2026-10-04 10:15:00'),
  (2, 'BILL/2026-27/0002', 'Delhi Book Bureau', '07AABFD4412L1ZM', 'DBB-INV-891', 12, 42350.00, '2026-10-08', 'Purchase of 65 reference textbooks for Undergraduate Semester V.', 'paid', 'Disbursed (Accounts)', 25, '2026-10-08 11:00:00'),
  (3, 'BILL/2026-27/0003', 'Apex Scientific Equipment Co', '07AACCA9918F1ZT', 'ASE/2026/410', 1, 125000.00, '2026-10-15', 'Annual maintenance and calibration of laboratory equipment.', 'processing', 'Bursar & Finance Desk', 24, '2026-10-15 09:30:00'),
  (4, 'BILL/2026-27/0004', 'Green Earth Landscaping & Services', '07AAGFG6654N1Z0', 'GE-7781', 10, 28000.00, '2026-10-20', 'Campus garden landscaping, lawn mowing, and tree trimming charges.', 'verified', 'Internal Audit Desk', 23, '2026-10-20 10:45:00'),
  (5, 'BILL/2026-27/0005', 'SpeedNet Telecom Solutions', '07AALCB1190E1ZR', 'SN/DEL/4432', 13, 35400.00, '2026-10-22', 'Monthly optical fibre leased line bandwidth charges for October.', 'processing', 'Accounts Officer Desk', 26, '2026-10-22 11:15:00'),
  (6, 'BILL/2026-27/0006', 'National Stationers & Printers', '07AAHFN7782A1ZS', 'NSP-9081', 10, 18650.00, '2026-10-28', 'Printing of Answer Booklets and examination stationery.', 'returned', 'Department Desk', 24, '2026-10-28 12:00:00'),
  (7, 'BILL/2026-27/0007', 'Supreme Electricals & Hardware', '07AAMCS2231C1Z4', 'SE-2026-55', 10, 64200.00, '2026-11-01', 'Replacement of 120 LED tube lights and circuit breakers in Arts Block.', 'verified', 'Finance Desk', 23, '2026-11-01 10:00:00'),
  (8, 'BILL/2026-27/0008', 'Shree Balaji Hospitality Services', '07AAJCS3342P1ZW', 'SBH/EVT/09', 15, 52000.00, '2026-11-03', 'Catering and refreshment services for Annual Inter-College Sports Meet.', 'received', 'Verification Desk', 36, '2026-11-03 14:20:00'),
  (9, 'BILL/2026-27/0009', 'SecureShield Facility Services', '07AABCS9982H1ZB', 'SS-INV-112', 14, 98000.00, '2026-11-05', 'CCTV surveillance server repair and camera replacement in Hostel.', 'received', 'Verification Desk', 27, '2026-11-05 09:40:00'),
  (10, 'BILL/2026-27/0010', 'Modern Office Furnishers', '07AADCP8811K1ZX', 'MOF/DEL/782', 2, 76500.00, '2026-11-07', 'Supply of 25 ergonomic mesh chairs for Commerce Faculty Staffroom.', 'received', 'Verification Desk', 26, '2026-11-07 15:10:00');

-- Movements Log: Desk-to-Desk Handover Trail
INSERT INTO bill_movements (bill_id, from_desk, to_desk, action, remarks, action_by, created_at) VALUES
  (1, 'Department Desk', 'Verification Desk', 'submitted', 'Bill submitted along with store entry report.', 26, '2026-10-04 10:15:00'),
  (1, 'Verification Desk', 'Finance Desk', 'verified', 'GSTIN verified on portal; physical asset verified in Lab 3.', 29, '2026-10-05 14:30:00'),
  (1, 'Finance Desk', 'Bursar Desk', 'forwarded', 'Sanction within departmental budget allowance.', 30, '2026-10-06 11:20:00'),
  (1, 'Bursar Desk', 'Accounts Cashier', 'processed', 'Sanction approved. Payment released through PFMS/NEFT.', 29, '2026-10-07 16:00:00'),
  (1, 'Accounts Cashier', 'Completed Ledger', 'paid', 'Disbursed via SBI NEFT Ref: UTR202610080098.', 29, '2026-10-08 12:45:00'),
  (2, 'Library Desk', 'Verification Desk', 'submitted', 'Accession register numbers stamped on bill invoice.', 25, '2026-10-08 11:00:00'),
  (2, 'Verification Desk', 'Finance Desk', 'verified', 'Books accession verified with Library Committee minutes.', 29, '2026-10-09 15:10:00'),
  (2, 'Finance Desk', 'Accounts Cashier', 'processed', 'Approved for Cheque issue.', 30, '2026-10-10 10:30:00'),
  (2, 'Accounts Cashier', 'Completed Ledger', 'paid', 'Cheque No. 440219 issued to Delhi Book Bureau.', 29, '2026-10-12 11:45:00'),
  (3, 'Chemistry Dept', 'Verification Desk', 'submitted', 'Quarterly AMC bill for lab spectrophotometers.', 24, '2026-10-15 09:30:00'),
  (3, 'Verification Desk', 'Internal Audit Desk', 'verified', 'AMC agreement dated 01-Apr-2026 verified.', 29, '2026-10-16 12:00:00'),
  (3, 'Internal Audit Desk', 'Bursar Desk', 'forwarded', 'Audit cleared; passed for Rs 1,25,000.', 30, '2026-10-18 14:15:00'),
  (4, 'Admin Office', 'Verification Desk', 'submitted', 'Monthly gardening and lawn maintenance invoice.', 23, '2026-10-20 10:45:00'),
  (4, 'Verification Desk', 'Internal Audit Desk', 'verified', 'Work completion signed by caretaker.', 29, '2026-10-22 16:30:00'),
  (5, 'DUCC IT Desk', 'Verification Desk', 'submitted', 'Internet bandwidth lease bill for Oct 2026.', 26, '2026-10-22 11:15:00'),
  (5, 'Verification Desk', 'Accounts Officer Desk', 'verified', 'SLA uptime report attached (99.8%).', 29, '2026-10-24 14:00:00'),
  (6, 'Exam Cell', 'Verification Desk', 'submitted', 'Stationery printing charges for mid-sem exams.', 24, '2026-10-28 12:00:00'),
  (6, 'Verification Desk', 'Department Desk', 'returned', 'Missing GST tax invoice original copy; Xerox copy not accepted.', 29, '2026-10-29 16:20:00'),
  (7, 'Estate Office', 'Verification Desk', 'submitted', 'Electrical maintenance supplies.', 23, '2026-11-01 10:00:00'),
  (7, 'Verification Desk', 'Finance Desk', 'verified', 'Stock register entry confirmed.', 29, '2026-11-02 11:30:00'),
  (8, 'Sports Office', 'Verification Desk', 'submitted', 'Sports meet debate and hospitality refreshment bill.', 36, '2026-11-03 14:20:00'),
  (9, 'Hostel Office', 'Verification Desk', 'submitted', 'Hostel CCTV maintenance and server repair bill.', 27, '2026-11-05 09:40:00'),
  (10, 'Commerce Dept', 'Verification Desk', 'submitted', 'Staffroom ergonomic chairs delivery invoice.', 26, '2026-11-07 15:10:00');
