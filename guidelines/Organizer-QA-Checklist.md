# Organizer QA Checklist (Organizer Role)

Date: ____________________
Tester: __________________
Environment: __________________
Build/Commit: __________________

Legend:
- Pass: behavior matches expected result
- Fail: behavior does not match expected result
- Blocked: cannot execute due to missing setup/data/bug

## Preconditions

- You can sign in as a user with role organizer.
- At least one active organization exists to submit bookings.
- CSRF/session is working in your environment.
- Optional: have at least one existing pending booking for edit/delete tests.

## Result Summary

| Area | Pass | Fail | Blocked | Notes |
|---|---:|---:|---:|---|
| Organizer Access & Navigation |  |  |  |  |
| Organizer Dashboard |  |  |  |  |
| My Bookings List |  |  |  |  |
| Booking Creation |  |  |  |  |
| Booking View/Edit/Delete |  |  |  |  |
| Account Settings |  |  |  |  |
| Role/Access Control |  |  |  |  |
| Known Gaps |  |  |  |  |

## 1) Organizer Access & Navigation

| ID | Route/Action | Steps | Expected Result | Status | Notes |
|---|---|---|---|---|---|
| OG1 | /dashboard role redirect | Sign in as organizer, open /dashboard | Redirects to /organizer-dashboard |  |  |
| OG2 | Organizer dashboard direct | Open /organizer-dashboard | Organizer dashboard loads |  |  |
| OG3 | Bookings entry | Open /bookings and /bookings/my-bookings | My bookings page loads |  |  |
| OG4 | Nav visibility | Check top nav/account menu | Bookings and Account Settings links are visible for organizer |  |  |

## 2) Organizer Dashboard

| ID | Route/Action | Steps | Expected Result | Status | Notes |
|---|---|---|---|---|---|
| OD1 | Stats cards | Open /organizer-dashboard | My Bookings, Pending, Accepted cards render |  |  |
| OD2 | Booking requests list | Scroll dashboard list | Organizer booking items render or empty-state message appears |  |  |
| OD3 | Status badge rendering | Check mixed booking statuses | Pending/Accepted/Declined badges display correctly |  |  |

## 3) My Bookings List

| ID | Route/Action | Steps | Expected Result | Status | Notes |
|---|---|---|---|---|---|
| MB1 | Open list | Open /bookings/my-bookings | Bookings table renders or empty-state message appears |  |  |
| MB2 | New booking CTA | Click + New Booking | Redirects to /bookings/create |  |  |
| MB3 | View action | Click View for any booking | Opens /bookings/view/{id} |  |  |
| MB4 | Edit action visibility | For pending booking row, check actions | Edit appears only for pending status |  |  |

## 4) Booking Creation

| ID | Test Type | Input / Action | Expected Result | Status | Notes |
|---|---|---|---|---|---|
| BC1 | Positive | Submit valid form with required fields | Booking created and redirected with success |  |  |
| BC2 | Negative | Missing required fields | Error shown: all required fields must be filled |  |  |
| BC3 | Negative | Invalid Engage URL format | Error shown: valid Engage URL required |  |  |
| BC4 | Negative | Invalid invitation URL | Error shown: must be Google Drive or OneDrive link |  |  |
| BC5 | Negative | Invalid organization selection | Error shown: invalid organization selected |  |  |
| BC6 | Data check | After create, open my bookings | New booking appears with expected status (pending) |  |  |

Required fields to validate in creation flow:
- Organization
- Event Name
- Event Date
- Venue
- Engage Event Link
- Invitation Document URL

## 5) Booking View/Edit/Delete

### 5.1 View Details

| ID | Route/Action | Steps | Expected Result | Status | Notes |
|---|---|---|---|---|---|
| BV1 | View own booking | Open /bookings/view/{id} for own booking | Booking details load |  |  |
| BV2 | Unauthorized view guard | Open booking id not owned by organizer | Access denied/unauthorized response |  |  |
| BV3 | Attachment links | Check Engage and Invitation links | Links open in new tab when present |  |  |

### 5.2 Edit Booking (Pending only)

| ID | Route/Action | Steps | Expected Result | Status | Notes |
|---|---|---|---|---|---|
| BE1 | Open edit for pending | Open /bookings/edit/{id} (pending booking) | Edit form loads with existing values |  |  |
| BE2 | Save valid changes | Update fields and submit | Redirect to booking detail with success |  |  |
| BE3 | Edit non-pending | Open/edit accepted or declined booking | Blocked with message: can only edit pending |  |  |
| BE4 | Unauthorized edit guard | Open/edit booking not owned by organizer | Access denied/unauthorized response |  |  |

### 5.3 Delete Booking (Pending only)

| ID | Route/Action | Steps | Expected Result | Status | Notes |
|---|---|---|---|---|---|
| BD1 | Delete pending booking | Delete from detail page (pending) | Booking deleted and success message shown |  |  |
| BD2 | Delete non-pending booking | Attempt delete for accepted/declined | Blocked with error: only pending can be deleted |  |  |
| BD3 | Unauthorized delete guard | Submit delete for booking not owned by organizer | Blocked with unauthorized error |  |  |
| BD4 | Missing booking id edge | Submit delete without booking_id | Error/redirect with booking ID required |  |  |

## 6) Account Settings (while logged in as organizer)

| ID | Route/Action | Steps | Expected Result | Status | Notes |
|---|---|---|---|---|---|
| AC1 | Profile update | Open /account, change name, submit | Success message and updated display name/session |  |  |
| AC2 | Password change valid | Submit current + valid new + confirm | Success message and session remains usable |  |  |
| AC3 | Password change invalid current | Submit wrong current password | Error message shown |  |  |
| AC4 | Password mismatch | New and confirm mismatch | Error message shown |  |  |
| AC5 | Password too short | New password <8 chars | Error message shown |  |  |

## 7) Role/Access Control

| ID | Scenario | Steps | Expected Result | Status | Notes |
|---|---|---|---|---|---|
| RC1 | Organizer blocked from admin routes | As organizer, open /admin/dashboard | Access denied/redirect due to role guard |  |  |
| RC2 | Organizer blocked from org-admin routes | As organizer, open /org-admin/dashboard | Access denied/redirect due to role guard |  |  |
| RC3 | Unauthenticated organizer routes | Sign out and open /bookings or /organizer-dashboard | Redirect to sign-in |  |  |

## 8) Known Gaps / Defects to Verify

| ID | Gap | How to Test | Expected Current Behavior | Status | Notes |
|---|---|---|---|---|---|
| G1 | /account/delete route is wired but controller method may be missing | Click Delete Account in /account | Likely failure/error due to missing controller method |  |  |
| G2 | Invitation URL requires cloud link format, not direct upload in organizer form | Try non-GDrive/OneDrive URL | Validation rejects with invitation URL error |  |  |

## Defect Log

| Bug ID | Severity | Area | Steps to Reproduce | Actual Result | Expected Result | Evidence |
|---|---|---|---|---|---|---|
|  |  |  |  |  |  |  |

## Sign-off

- QA Reviewer: __________________
- Date: _________________________
- Overall Result: Pass / Fail / Blocked
