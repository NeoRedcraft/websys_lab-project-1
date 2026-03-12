# Admin QA Checklist (System Admin)

Date: ____________________
Tester: __________________
Environment: __________________
Build/Commit: __________________

Legend:
- Pass: behavior matches expected result
- Fail: behavior does not match expected result
- Blocked: cannot execute due to missing setup/data/bug

## Preconditions

- You can sign in as a user with role `system_admin`.
- At least one organization exists (active or inactive).
- At least one organizer and one org admin user exist (if possible).
- CSRF/session is working in your environment.

## Result Summary

| Area | Pass | Fail | Blocked | Notes |
|---|---:|---:|---:|---|
| Admin Access & Navigation |  |  |  |  |
| Admin Dashboard |  |  |  |  |
| Home Banner Upload |  |  |  |  |
| Organization Management |  |  |  |  |
| User Management |  |  |  |  |
| Audit Logs |  |  |  |  |
| Admin Bookings Privileges |  |  |  |  |
| Account Settings |  |  |  |  |
| Role/Access Control |  |  |  |  |
| Known Gaps |  |  |  |  |

## 1) Admin Access & Navigation

| ID | Route/Action | Steps | Expected Result | Status | Notes |
|---|---|---|---|---|---|
| A1 | /dashboard role redirect | Sign in as system admin, open /dashboard | Redirects to /admin/dashboard |  |  |
| A2 | Direct admin entry | Open /admin and /admin/dashboard | Admin dashboard loads |  |  |
| A3 | Admin nav visibility | Check top nav | Admin Dashboard and Bookings links are visible |  |  |

## 2) Admin Dashboard

| ID | Route/Action | Steps | Expected Result | Status | Notes |
|---|---|---|---|---|---|
| D1 | Stats cards | Open /admin/dashboard | Organizations count, Pending, Accepted values render |  |  |
| D2 | Recent audit panel | Scroll dashboard | Recent audit logs list shows entries or empty-state message |  |  |
| D3 | Organizations quick actions | Use Edit/Activate/Deactivate in org side panel | Correct action executes and success/error message appears |  |  |

## 3) Home Banner Upload

| ID | Test Type | Input | Expected Result | Status | Notes |
|---|---|---|---|---|---|
| HB1 | Positive | Upload valid JPG <=5MB | Success message and new banner preview shown |  |  |
| HB2 | Positive | Upload valid PNG <=5MB | Success message and new banner preview shown |  |  |
| HB3 | Negative | Upload invalid type (ex: .pdf) | Error message about invalid image type |  |  |
| HB4 | Negative | Upload image >5MB | Error message about file size limit |  |  |
| HB5 | Negative | Submit with no file | Error message about upload failure/valid file required |  |  |

## 4) Organization Management

### 4.1 List/Create/Edit/Delete/Activate/Deactivate

| ID | Route/Action | Steps | Expected Result | Status | Notes |
|---|---|---|---|---|---|
| O1 | List organizations | Open /admin/organizations | Table loads with current organizations |  |  |
| O2 | Create form open | Click New Organization | Create form loads |  |  |
| O3 | Create minimal valid org | Submit valid name (+ optional fields) | Org created and redirect with success message |  |  |
| O4 | Edit org fields | Edit name/genre/bio and save | Changes persist and success message appears |  |  |
| O5 | Deactivate org | Click Deactivate on active org | Org status changes to inactive |  |  |
| O6 | Activate org | Click Activate on inactive org | Org status changes to active |  |  |
| O7 | Delete org | Click Delete for test org | Org removed or clear error explains constraint |  |  |

### 4.2 President Assignment Validation

| ID | Test Type | Input | Expected Result | Status | Notes |
|---|---|---|---|---|---|
| OP1 | Negative | president_name only | Error: provide both president name and email |  |  |
| OP2 | Negative | president_email only | Error: provide both president name and email |  |  |
| OP3 | Negative | Non-mapua email | Error: must be @mymail.mapua.edu.ph |  |  |
| OP4 | Positive | New valid mapua email | President account created and temporary password indicated |  |  |
| OP5 | Positive/Branch | Existing valid mapua email | Existing account reassigned/relinked as org admin |  |  |

### 4.3 Organization Image Upload Validation

