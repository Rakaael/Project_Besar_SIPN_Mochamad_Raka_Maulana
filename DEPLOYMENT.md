# Automatic deployment to InfinityFree

This repository deploys the `SIPN/` application directory to `/htdocs/SIPN/`
when a commit is pushed to the `main` branch. A deployment can also be started
manually from the repository's Actions tab.

## Configure GitHub Actions

In the GitHub repository, open **Settings → Secrets and variables → Actions**
and add these repository secrets:

| Secret | Value |
| --- | --- |
| `FTP_SERVER` | `ftpupload.net` |
| `FTP_USERNAME` | FTP username shown on the InfinityFree FTP Details page |
| `FTP_PASSWORD` | FTP password for that hosting account |

Never put FTP credentials in source files, commits, issues, or chat messages.
The deployment uses regular FTP on port 21, which does not encrypt credentials
or file contents in transit. Only enable it if you accept this limitation.

## Before the first deployment

1. Back up the website files and database from the hosting control panel.
2. Verify that this domain's document root is actually `/htdocs/SIPN/` in
   InfinityFree File Manager. If it differs, update `server-dir` in
   `.github/workflows/deploy-infinityfree.yml` before deploying.
3. Keep the hosting copy of `SIPN/Koneksi.php`. The workflow deliberately
   excludes this host-specific database configuration, the SQL dump, account
   notes, ZIP files, and uploaded profile images.
4. Add the three GitHub Actions secrets above.
5. Commit and push the workflow to `main`. Check **Actions** and open the
   deployment run to confirm it succeeded, then load the live site and test
   login and one page from each required role.

The workflow does not modify the database. Do not import `database/sipn.sql`
as part of deployment; it can recreate tables and destroy existing data.
