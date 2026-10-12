#!/usr/bin/env python3
"""
TEACHER TOOL — one private repository per student in the campus-sutra organisation.
Needs Python 3 and the GitHub CLI (`gh auth login` as an organisation owner).

    python tools/student_repos.py create  students.csv   # make every repo from the template, module.json pre-filled
    python tools/student_repos.py invite  students.csv   # invite each student (github_username column) with push access
    python tools/student_repos.py status  students.csv   # invitation accepted? last push? check green?
    python tools/student_repos.py clone   students.csv repos/   # clone or update every repo (for tools/build_portal.php)

students.csv columns:
    roll, name, project, title, prefix, addon, addon_title, addon_prefix, github_username
(addon columns empty if the student has no additional module; github_username filled in as students send it)
Safe to re-run: existing repositories and invitations are left as they are.
"""
import base64, csv, json, os, subprocess, sys, time

ORG = "campus-sutra"
TEMPLATE = f"{ORG}/rc-campus-connect-starter"


def gh(*args, input=None, check=True):
    r = subprocess.run(["gh", *args], input=input, capture_output=True, text=True, encoding="utf-8")
    if check and r.returncode != 0:
        raise RuntimeError(r.stderr.strip() or r.stdout.strip())
    return r


def api(method, path, body=None, check=True):
    args = ["api", "-X", method, path, "-H", "Accept: application/vnd.github+json"]
    if body is not None:
        args += ["--input", "-"]
    r = gh(*args, input=json.dumps(body) if body is not None else None, check=check)
    return json.loads(r.stdout) if r.stdout.strip() else None


def repo_name(row):
    return f"{row['project'].lower()}-{row['roll']}"


def title_case(name):
    return " ".join(w.capitalize() for w in name.split())


def read_rows(path):
    with open(path, newline="", encoding="utf-8-sig") as f:
        return [r for r in csv.DictReader(f) if r.get("roll")]


def exists(full):
    return gh("api", f"repos/{full}", check=False).returncode == 0


def module_json(row):
    m = {
        "id": row["project"],
        "title": row["title"],
        "student": {"name": title_case(row["name"]), "roll_no": row["roll"]},
        "table_prefix": row["prefix"],
        "entry": "index.php",
        "menu": [
            {"label": "Suggestions (example)", "href": "index.php"},
            {"label": "New suggestion (example)", "href": "add.php"},
        ],
        "addon": None,
    }
    if row.get("addon"):
        m["addon"] = {
            "id": row["addon"], "title": row["addon_title"], "table_prefix": row["addon_prefix"],
            "entry": "addon/index.php", "menu": [{"label": row["addon_title"], "href": "addon/index.php"}],
        }
    return json.dumps(m, indent=2, ensure_ascii=False) + "\n"


ADDON_PAGE = """<?php
/* First page of your additional module ({id} · {title}). Replace this with your own pages. */
require __DIR__ . '/../boot.php';
require_login();
page_start('{title}', ['sub' => 'Additional module {id}']);
?>
<div class="empty"><h2>Work in progress</h2><p>Build the {title} pages in this addon/ folder.</p></div>
<?php page_end();
"""


def put_file(full, path, text, message):
    cur = api("GET", f"repos/{full}/contents/{path}", check=False)
    body = {"message": message, "content": base64.b64encode(text.encode("utf-8")).decode("ascii")}
    if isinstance(cur, dict) and cur.get("sha"):
        if base64.b64decode(cur["content"]).decode("utf-8") == text:
            return False
        body["sha"] = cur["sha"]
    api("PUT", f"repos/{full}/contents/{path}", body)
    return True


def create(rows):
    for row in rows:
        full = f"{ORG}/{repo_name(row)}"
        if not exists(full):
            gh("repo", "create", full, "--private", "--template", TEMPLATE,
               "--description", f"{row['project']} · {row['title']}")
            for _ in range(30):                      # the template copy finishes in the background
                if api("GET", f"repos/{full}/contents/module.json", check=False):
                    break
                time.sleep(2)
            made = "created"
        else:
            made = "exists "
        changed = put_file(full, "module.json", module_json(row),
                           f"Set up module {row['project']} for {title_case(row['name'])}")
        if row.get("addon"):
            put_file(full, "addon/index.php", ADDON_PAGE.format(id=row["addon"], title=row["addon_title"]),
                     f"Add a first page for additional module {row['addon']}")
        print(f"{made}  {full:<40} {'module.json updated' if changed else 'module.json ok'}")


def invite(rows):
    for row in rows:
        user = (row.get("github_username") or "").strip().lstrip("@")
        full = f"{ORG}/{repo_name(row)}"
        if not user:
            print(f"waiting  {full:<40} no GitHub username yet")
            continue
        if gh("api", f"users/{user}", check=False).returncode != 0:
            print(f"ERROR    {full:<40} GitHub user '{user}' does not exist")
            continue
        r = gh("api", "-X", "PUT", f"repos/{full}/collaborators/{user}", "-f", "permission=push", check=False)
        if r.returncode != 0:
            print(f"ERROR    {full:<40} {r.stderr.strip()}")
        else:
            print(f"{'invited ' if r.stdout.strip() else 'member  '} {full:<40} {user}")


def status(rows):
    print(f"{'repository':<28} {'student':<24} {'access':<9} {'commits':>7}  {'last push':<17} check")
    for row in rows:
        full = f"{ORG}/{repo_name(row)}"
        user = (row.get("github_username") or "").strip().lstrip("@")
        access = "-"
        if user:
            r = gh("api", f"repos/{full}/collaborators/{user}", check=False)
            access = "yes" if r.returncode == 0 else "invited"
        commits = api("GET", f"repos/{full}/commits?per_page=100", check=False) or []
        n = len(commits) if isinstance(commits, list) else 0
        last = commits[0]["commit"]["committer"]["date"][:16].replace("T", " ") if n else ""
        runs = api("GET", f"repos/{full}/actions/runs?per_page=1", check=False) or {}
        run = (runs.get("workflow_runs") or [{}])[0]
        check = run.get("conclusion") or run.get("status") or ""
        print(f"{repo_name(row):<28} {title_case(row['name'])[:24]:<24} {access:<9} {n:>7}  {last:<17} {check}")


def clone(rows, dest):
    os.makedirs(dest, exist_ok=True)
    for row in rows:
        full = f"{ORG}/{repo_name(row)}"
        target = os.path.join(dest, repo_name(row))
        if os.path.isdir(os.path.join(target, ".git")):
            r = subprocess.run(["git", "-C", target, "pull", "-q", "--ff-only"], capture_output=True, text=True)
            print(("updated " if r.returncode == 0 else "PULL FAILED ") + full)
        else:
            gh("repo", "clone", full, target, "--", "-q")
            print("cloned  " + full)


if __name__ == "__main__":
    if len(sys.argv) < 3 or sys.argv[1] not in ("create", "invite", "status", "clone"):
        print(__doc__)
        sys.exit(1)
    rows = read_rows(sys.argv[2])
    {"create": create, "invite": invite, "status": status}.get(sys.argv[1], lambda r: clone(r, sys.argv[3]))(rows)