| ID | Test Type | Input | Expected Result | Status | Notes |
|---|---|---|---|---|---|
| OI1 | Positive | Valid image <=2MB (JPG/PNG/GIF/WEBP) | Image uploads and is associated to organization |  |  |
| OI2 | Negative | Invalid image type | Error shown, org may still be created/edited without valid image |  |  |
| OI3 | Negative | Image >2MB | Error shown due to size limit |  |  |

## 5) User Management

| ID | Route/Action | Steps | Expected Result | Status | Notes |
|---|---|---|---|---|---|
| U1 | Open users page | Open /admin/users | Users table loads |  |  |
| U2 | Change role modal open | Click Change Role on a user | Modal opens with selected user details |  |  |
| U3 | Assign role valid | Submit user_id + role_id (+ org for org_admin) | Success JSON returned and role updated in data |  |  |
| U4 | Assign role invalid | Submit missing user_id or role_id | Error JSON returned |  |  |
| U5 | Pre-register modal open | Click + Pre-register President | Modal opens |  |  |
| U6 | Pre-register valid | Submit name + mapua email + org | Success JSON with temp_password shown; page reloads |  |  |
| U7 | Pre-register invalid email | Submit non-mapua email | 422 JSON error shown |  |  |
| U8 | Pre-register duplicate email | Submit existing email | 409 JSON error shown |  |  |
| U9 | Pre-register missing fields | Omit required values | 422 JSON error shown |  |  |

Note:
- Current implementation returns JSON for role assignment and preregister actions. Confirm browser response behavior is acceptable for your UX.

## 6) Audit Logs

| ID | Route/Action | Steps | Expected Result | Status | Notes |
|---|---|---|---|---|---|
| AL1 | Open audit logs | Open /admin/audit-logs | Logs table renders or empty-state message appears |  |  |
| AL2 | Limit filter | Open /admin/audit-logs?limit=10 | Up to 10 recent logs shown |  |  |
| AL3 | User filter | Open /admin/audit-logs?user_id=<valid_user_id> | Only logs for selected user shown |  |  |

## 7) Admin Bookings Privileges

| ID | Route/Action | Steps | Expected Result | Status | Notes |
|---|---|---|---|---|---|
| B1 | Open bookings as admin | Open /bookings | Admin bookings list grouped by organization appears |  |  |
| B2 | View booking detail | Click View on a booking | Detail page loads |  |  |
| B3 | Delete non-pending booking as admin | Delete accepted/declined booking | Delete is allowed for system admin |  |  |
| B4 | Create booking as admin | Open /bookings/create and submit valid form | Booking created successfully |  |  |

## 8) Account Settings (while logged in as admin)

| ID | Route/Action | Steps | Expected Result | Status | Notes |
|---|---|---|---|---|---|
| AC1 | Profile update | Open /account, change name, submit | Success message and updated display name/session |  |  |
| AC2 | Password change valid | Submit current + valid new + confirm | Success message and session remains usable |  |  |
| AC3 | Password change invalid current | Submit wrong current password | Error message shown |  |  |
| AC4 | Password mismatch | New and confirm mismatch | Error message shown |  |  |
| AC5 | Password too short | New password <8 chars | Error message shown |  |  |

## 9) Access Control / Authorization

| ID | Scenario | Steps | Expected Result | Status | Notes |
|---|---|---|---|---|---|
| R1 | Non-admin hits admin route | Sign in as organizer/org_admin and open /admin/dashboard | Access denied / redirected due to role guard |  |  |
| R2 | Unauthenticated admin route access | Sign out and open /admin/dashboard | Redirect to sign-in |  |  |
| R3 | System admin booking permissions | As system admin, delete booking not owned by admin | Allowed for system admin |  |  |

## 10) Known Gaps / Defects to Verify

| ID | Gap | How to Test | Expected Current Behavior | Status | Notes |
|---|---|---|---|---|---|
| G1 | /account/delete route wired but controller method may be missing | Click Delete Account in /account | Likely error/failure due to missing handler implementation |  |  |

## Defect Log

| Bug ID | Severity | Area | Steps to Reproduce | Actual Result | Expected Result | Evidence |
|---|---|---|---|---|---|---|
|  |  |  |  |  |  |  |

## Sign-off

- QA Reviewer: __________________
- Date: _________________________
- Overall Result: Pass / Fail / Blocked
