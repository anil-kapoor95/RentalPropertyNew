# Rental Property Booking Calendar: audit fixes report

**Date:** 2026-10-14
**Scope:** every finding in `company-audit-report.md`. Each finding was checked against the code before it was changed.

**Where it is applied:** your project folder (XAMPP `RentalProperty`). The same 34 files are in `audit-fixes.zip`.
- The git working tree was clean before the changes (HEAD `304bd6a`), so `git diff` shows every change.
- A full copy of the code as it was before the fixes is kept in the session workspace.

**Credentials:** none are shown in this report. `config.inc.php` was not read or changed.

---

## 1. Fixed issues, by severity

### High

| ID | Finding (verified in code) | Fix | File(s) |
|---|---|---|---|
| H1 | Reservation edit saved to the **POSTed id** using all posted columns (`raw()`), and could move a booking to another company's property. | The booking saved is the one opened from the URL, which is already checked against the company. A different posted id is refused (AR10). The target property must belong to the company (owner restriction included). Posted id, payment-gateway fields, timestamps and IP are never taken from the form. If the property changes, the old iCal event is removed. A broken `uuid` lookup (`isEmpty` was inverted) is fixed. | `app/controllers/pjAdminReservations.controller.php` |
| H2 | SQL injection: `c_name` / `c_email` (and the date-range filter) were concatenated into SQL in the property reservations grid. `c_email` was also checked with the wrong key. | All three are escaped and the key check is corrected. | `app/controllers/pjAdminCalendars.controller.php` |
| H3 | SQL injection in the search `q` of the languages grid and the SMS log grid. | Escaped. | `plugins/pjBase/controllers/pjBaseLocale.controller.php`, `pjBaseSms.controller.php` |
| H4 | Sort column taken from the request with no whitelist (languages, labels, SMS log). The other grids were already whitelisted (verified). | Whitelists added. | same as H3 |
| H5 | `pjModel` runs any value matching `^:[a-zA-Z]` as raw SQL. Request data reached it (for example the booking form). | Root fix at the input layer: such request values get a leading space and are stored as text. Values the code itself sets (`:NOW()`, `:NULL`) still work. | `core/framework/pjInput.class.php` |
| H6 | Front-end cancel used the **POSTed id**, so any booking could be cancelled. | Only the booking in the signed link (id + hash, already checked) is cancelled, and only once. Its iCal event is updated. | `app/controllers/pjFront.controller.php` |
| H7 | Extras were not validated in the price calculation or when saving: negative quantity, other properties' or other companies' extras. | New `pjAppController::sanitizeExtras()`: only the property's active extras, with quantity clamped to 1..max_count as the forms allow. Used in the price calculation and in admin create, admin edit and front booking. Front booking also never takes amount, deposit, tax, ids or gateway fields from the visitor. | `pjAppController`, `pjAdminReservations`, `pjFront` |
| H8 | `syncFeeds()` did not exist, so adding, editing or refreshing a feed in the iCal feeds tab was a fatal error. | Implemented. All feeds of a property (iCal feeds tab + iCal URL list) are always synced together, so one source can no longer remove bookings imported by the other. Cron now also includes properties whose feeds are only in the iCal feeds tab. | `pjAppController`, `pjCron` |
| H9 | User escalation: non-admins could see and edit admins, change any column or role, and export password hashes. | Done in the earlier role fix (`admin-role-access.zip`, verified still in place). This round adds: in the Permissions screen, a non-admin can no longer change his own rights or grant rights he does not hold. | `pjBasePermissions.controller.php` |
| H10 | `pjBaseOptions` OptionsUpdate, UpdateTheme, AjaxSmtp and AjaxSend had no permission check. Any user could write the shared captcha keys. | Each action requires the right to its own System Options page. OptionsUpdate saves only the option keys of the submitted page (no cross-tab keys). Shared login-screen and captcha keys can be changed by administrators only. | `plugins/pjBase/controllers/pjBaseOptions.controller.php` |
| H11 | Languages grid saved any column (`flag`), and deleting a language `unlink()`ed that path, so any file could be deleted. | Column whitelist (language, name, dir). A language row shared with other companies can be renamed only by an admin. Flag files are deleted only inside `app/web/upload/`. | `pjBaseLocale.controller.php` |
| H12 | `pjAdminSetup` skipped the login, permission and CSRF checks. Demo import was global (fixed uuids), so a second company got nothing. | Login and CSRF checks restored. Import needs the add-property right, removal the delete-property right. Demo uuids are made per company (the first company keeps the old ids). Detection and removal are per company, and the current user becomes the owner. | `pjAdminSetup.controller.php`, `app/web/js/pjAdminSetup.js`, `menu-left.php` |
| H13 | Web-root exposure. | Verified that `app/web/backup/` and its company sub-folders already deny web access, and that no old system dumps remain there. Added root `.htaccess` rules that block archives, dumps, logs, `.md` files, `.git` and `Claude outputs/`. **Tested:** `Archive.zip`, `Claude outputs/*`, `.git/*`, `error_log`, `database.sql` and backup files all return **403**. | `.htaccess` |
| H14 | Session fixation through `?session_id=`, no new session id at login, and `?reporting=1` let anyone show PHP errors. | `session_id` in the URL is accepted only for the public booking widgets (`pjFront` / `pjListings`, which need it for cross-site iframes) and must look like a real id. A new session id is issued at login. Cookies are httponly with strict session mode. `reporting` works only for a logged-in administrator. | `index.php`, `plugins/pjAuth/controllers/pjAuth.controller.php` |

