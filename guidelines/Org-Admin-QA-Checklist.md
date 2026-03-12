# Org-Admin QA Checklist (Organization Admin)

Date: ____________________
Tester: __________________
Environment: __________________
Build/Commit: __________________

Legend:
- Pass: behavior matches expected result
- Fail: behavior does not match expected result
- Blocked: cannot execute due to missing setup/data/bug

## Preconditions

- You can sign in as a user with role org_admin.
- The org_admin user is assigned to an organization (org_id is not null).
- At least one booking request exists for the assigned organization (for inbox/accept/decline tests).
- CSRF/session is working in your environment.

## Result Summary

| Area | Pass | Fail | Blocked | Notes |
|---|---:|---:|---:|---|
| Org-Admin Access & Navigation |  |  |  |  |
| Org-Admin Dashboard |  |  |  |  |
| Organization Profile |  |  |  |  |
| Admin-Change Review Flow |  |  |  |  |
| Booking Inbox Workflow |  |  |  |  |
| Org Statistics |  |  |  |  |
| Account Settings |  |  |  |  |
| Role/Access Control |  |  |  |  |
| Known Gaps |  |  |  |  |

## 1) Org-Admin Access & Navigation

| ID | Route/Action | Steps | Expected Result | Status | Notes |
|---|---|---|---|---|---|
| OA1 | /dashboard role redirect | Sign in as org_admin, open /dashboard | Redirects to /org-admin/dashboard |  |  |
| OA2 | Direct org-admin entry | Open /org-admin and /org-admin/dashboard | Org-admin dashboard loads |  |  |
| OA3 | Bookings redirect behavior | Open /bookings as org_admin | Redirects to /org-admin/bookings |  |  |
| OA4 | Org-admin nav visibility | Check top nav/account menu | Org Admin Dashboard, Bookings, and Account Settings are visible |  |  |

## 2) Org-Admin Dashboard

| ID | Route/Action | Steps | Expected Result | Status | Notes |
|---|---|---|---|---|---|
| OD1 | Stats cards | Open /org-admin/dashboard | Members, Pending, Accepted cards render |  |  |
| OD2 | Upcoming accepted events | Check panel | Upcoming accepted events list renders or empty-state |  |  |
| OD3 | Completed accepted events | Check panel | Completed accepted events list renders or empty-state |  |  |
| OD4 | Incoming bookings quick actions | Use Accept/Decline on a pending item | Status changes and success/error feedback is shown on redirect |  |  |
| OD5 | Organization info quick edit | Click Edit Profile | Redirects to /org-admin/profile/edit |  |  |

## 3) Organization Profile

### 3.1 View and Edit Profile

| ID | Route/Action | Steps | Expected Result | Status | Notes |
|---|---|---|---|---|---|
| OP1 | View profile | Open /org-admin/profile | Organization details render (name, genre, bio, requirements, links) |  |  |
| OP2 | Open edit form | Click Edit Profile | /org-admin/profile/edit loads with prefilled values |  |  |
| OP3 | Save basic edits | Update name/genre/bio/requirements/youtube links and save | Redirect to /org-admin/profile with success message |  |  |
| OP4 | Upload valid image | Upload JPG/PNG/GIF/WEBP <=2MB and save | Profile updates and image displays |  |  |
| OP5 | Upload invalid image type | Upload invalid file type | Error shown, profile not updated with invalid image |  |  |
| OP6 | Upload oversized image | Upload image >2MB | Error shown due to size limit |  |  |

### 3.2 Delete Organization Profile (Danger Zone)

| ID | Route/Action | Steps | Expected Result | Status | Notes |
|---|---|---|---|---|---|
| OPD1 | Delete organization | In /org-admin/profile/edit, click Delete Organization and confirm | Organization delete attempted, session is flushed, user is redirected to home with success if delete works |  |  |
| OPD2 | Delete with linked data constraints | Attempt delete when related records may block | Clear error shown if deletion fails |  |  |

## 4) Admin-Change Review Flow

These tests apply when a system admin has recently edited this same organization profile.

