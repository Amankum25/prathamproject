# Teacher guide

GitHub Classroom was retired in August 2026, so each student's repository is created directly in the
**campus-sutra** organisation (display name *Campus Connect*) from this private template, with
`tools/student_repos.py`. Needs Python 3 and the GitHub CLI logged in as an organisation owner.

## The student list

`students.csv` (kept outside this repository — it holds names) has one row per student:

```
roll, name, project, title, prefix, addon, addon_title, addon_prefix, github_username
24020107008, ABHINAV JAIN, P22, Library Book Issue & Return, lib_, , , ,
```

Repository names are `<project>-<roll>`, e.g. `campus-sutra/p22-24020107008`.

## 1. Create every repository (once)

```bash
python tools/student_repos.py create students.csv
```

Each repository is a private copy of this template with `module.json` already filled in (project ID,
title, name, roll number, table prefix, additional module) and, for students with an A-module, a
first `addon/index.php`. Re-running is safe.

## 2. Invite students

Students send their GitHub usernames (WhatsApp, a Google Form — anything). Put each one in the
`github_username` column, then:

```bash
python tools/student_repos.py invite students.csv
```

Each student gets an e-mail invitation with **push** access to their own repository only. Run it again
whenever more usernames arrive; already-invited students are left alone.

## 3. Follow progress

```bash
python tools/student_repos.py status students.csv
```

Shows, per student: invitation accepted or pending, number of commits, last push, and whether the
latest Module check passed. As organisation owner you can also open any repository on github.com,
read the code and comment on commits.

## 4. Build the combined portal

```bash
python tools/student_repos.py clone students.csv repos/
php tools/build_portal.php repos portal
```

`build_portal.php` checks each repository with the same rules as the GitHub Action (plus: the example
tables must be gone), copies passing modules to `portal/modules/<ID>/`, always uses this template's
`shared/` and `boot.php`, and writes `portal/install.sql` (shared schema + every module's tables).
It prints which modules were installed and why any were skipped.

On the server (any Apache + PHP 8 + MySQL/MariaDB host — a lab PC with XAMPP works):

1. Copy `portal/` to the web root.
2. Create the database `rc_campus` (utf8mb4_unicode_ci) and import `portal/install.sql`.
3. Edit `portal/config.php`: database user/password; `DEV_MODE` stays `false`.
4. Change the sample passwords, or create real accounts, before anyone outside the class uses it.

Re-run both commands whenever students push fixes (re-importing `install.sql` resets the data).

## Changing shared/ during the semester

Edit `shared/` or `boot.php` here, run `php tools/update_checksums.php`, and push. Student repositories
were copied from the template, so they do not receive the change automatically — announce it and ask
students to copy the new `shared/` folder. The portal build always uses this repository's copy.
