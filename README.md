# RC Campus Connect — module starter

**GE-5b Advanced Web Programming · Ramanujan College, University of Delhi · 2026-27**

The class is building one college ERP, **RC Campus Connect**, modelled on the Samarth eGov ERP and
the DUCC IT services. Each of you builds **one module** (P01–P46) — and some of you one additional
module (A01–A10) — in PHP, MySQL, HTML/CSS and jQuery. At the end the teacher joins all modules into
one portal with one login, one database and one look.

This repository is your starting point. It already gives you:

- the **shared theme** (maroon college colours, buttons, forms, tables, badges, timeline) — `shared/theme.css`
- the **shared header and footer** with the portal menu, the logged-in user and notifications
- the **shared database** with 88 sample users, 48 students, 36 staff, departments, programmes and courses
- a **"switch user" bar** so you can try your module as a student, HOD, accountant … without passwords
- a complete **example module** (Suggestion Box) showing every syllabus unit — copy its patterns
- an **automatic check** that runs on GitHub every time you push

> Your project ID, title and what to build are in the allocation sheet the teacher shared.
> Look at the **style guide** (link in the yellow bar at the top of every page) before writing any HTML.

---

## 1. Set up (once, about 20 minutes)

1. **Install XAMPP** (PHP 8.x) from apachefriends.org. Start **Apache** and **MySQL** in the XAMPP Control Panel.
2. **Install GitHub Desktop** from desktop.github.com and sign in with your GitHub account.
3. **Create your GitHub account with the username `rc` + your university roll number**, e.g.
   `rc24020107008` (github.com → Sign up). The teacher's script finds that account and invites you to
   your own private repository, named after your project and roll number, e.g.
   `campus-sutra/p22-24020107008`. Click **Accept invitation** in the e-mail (it also appears under the
   bell icon on github.com). Already have a GitHub account with another name? Send that name to the teacher instead.
4. On your repository page click **Code → Open with GitHub Desktop**, and set the local path to
   `C:\xampp\htdocs`. Your files are now in a folder like `C:\xampp\htdocs\p22-24020107008`.
5. Open **http://localhost/phpmyadmin**:
   - **New** → database name `rc_campus`, collation `utf8mb4_unicode_ci` → Create
   - select `rc_campus` → **Import** → choose `shared/shared_schema.sql` → Import
   - **Import** again → choose `database.sql` → Import
6. Open **http://localhost/p22-24020107008/** (your own folder name) — you should see the login page.
   Click **pick a user without a password**, choose a student, and the example module opens.

Sample accounts: every password is `Pass@123`. `admin` can open everything; `principal` is the Principal.
Use **switch user** to try any role.

If your MySQL root user has a password, put it in `config.php`.

## 2. Make it yours

1. **`module.json`** is already filled in for you: project ID, title, your name, roll number and table
   prefix (and your additional module, if you have one). Check it; as you add pages, change the `menu`:

   ```json
   {
     "id": "P15",
     "title": "Hostel Accommodation & Room Allocation",
     "student": { "name": "Your Name", "roll_no": "24020568005" },
     "table_prefix": "hostel_",
     "entry": "index.php",
     "menu": [
       { "label": "Rooms", "href": "index.php" },
       { "label": "Apply", "href": "apply.php" },
       { "label": "Warden desk", "href": "warden.php" }
     ],
     "addon": null
   }
   ```

   If you have an additional module (A01–A10), its block looks like this, and its pages go in the `addon/` folder:

   ```json
   "addon": {
     "id": "A04", "title": "Transport & Vehicle Booking", "table_prefix": "transport_",
     "entry": "addon/index.php",
     "menu": [ { "label": "Bookings", "href": "addon/index.php" } ]
   }
   ```

2. Replace the example: delete `add.php`, `view.php`, `api/search.php`, the example functions and the
   `example_suggestions` table, and write your own pages the same way.
3. Commit and push often (GitHub Desktop → write a summary → **Commit to main** → **Push origin**).
   The teacher sees your progress; the check on GitHub tells you if something breaks the rules.

## 3. Rules that make 46 modules work as one

| Rule | Why |
|---|---|
| **Never edit `shared/` or `boot.php`.** | They are replaced with the teacher's copy when modules are joined. Your changes would vanish. The check fails if they differ. |
| Every page starts with `require __DIR__ . '/boot.php';` (`'/../boot.php'` from a sub-folder). | Gives you the session, the database and the helpers, wherever your module is installed. |
| Every page uses `page_start('Title')` … `page_end()` and the theme's classes. | Same header, menu and look on every page of the portal. No colours or fonts of your own. |
| Every table name starts with your `table_prefix`. | 56 modules share one database; prefixes prevent clashes. |
| `database.sql` never creates, alters or fills the shared tables. Read them freely. Only their owner modules (P01 `users`, P03 `departments`/`academic_sessions`/`holidays`, P04 `programmes`/`courses`, P08 `students`, P31 `employees`) change them — through their pages. | Everyone depends on them. Sample data is already there. |
| Links inside your module go through `module_url('page.php')`. | Your folder is `htdocs/<repo>/` on your PC but `modules/P15/` on the portal. |
| SQL only with `prepare()` + `bind_param()`. Output only through `e()`. POST forms carry `csrf_field()` and call `csrf_verify()`. | The security checklist in your report. |
| No real personal data. | Use the sample people, or invent your own. |