### Medium

| Finding | Fix | File(s) |
|---|---|---|
| SSRF / local file read through feed URLs | New `isSafeFeedUrl()`: http(s) only, no credentials in the URL, ports 80/443/8080/8443. Private, loopback, link-local and reserved IPs are refused, except the script's own address. Every redirect hop is checked, and the checked IP is pinned (`CURLOPT_RESOLVE`). Local files are read only from the upload folder. Feed rows no longer take raw POST fields. | `pjAppController`, `pjAdminCalendars` |
| CSRF on the back-office plugin pages (`pjBase*`) | Every POST of a logged-in user needs the session token. `pjAdminCore.js` is now loaded on those pages too, so forms and jQuery Ajax send it automatically. GET deletes were checked: all require an Ajax header, which a cross-site request cannot send. | `plugins/pjBase/controllers/pjBase.controller.php` |
| Referer-based permission inheritance (forged Referer unlocked actions without a permission row) | The Referer is trusted only inside the same section (controller). All 23 app actions without a permission row now have explicit rules. Duplicate inherit keys are removed. Create/Edit form helpers use the permission of the form they were called from. | `app/controllers/pjAdmin.controller.php` |
| `?company` + `?cid` could mix companies on the front end | The property (`cid`) decides the company. With no `cid`, the default property is one of the page's company, not simply #1. | `pjFront` |
| Property tab forms saved to the property held in the session (multi-tab risk) | Saves and Ajax calls from a property page are bound to the `?id=` of that page (from its Referer, checked against the company and owner). | `pjAdminCalendars` |
| Reflected XSS (reservations list `date`/`status`, cancel page, export tab) | Values escaped or URL-encoded. | `pjAdminReservations/pjActionIndex.php`, `pjFront/pjActionCancel.php`, `pjAdminCalendars/elements/export.php` |
| Stored XSS through labels | Label texts saved by non-admins keep simple formatting only. Scripts, event handlers and `javascript:` links are removed. | `pjBaseLocale` |
| Deleting a property left orphan rows and files | One purge routine for single and bulk delete. It also removes features, limits, export passwords, iCal URLs, photos (rows and files), reservation translations and the `.ics` file. | `pjAdminCalendars` |
| Copy settings, tab 8 copied the whole property row (id, uuid, owner, iCal file) | Only the invoice (`y_*`) columns are copied. | `pjAdminCalendars` |
| A company backup could roll back admin accounts | Admins and other companies' users are no longer written into company backups. | `pjCompanyBackup.component.php` |
| Users of a deleted company kept a dangling `company_id` | Set to NULL (users are not deleted). The company's cron rows are removed. | `pjAdminCompanies` |
| Company-role users were found only through `companies.user_id` | Their assigned `company_id` is used as well. | `pjCompanyContext` |
| Re-running update 09 wiped role 4 permissions | The `DELETE` was removed (role 4 starts empty, so `INSERT IGNORE` is enough). | `update_2026_10_08_09_00_00.sql` |
| Cron never did a system run once companies existed | After the company runs, a system run on the job's own schedule handles the full system backup and properties with no active company. | `pjBaseCron`, `pjCron` |
| Anonymous direct call to the cron iCal sync URL | Allowed only for a logged-in admin (cron still runs it through `pjBaseCron`). Tested: anonymous request returns 403. | `pjCron` |
| Platform SMTP / captcha secrets copied into every company | New companies get these empty. While a company keeps the platform's SMTP host or site key, the platform secret is used at run time; with its own server it must enter its own. | `pjCompanyContext` |
| SMS page showed (unescaped) the platform's SMS API key to companies | Shows and escapes only the company's own values. | `pjBaseSms/pjActionIndex.php` |
| SMS log was not per company | Each sent SMS is tagged with its company (new table `companies_sms`). The list shows the company's own messages; admins also see untagged ones. | `pjCompanyContext`, `pjBaseSms` |
| "Copy texts" of a new language filled other companies' texts | Limited to the company's own records plus the shared system texts. | `pjBaseLocale` |
| Automatic backup on/off was global | In a company the switch changes only that company's cron row. The system switch needs `scope=system` from an admin. The page shows the company's own state. | `pjBaseBackup`, `pjBaseBackup/pjActionIndex.php` |
| Coupon code uniqueness was checked in the browser only | Checked on the server for create, edit and inline edit. | `pjAdminVouchers` |
| An owner saving an extra, discount or coupon dropped other owners' property links | Only the links to properties the user manages are replaced. | `pjAdminExtras`, `pjAdminDiscounts`, `pjAdminVouchers` |
| System-wide payment settings (no property) could be changed by any user with property rights | Admins only. | `pjAdminCalendars` |

