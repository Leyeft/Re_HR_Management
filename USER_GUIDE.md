# Crewly User Guide

Crewly is the HR system for managing staff, departments and leave. This guide explains what each person can do, how to do it, and the rules the system follows.

**Live site:** <https://rehrmanagement-production.up.railway.app>

---

## Contents

1. [Who can do what](#1-who-can-do-what)
2. [Getting started](#2-getting-started)
3. [Leave: the rules](#3-leave-the-rules)
4. [For everyone](#4-for-everyone)
5. [For Heads of Department](#5-for-heads-of-department)
6. [For HR and Admin](#6-for-hr-and-admin)
7. [Salary](#7-salary)
8. [Safeguards: things the system won't let you do](#8-safeguards-things-the-system-wont-let-you-do)
9. [Common questions](#9-common-questions)

---

## 1. Who can do what

Everyone has a **role**: Employee, HR or Admin. Separately, an employee can be the **Head of Department**, who approves leave for their department.

| What | Employee | Head | HR | Admin |
| --- | :-: | :-: | :-: | :-: |
| Sign in, edit own profile, photo and password | ✅ | ✅ | ✅ | ✅ |
| See own dashboard, leave balance and notifications | ✅ | ✅ | ✅ | ✅ |
| Request and cancel own leave | ✅ | ✅ | ✅ | ✅ |
| See own monthly salary | ✅ | ✅ | ✅ | ✅ |
| Approve or reject leave for **own department** (step 1) | — | ✅ | — | — |
| See own department's leave history | — | ✅ | — | — |
| Give final approval to leave (step 2) and see all leave | — | — | ✅ | ✅ |
| Company dashboard with charts | — | — | ✅ | ✅ |
| Add, edit, delete and restore employees and departments | — | — | ✅ | ✅ |
| Manage skills, holidays and leave settings | — | — | ✅ | ✅ |
| See and set everyone's salary (never their own) | — | — | ✅ | ✅ |
| Activity log and CSV/PDF reports | — | — | ✅ | ✅ |
| Create, edit or remove **Admin** accounts | — | — | — | ✅ |

HR and Admin accounts can't also be a Head of Department.

---

## 2. Getting started

### Signing in

Open the site and sign in with your work email and password. There is **no public sign-up**: HR or an Admin creates your account and gives you a temporary password. Change it straight away (see [Change your password](#change-your-password)).

### Getting around

- **Top bar:** the main pages. You only see the pages you're allowed to use.
- **Bell icon:** your notifications. The red number is how many are unread. It checks for new ones every 30 seconds.
- **Your name (top right):** Profile, Notifications, the **Theme** switch and **Sign out**. HR and Admin also find Leave settings, Skills and the Activity log here.
- **On a phone:** tap **☰** to open the menu.

### Light and dark mode

Open the menu under your name and choose **Light**, **Dark** or **System**. System follows your device. Your choice is remembered for **you** on this browser. When nobody is signed in, the sign-in page follows the device, so the next person on the same computer doesn't get your theme.

---

## 3. Leave: the rules

### Leave types

| Type | Allowance | When | Approval |
| --- | --- | --- | --- |
| **Annual** | Based on years of service (see below) | Today or later | Head, then HR |
| **Sick** | 30 working days a year | Today or earlier (logged after you were ill) | **None**: approved straight away |
| **Professional** (training, conferences) | No limit | Today or later | Head, then HR |

### Annual allowance by years of service

| Years worked | Days per year |
| --- | --- |
| Under 1 year | 3 |
| 1–7 years | 10 |
| 8–15 years | 15 |
| 16 years or more | 20 |

Years are counted from your **date hired**. Your allowance goes up automatically on your work anniversary.

### How days are counted

- Only **working days** count: Monday to Friday, minus public holidays. A request from Friday to Monday uses 2 days.
- A request that falls only on weekends or holidays is refused, because it uses no working days.
- Requests still **waiting for approval** count against what you have left, so you can't book more than your allowance.

### Rollover

HR decides whether unused annual days carry over to the next year, and the maximum (0–60 days; 5 by default). Rollover is **off** unless HR turns it on. People hired this year have nothing to carry over.

### The approval steps

```
You submit  →  Head of Department approves  →  HR approves  →  Approved
                         ↘ rejects                ↘ rejects
                       Rejected (with reason)    Rejected (with reason)
```

- **Sick leave** skips both steps and is approved straight away.
- **A Head's own leave** skips the Head step and goes straight to HR.
- **A department with no Head:** requests go straight to HR.
- A rejection always includes a **reason**, which you see on the request and in your notification.
- You get a notification at each step.

### Other leave rules

- You can't request dates that **overlap** another request of yours that is pending or approved. Cancel the old one first.
- You must give a **reason** (up to 500 characters).
- You can **cancel** your own request while it's pending **or after it's approved**, with a short reason. The days go back to your balance. Rejected or already cancelled requests can't be cancelled.
- Only you can cancel your own request.

---

## 4. For everyone

### Check your leave balance

Open **Dashboard**. It shows your Employee ID, your annual days left (allowance, used, pending), sick days left, professional days used, and how many of your requests are pending, approved, rejected and so on. Your full list of requests is under **My Leave**.

### Request leave

1. Go to **My Leave → + Request leave** (or **Request leave** on the Dashboard).
2. Choose the type, the start and end dates, and write a reason.
3. The form shows how many working days it is before you send it, and warns you if it's more than you have left.
4. Click **Submit request** (for sick leave: **Record sick leave**). You'll see the request under **My Leave** with its status.

### Cancel a request

In **My Leave**, click **Cancel** on the request and give a reason.

### Notifications

Click the **bell** to see recent notifications, or **Notifications** in your menu for the full list. Click one to open it, or **Mark all as read**.

### Your profile

Open **Profile** from the menu under your name.

- **Your account:** your role, Employee ID, department, rank and monthly salary. Only HR can change these.
- **Profile photo:** upload a JPG, PNG or WebP image (at most 2 MB, between 64×64 and 4000×4000 pixels), or remove it.
- **Name and email:** you can change them. A new email must be verified again.
- **Verify your email:** if you see the yellow banner, click **Send verification email** and open the link in the email.

### Change your password

In **Profile**, click **Change password**. On that page, enter your current password and the new one twice, then click **Change password**. It must be at least 8 characters.

### Forgot your password

On the sign-in page, click **Forgot password?** and enter your email. If an account exists, a reset link is sent to it. Open the link and choose a new password.

> **Note:** until a mail service is connected, emails (password reset and verification) are written to the server log instead of being sent. Ask HR or the system administrator for help.

---

## 5. For Heads of Department

### Approve or reject leave

1. Open **Approvals**. It lists requests from your department waiting for you.
2. Click a name to see that person's details and leave balance.
3. Click **Approve** to send it on to HR, or **Reject** and give a reason.

You can only act on requests from **your own department**. Your own leave goes to HR, not to you.

### Department history

**Dept. History** shows every past request in your department and what happened to it.

Heads **can't see salaries**, not even for their own staff.

---

## 6. For HR and Admin

### Final leave approval

Open **All Leave**. It lists every request in the company. Search by name, or filter by status, type, department or dates. Requests marked **Awaiting HR** need your decision: **Approve** or **Reject** (with a reason). You can only approve a request after the Head has approved it, or if it skipped the Head step.

### Employees

Open **Employees**.

- **Search and filter** by name, department, rank or skill, and sort the list. Click a person for their full profile and leave history.
- **Add employee:** enter their name, work email, a temporary password (at least 8 characters), date of birth, department, date hired, role, rank, skills and salary. The **Employee ID** is created automatically from the department (for example 100001).
- **Edit:** change any of these except the password, which only the person changes.
- **Delete:** the person can no longer sign in, and their pending requests are cancelled. Their past leave stays on record.
- **Deleted tab:** shows removed employees. Click **Restore** to bring someone back. Their department must exist.

### Departments

Open **Departments**.

- Add, rename, describe and delete departments. Names must be unique.
- A department can sit **inside another** (for example Housekeeping inside Operations). A department can't be placed inside itself or inside one of its own sub-departments.
- Deleting a parent department moves its sub-departments up one level.
- A department **with employees can't be deleted**. Move them first.
- Deleted departments can be restored.

### Skills

Open **Skills** from the menu under your name. Add, rename or delete skills. On an employee's form, give each skill a level: **beginner**, **intermediate** or **expert**. Deleting a skill removes it from everyone who had it.

### Public holidays and leave settings

Open **Leave settings** from the menu under your name.

- **Rollover:** turn it on or off and set the maximum days carried over.
- **Holidays:** add one at a time (date and name), or **import** a whole year:
  1. Download the **Excel** or **CSV** template.
  2. Fill in one holiday per row under the headings **date** and **name**. Dates like `2027-04-13` or `13/04/2027` work.
  3. Upload the file. You see a **preview** first, and nothing is saved until you confirm.
  - A date that already exists has its name replaced. New dates are added. Other saved holidays stay.
  - At most 500 rows per file, up to 1 MB, as .xlsx or .csv.
- **Clear all** removes every holiday, for example before uploading a new year.
- Holidays are **plain dates**, not repeated automatically, because they change each year. Upload each year's list.

### Activity log

Open **Activity log** from the menu under your name. It shows who created, changed, deleted or restored what, with the old and new values, plus every sign-in. Search, or filter by person, action, record type and dates. Passwords are never shown.

### Reports

- **All Leave** and **Employees:** click **Export CSV** or **Export PDF**. The export uses the filters you have on screen. The Employees CSV includes salaries.
- **An employee's profile:** download their **leave record** as a PDF.

### Admin only

Only an Admin can create Admin accounts, give someone the Admin role, or edit or remove an Admin. HR manages everyone else.

---

## 7. Salary

Each employee can have a **monthly base salary** in Thai baht.

| | Sees salary | Can change it |
| --- | --- | --- |
| Admin | Everyone's | Everyone's except their own |
| HR | Everyone's | Everyone's except their own and Admins' |
| Head of Department | **Only their own** | No |
| Employee | **Only their own** | No |

- Set it on the employee form (**Monthly salary (THB)**). It is optional and can be left empty.
- On your own record the field is locked: ask another HR member or an Admin.
- You see your own salary on your **Profile** page.
- Every change appears in the Activity log with the old and new amount.
- This is a salary record, not a payroll system: it doesn't calculate tax, deductions or payslips.

---

## 8. Safeguards: things the system won't let you do

These rules keep the system usable. If you try one, Crewly explains why and what to do instead.

- **There is always at least one Admin and one HR.** The last of either can't be deleted or given another role. Give someone else the role first.
- **A department with staff always has a Head.** Its only Head can't be demoted, moved or deleted while others work there. Make someone else Head first.
- **HR and Admin can't be Heads.**
- **A department with employees can't be deleted.**
- **Employee IDs are never reused**, even after someone is deleted.
- **No one changes their own salary**, and only an Admin manages Admin accounts.
- **Leave:** no overlapping requests, no more than your allowance, no requests on weekends or holidays only, and annual or professional leave can't start in the past.

---

## 9. Common questions

**I can't see a page someone else can.**
Pages depend on your role. For example, only Heads see Approvals, and only HR and Admin see Employees. Ask HR if your role looks wrong.

**My leave request was refused when I submitted it.**
Read the message: it explains the reason. Usually the dates overlap another request, you don't have enough days left, or the dates are only weekends or holidays.

**My request is stuck on "Awaiting Head".**
Your Head hasn't acted yet. If your department has no Head, requests go straight to HR instead.

**I didn't get the email to reset my password or verify my email.**
Check your spam folder. If email isn't set up on the server yet, ask HR to help you.

**Who can see my salary?**
Only you, HR and Admin. Your Head of Department can't.

**I deleted someone by mistake.**
HR or Admin can restore them from the **Deleted** tab on the Employees page.