## 4. Helpers you get from `shared/`

| Helper | What it does |
|---|---|
| `page_start($title, ['sub' => …, 'actions' => …])`, `page_end()` | Page header and footer |
| `db()` | The mysqli connection (always prepared statements) |
| `e($text)` | Escape for HTML — use on every value you echo |
| `module_url('edit.php?id=3')` | Link to a page of your module |
| `require_login()`, `require_role('warden')`, `has_role('hod', 'principal')` | Access control (admin always passes) |
| `current_user()`, `user_id()`, `user_role()` | The logged-in user |
| `my_student_id()`, `my_employee_id()` | The user's id in `students` / `employees` |
| `is_post()`, `csrf_field()`, `csrf_verify()` | Form handling |
| `redirect($url)`, `flash('ok', 'Saved.')` | Post/Redirect/Get with a message |
| `json_response($data)` | Send JSON from `api/*.php` |
| `status_badge($status)` | Coloured badge for a workflow status |
| `notify($user_id, 'Your room is allotted', 'my_room.php')` | Notification to a user (bell icon) |
| `audit('approve', 'hostel_allocations', $id)` | Record who did what |
| `table_exists('fee_payments')`, `module_link('P25', 'pay.php')` | Use another module's data or pages only if it is installed |
| `can('P15', 'edit')` | Fine-grained permission from P02's `rbac_permissions` (everyone passes until P02 is installed) |

In jQuery: `RC.base + 'api/x.php'` is your module's folder; POSTs made with jQuery send the CSRF token automatically.
`data-confirm="Sure?"` on a button asks before submitting; `data-modal="#id"` opens a modal.

## 5. Shared tables (read-only for you)

`users` (id, username, full_name, email, mobile, role, status) ·
`students` (id, user_id, enrollment_no, full_name, dob, gender, category, programme_id, semester, admission_year, email, mobile) ·
`employees` (id, user_id, emp_code, full_name, designation, department_id, type, gender, dob, date_of_joining, email, mobile) ·
`departments` (id, code, name, type, hod_employee_id) · `academic_sessions` (id, label, start_date, end_date, is_current) ·
`holidays` · `programmes` (id, code, name, department_id, duration_years) ·
`courses` (id, code, title, type, department_id, lecture, tutorial, practical, credits) ·
`notifications` · `audit_log`

Roles: `admin`, `principal`, `hod`, `faculty`, `staff`, `accounts`, `librarian`, `warden`, `it_admin`, `student`, `applicant`.

Your foreign keys may point to your own tables and to these shared tables — **not** to another student's
tables (their module may not be installed). To use another module's data, check `table_exists()` first.

## 6. Folder layout

```
boot.php            ← do not edit
config.php          ← your local database settings
module.json         ← your module's id, title, name, menu
database.sql        ← your tables + at least 10 sample rows each
index.php …         ← your pages
api/                ← PHP files that return JSON for jQuery
includes/           ← your functions (SQL, validation, string handling)
js/app.js           ← your jQuery
css/module.css      ← layout tweaks only
addon/              ← pages of your additional module (A01–A10), if you have one
shared/             ← do not edit: theme, header, footer, login, sample database
tools/              ← the automatic check (php tools/check_module.php)
```

## 7. The class test portal

Everyone's modules also run together online, on one shared database, at the address the teacher shares
in the class group. **About five minutes after you push**, your module is live there at
`…/modules/<your project ID>/` — with the same header, login and data as every other module, so you
can see your pages working with your classmates' (e.g. the student dashboard reading fee payments).

- Enter with your **university roll number** and the **class code** given in class.
- Then use **switch user** (yellow bar) to try your module as a student, HOD, warden, accountant …
- **`…/status.php`** shows every module: live or not, and whether its `database.sql` loaded — with the
  error message if it did not.
- Develop on your own XAMPP; the test portal is for seeing modules work together. Its data is sample
  data and can be reset at any time.

## 8. The automatic check

Every push runs **Module check** (your repository → **Actions** tab). A green tick means:
every PHP file has valid syntax, `module.json` is filled in, `database.sql` follows the table rules and
imports cleanly into MySQL, and `shared/` is untouched. A red cross lists exactly what to fix.
You can run the same check on your PC: `C:\xampp\php\php.exe tools\check_module.php`.

## 9. Problems?

- **"Cannot connect to MySQL"** — start MySQL in XAMPP; check the database is named `rc_campus`.
- **Table doesn't exist** — import `shared/shared_schema.sql` first, then `database.sql`.
- **Page looks unstyled** — the page doesn't start with `require __DIR__ . '/boot.php'` and `page_start()`.
- **Apache won't start** — another program uses port 80; change Apache's port in XAMPP (`Config → httpd.conf`, `Listen 8080`) and open `http://localhost:8080/…`.