### Low

| Finding | Status |
|---|---|
| PHP 8 notices | Fixed where found during testing: `compact()` of undefined variables broke the JSON of the feed grid and of the installer's update/plugin lists. A full-site PHP 8 notice sweep was **not** done. All main admin pages were loaded with error display on and showed no warnings. |
| `next_action` block in `pjAdmin` | The value is now validated (pattern), and a missing `controller` no longer warns. |
| Fresh install gave user #1 personal permissions | Fixed in the role round (still in place). |
| `options.inc.php` company role list (3 entries) | Checked: it matches update 09 and only affects fresh installs. No change needed. |
| Company id 0 behaviour | Users without a company now see no data (all queries are scoped to company 0). No further change. |
| Non-idempotent merged SQL (08_09/11/14) | Only the destructive role-4 statement was changed. The files are already recorded as executed and are not re-run. |
| `PJ_TEST_MODE = true` | **Not changed: needs your decision** (see §5). It only switches the Braintree payment UI between sandbox and live. |
| `pjActionAfterInstall` role 3 order | Not changed. Cosmetic, and only on fresh installs. |

---

## 2. Database changes (all additive, nothing deleted)

| File | What it does | Status |
|---|---|---|
| `app/config/updates/update_2026_10_14_10_00_00.sql` | `CREATE TABLE IF NOT EXISTS companies_sms` (SMS log per company). The code also creates it on first use. | **Executed** on your local DB through System Options → Updates. |
| `update_2026_10_08_09_00_00.sql` (edited) | Removed the role-4 `DELETE` so a re-run cannot wipe permissions. | Already executed before; nothing re-runs. |

No production data was deleted or rewritten by the fixes.

One setting was changed during testing and then restored: the automatic-backup switch (details in §4).

## 3. Modified files (34)

```
.htaccess
index.php
core/framework/pjInput.class.php
app/config/updates/update_2026_10_08_09_00_00.sql
app/config/updates/update_2026_10_14_10_00_00.sql            (new)
app/controllers/components/pjCompanyBackup.component.php
app/controllers/components/pjCompanyContext.component.php
app/controllers/pjAdmin.controller.php
app/controllers/pjAdminCalendars.controller.php
app/controllers/pjAdminCompanies.controller.php
app/controllers/pjAdminDiscounts.controller.php
app/controllers/pjAdminExtras.controller.php
app/controllers/pjAdminReservations.controller.php
app/controllers/pjAdminSetup.controller.php
app/controllers/pjAdminVouchers.controller.php
app/controllers/pjAppController.controller.php
app/controllers/pjCron.controller.php
app/controllers/pjFront.controller.php
app/views/pjAdminCalendars/elements/export.php
app/views/pjAdminReservations/pjActionIndex.php
app/views/pjFront/pjActionCancel.php
app/views/pjLayouts/elements/menu-left.php
app/web/js/pjAdminSetup.js
plugins/pjAuth/controllers/pjAuth.controller.php
plugins/pjBase/controllers/pjBase.controller.php
plugins/pjBase/controllers/pjBaseBackup.controller.php
plugins/pjBase/controllers/pjBaseCron.controller.php
plugins/pjBase/controllers/pjBaseLocale.controller.php
plugins/pjBase/controllers/pjBaseOptions.controller.php
plugins/pjBase/controllers/pjBasePermissions.controller.php
plugins/pjBase/controllers/pjBaseSms.controller.php
plugins/pjBase/views/pjBaseBackup/pjActionIndex.php
plugins/pjBase/views/pjBaseSms/pjActionIndex.php
plugins/pjInstaller/controllers/pjInstaller.controller.php   (CRLF kept)
```