| ID | Route/Action | Steps | Expected Result | Status | Notes |
|---|---|---|---|---|---|
| AR1 | Pending banner visibility | Ensure admin changed org profile, then open /org-admin/profile | Pending review banner appears |  |  |
| AR2 | Accept admin changes | Click Accept Admin Changes | Success message, acceptance audit action recorded |  |  |
| AR3 | Decline admin changes | Click Decline and Revert | Previous values are restored and success shown |  |  |
| AR4 | No pending changes edge | Trigger accept/decline without pending change | Friendly error shown indicating no pending changes |  |  |

## 5) Booking Inbox Workflow

### 5.1 Inbox and Detail

| ID | Route/Action | Steps | Expected Result | Status | Notes |
|---|---|---|---|---|---|
| BI1 | Open inbox | Open /org-admin/bookings | Booking table renders or empty-state message appears |  |  |
| BI2 | View booking detail | Click View on a booking | /org-admin/bookings/{id} detail page loads |  |  |
| BI3 | Org ownership guard | Open booking id from another organization | Access denied/unauthorized behavior |  |  |

### 5.2 Accept / Decline Pending Requests

| ID | Route/Action | Steps | Expected Result | Status | Notes |
|---|---|---|---|---|---|
| BW1 | Accept pending booking | Click Accept for pending request | Booking status becomes accepted and success appears |  |  |
| BW2 | Decline pending booking | Click Decline for pending request | Booking status becomes declined and success appears |  |  |
| BW3 | Re-process non-pending booking | Try accept/decline on already processed item | Error shown: booking already processed |  |  |
| BW4 | Missing booking id edge | Submit accept/decline without booking_id | Error/redirect with booking ID required |  |  |

### 5.3 Organizer Data Display

| ID | Route/Action | Steps | Expected Result | Status | Notes |
|---|---|---|---|---|---|
| BO1 | Organizer name/email fallback | Use booking where organizer_name/email is missing | Inbox/detail still shows organizer values via fallback lookup when possible |  |  |

## 6) Org Statistics

| ID | Route/Action | Steps | Expected Result | Status | Notes |
|---|---|---|---|---|---|
| ST1 | Open statistics page | Open /org-admin/statistics | Total, Pending, Accepted, Declined cards render |  |  |
| ST2 | Summary text consistency | Compare card values to summary sentence | Summary reflects same values shown in cards |  |  |

## 7) Account Settings (while logged in as org_admin)

| ID | Route/Action | Steps | Expected Result | Status | Notes |
|---|---|---|---|---|---|
| AC1 | Profile update | Open /account, change name, submit | Success message and updated display name/session |  |  |
| AC2 | Password change valid | Submit current + valid new + confirm | Success message and session remains usable |  |  |
| AC3 | Password change invalid current | Submit wrong current password | Error message shown |  |  |
| AC4 | Password mismatch | New and confirm mismatch | Error message shown |  |  |
| AC5 | Password too short | New password <8 chars | Error message shown |  |  |

## 8) Role/Access Control

| ID | Scenario | Steps | Expected Result | Status | Notes |
|---|---|---|---|---|---|
| RC1 | Non-org-admin access | Sign in as organizer/system_admin and open /org-admin/dashboard | Access denied due to role guard |  |  |
| RC2 | Unauthenticated access | Sign out and open /org-admin/dashboard | Redirect to sign-in |  |  |
| RC3 | Org assignment required | Use org_admin account with no org_id and open org-admin routes | 403 or clear error about no organization assigned |  |  |

## 9) Known Gaps / Defects to Verify

| ID | Gap | How to Test | Expected Current Behavior | Status | Notes |
|---|---|---|---|---|---|
| G1 | /account/delete route is wired but handler may be missing | Click Delete Account in /account | Likely failure/error due to missing controller method |  |  |
| G2 | Accept/Decline supports notes/reason in controller but UI does not provide fields | Use dashboard/inbox/detail accept/decline actions | Action works, but no notes/reason can be entered from UI |  |  |

## Defect Log

| Bug ID | Severity | Area | Steps to Reproduce | Actual Result | Expected Result | Evidence |
|---|---|---|---|---|---|---|
|  |  |  |  |  |  |  |

## Sign-off

- QA Reviewer: __________________
- Date: _________________________
- Overall Result: Pass / Fail / Blocked
