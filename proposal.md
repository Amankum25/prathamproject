# RC Campus Connect — Module Proposal (Milestone 1)
**Project Title:** Samarth: Bill Tracking System  
**Package:** Finance (RC Campus Connect ERP)  
**Student Name:** [Your Name]  
**Roll Number:** [Your University Roll Number]  
**Assigned Prefix:** `bill_`  
**Target Roles:** Department Staff, Accounts Staff, College Administration  

---

## 1. Problem Statement
In collegiate administration, vendors and departments submit various utility, hardware, library, maintenance, and event-related bills. Currently, tracking whether a bill is lying at the verification desk, awaiting bursar/finance clearance, or disbursed for payment is largely paper-driven. This leads to untracked delays and vendor payment disputes. 

The **Samarth: Bill Tracking Module** digitizes the complete desk-to-desk life cycle of incoming bills, enforcing GST compliance, automatic bill numbering, validation, audit trails, and instant status tracking via AJAX.

---

## 2. User Roles & Desk-to-Desk Workflow
* **Department Staff:** Submits incoming vendor bills with GSTIN, amount, invoice copy references, and purpose; monitors status in real-time.
* **Accounts Staff (Verification Desk):** Performs primary verification (verifying GSTIN format, valid tax invoice dates, department budget sanction); forwards to processing or returns with remarks.
* **Accounts Staff (Payment / Cashier):** Processes verified bills, enters bank/cheque/NEFT reference, marks bills as `paid`, and prints official vouchers.
* **Admin / Principal:** Audits overall ledger, oversees pending turnaround times, and reviews bill movements.

### Life-Cycle Workflow State Machine
$$\text{Received (Submission)} \longrightarrow \text{Verified (Accounts)} \longrightarrow \text{Processing (Bursar/Audit)} \longrightarrow \text{Paid (Disbursed)}$$
$$\text{OR} \longrightarrow \text{Returned (Remarks / Rectification)}$$

*Every transition is handled via secure HTTP POST, cannot skip intermediary states, and creates an immutable log in `bill_movements`.*

---

## 3. Database Architecture (Prefix: `bill_`)
The module uses two isolated relational tables:
1. **`bill_records`**: Stores core invoice attributes (bill number, vendor name, 15-character GSTIN, department, amount, invoice date, current status, current desk, creator).
2. **`bill_movements`**: Immutable desk-to-desk audit trail recording every handover (`bill_id`, `from_desk`, `to_desk`, `action`, `remarks`, `action_by`, timestamp).

---

## 4. Integration with Syllabus Units (GE-5b Requirements)
* **Unit 1 · PHP Basics:** Enums for statuses (`received`, `verified`, `processing`, `paid`, `returned`), department configurations, and loops dynamically building tabular reports.
* **Unit 2 · String Handling Tasks:**
  1. Strict 15-character Indian GSTIN regex validation (`^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$`).
  2. Sequential bill identifier generator (`BILL/2026-27/XXXX`).
  3. Indian Currency Amount-in-Words algorithm (converting figures into verbal descriptions, e.g., *"Forty-Five Thousand Two Hundred Rupees Only"*).
* **Unit 3 · Forms & Server-Side Validation:**
  - GET filter form (filter by vendor, department, date range, and desk status).
  - POST submission form with mandatory server validation (numeric positive amount, bill date $\le$ current date, valid GSTIN, sticky values, CSRF protection, and PRG pattern).
* **Unit 4 · MySQL & CRUD:**
  - Full CRUD operations via MySQLi prepared statements (`prepare()` / `bind_param()`).
  - SQL `JOIN` with shared `users` and `departments` tables to resolve staff names and department descriptions.
* **Unit 5 · jQuery / AJAX & JSON:**
  - Asynchronous tracking feature (`api/track_bill.php`).
  - Typing or selecting a bill number triggers a live AJAX request returning JSON, rendering a visual desk-to-desk progress timeline without page reloads.