After deployment, all 34 files on your computer were checked by md5 against the delivered versions and match.

## 4. Tests run and results

**Static checks**
- `php -l` (PHP 8.3) on all changed PHP files: pass.
- `node --check` on `pjAdminSetup.js`: pass.

**Unit checks (CLI)**
- Feed URL checker: loopback, private, link-local and IPv6 loopback addresses, `file://`, `gopher://`, credentials in the URL and port 22 are all **blocked**. The script's own `localhost` URL and public IPs are allowed.
- Label sanitizer: `<script>`, `onerror`, `onclick`, `javascript:`, `<svg onload>` and `style` are removed. `<br>`, `<b>` and links are kept.
- The scoped "copy texts" SQL was validated against a copy of the database.

**Live checks in your Chrome (admin session) and a separate browser session (temporary Owner user, deleted afterwards)**

| Test | Result |
|---|---|
| 29 back-office pages load | All 200 with no PHP warnings, also with error display on. Front page and cancel page also checked. |
| 14 Ajax grids | Valid JSON. |
| SQL payload in property-reservation name filter | No error, no extra rows. |
| Unknown sort column | Ignored. |
| CSRF on a plugin page and an app page | Request without token gets 403 "Invalid CSRF token"; with token it works. The real System Options "Save" button works (token added automatically). |
| OptionsUpdate | Posting an SMTP host via the General page did not change it; an unknown page returns 403. |
| Price calculation | Quantity −5 and 999 are clamped to the allowed value; another company's extra is ignored (extra price 0). |
| Reservation edit with a different posted id, or a property of another company | Refused (AR10) and data unchanged. A normal save of the same booking works (AR01, data identical). |
| iCal feeds | Internal and local URLs refused (code 105). Adding a feed works, Refresh works (`syncFeeds` no longer fatal: fetched 1, ignored 1, removed 0), and the feed was deleted afterwards. Reservations unchanged. |
| Front end | `?cid=2&company=<other company>` gives the same theme as `?cid=2`. Cancel link with a wrong hash shows "missing parameters". |
| Web root | `Archive.zip`, `Claude outputs/*`, `.git/*`, `error_log`, `app/config/database.sql` and backup `.sql` all return 403. Normal CSS and `.ics` files are still served (200). |
| Anonymous direct cron sync URL | 403. |
| Owner user | Menu limited (Dashboard, Properties, Reservations, Display, Install). The following were all denied: features and coupon helpers with a forged dashboard Referer, system payment settings, users list, theme change, test e-mail, system options save, editing his own permissions, language save (`flag` path), demo import, and a POST without CSRF token (403). Own grids (reservations, properties, schedule) work. |
| Deleted user while logged in | Next Ajax call gets "Your session has ended" (401). |
| Setup wizard | Page loads with the scripts. A POST without token gets 403; with token it works. |
| Auto-backup switch | Toggled on and off in the company: company row only. |
| Update `update_2026_10_14_10_00_00.sql` | Executed: "Database update has been applied". |

**Settings touched during testing**
- **Auto-backup switch.** My first toggle test also wrote the system-wide switch. I changed the code so this can no longer happen (see the Medium table). I then set the system switch back to **Yes**, which is the value in the last database snapshot I have; please confirm it under System Options → Backup.
- **Error display.** A test URL with `?reporting=1` (before the fix) had switched error display on for your admin session. I switched it off again.
- **Test data.** One temporary test user (QA Owner) was created and deleted. No bookings were created, changed or deleted. The temporary feed was removed.

## 5. Not done: needs your decision or cannot be verified

1. **Delete files from the web folder.** These are now blocked by `.htaccess`, but they should not be there:
   - `Archive.zip` (77 MB)
   - the `Claude outputs/` folder
   - the temporary `Claude outputs/.devsums.txt` (a file list I created for the sync check)

   Deleting needs your approval.
2. **Existing companies still hold copied platform secrets** (SMTP user/password, captcha front secret). New companies are fixed. Clearing the old copies is a data change; this SQL clears them only where they equal the platform value:
   ```sql
   UPDATE `rpbcalendar_plugin_base_options` c JOIN `rpbcalendar_plugin_base_options` g
     ON g.`foreign_id` = 1 AND g.`key` = c.`key`
   SET c.`value` = ''
   WHERE c.`foreign_id` > 1000 AND c.`key` IN ('o_smtp_user','o_smtp_pass','o_captcha_secret_key_front')
     AND c.`value` = g.`value`;
   ```
   Check the table prefix (`rpbcalendar_` or `crm_rpbcalendar_`) and take a DB backup first.
3. **Old company backup files** made before this fix may contain admin rows. Do not import them, or remove the `plugin_auth_users` lines for admins first.
4. **`PJ_TEST_MODE = true`.** Set it to `false` for a live site if Braintree is used in production.
5. **SSL verification for feed downloads stays off,** as before. Turning it on could break feeds on servers without a CA bundle (XAMPP on macOS often lacks one). Decide per server.
6. **Could not verify:**
   - payment plugins (Stripe, PayPal and others) scoping and webhooks;
   - the real cron schedule through `cron.php` (only individual job calls were run);
   - a full front-end booking (it would create a real reservation) and a real cancel by e-mail link. Both are covered by code review and by the price-calculation test;
   - Apache vs nginx in production (`.htaccess` rules apply to Apache only);
   - a full PHP 8 notice sweep.
7. **Permission granularity:** Login & Protection sub-sections (password / secure login / failed login / forgot) still share one save permission. The page-level check is enforced.

## 6. Deployment steps and regression checklist

**Deploy**
1. Copy the 34 files from `audit-fixes.zip` over the project, keeping the paths. Use the same permissions as the other files (644).
2. Open System Options → Updates and run `update_2026_10_14_10_00_00.sql`.
3. Log out and log in again (new session id and CSRF token).
4. On nginx, add equivalent `deny` rules for `*.zip`, `*.sql`, `*.log`, `*.md`, `/.git` and `/Claude outputs`.

**Regression checklist**
1. Log in as admin, then as an Owner and a Company user; the menus should match each role.
2. Properties: edit each tab with two properties open in two browser tabs; each save should land on its own property.
3. Reservations:
   - add a booking with extras;
   - edit it, change property and dates;
   - delete it.
4. Front end:
   - book with extras (check the total);
   - cancel through the e-mail link.
5. iCal feeds tab: add an Airbnb or Booking.com feed, click Refresh, then delete it.
6. Coupons: a duplicate code is refused. Extras, discounts and coupons: links to other owners' properties are kept.
7. System Options: each page saves; SMTP test works; captcha settings are editable by admins only.
8. Languages and labels:
   - add a language in a company; other companies are unchanged;
   - a label with HTML saved by a company user is cleaned.
9. Backup: the company switch and the cron page work. Check that the next system run makes a full backup.
10. SMS: send a test SMS; it appears in that company's log only.

---

## 7. Follow-up after approval (2026-10-14)

| Item | Done |
|---|---|
| Web-folder clean-up | `Archive.zip` (an Oct 8 copy of the project, still in git history), the `Claude outputs/` folder (all its files are also in the chat) and the temporary `.devsums.txt` were **deleted**. |
| `PJ_TEST_MODE` | Set to `false` in `app/config/options.inc.php` (Braintree runs in live mode). |
| Copied platform secrets | New migration `update_2026_10_14_11_00_00.sql` blanks the copied SMTP user, SMTP password and front captcha secret in company settings, **only where they still equal the platform's value**. Syntax was checked against a copy of the database. The file is on your computer but **not executed yet**: browser automation hit a usage limit before the database backup could be made. To finish: System Options → Backup → "Whole system" database backup, then System Options → Updates → run `update_2026_10_14_11_00_00.sql`. |
| Old company backups | Checked both company backup files: they contain only Company-role users (role 4), no administrators, so they are safe to import. No change needed. |
