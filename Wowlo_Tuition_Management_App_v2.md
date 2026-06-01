# Wowlo — Tuition Management App
## Full Product Document — Version 2.0

> **Tech Stack:** Laravel · Blade · Tailwind CSS · Alpine.js · PostgreSQL · PWA

---

## Table of Contents

1. [Idea & Requirements](#1-idea--requirements)
2. [User Flow](#2-user-flow)
3. [Wireframe](#3-wireframe)
4. [Design (UI / UX Decisions)](#4-design-ui--ux-decisions)
5. [Tech Stack & Architecture Decisions](#5-tech-stack--architecture-decisions)
6. [Database Planning](#6-database-planning)
7. [Code Architecture](#7-code-architecture)
8. [Testing](#8-testing)
9. [Deployment](#9-deployment)
10. [Improvement & Phased Growth](#10-improvement--phased-growth)

---

## 1. Idea & Requirements

### Problem Statement

This app centralises homework assignments and tutor–student communication in one secure platform, so tutors, students and parents can clearly see assigned homework, main details and messages anytime without confusion. It also eliminates manual tuition fee calculation and WhatsApp billing.

### What Problem Does the App Solve?

- Tutors manage homework via WhatsApp/In Person — messages get buried
- Parents/students forget homework
- No central place to see assigned homework or read tutor messages
- Tutor wastes time repeating same instructions
- Tutor cannot remember each student's learning progress
- Calculating tuition fees manually and sending individually via WhatsApp is time-consuming

> ⚠️ **IMPORTANT:** Only doing MVP for now. To be done in phases.

---

### Who Are the Users?

#### 1. Student / Parent (same login role)
- Logs in
- Sees: Profile, Assigned Homework, Tutor Messages, Tuition Fees & Details, Outstanding Payment, Remarks
- Cannot see other students' data

#### 2. Tutor / Admin
- Creates homework (with attachments)
- Assigns homework to students
- Sends messages via in-app inbox
- Manages users (students)
- Notifies for payment collection / outstanding payment
- Manages tuition schedule

---

### Core Features (MVP — Must Have)

#### Student / Parent Features
- Login (Email/Password + Google OAuth)
- Dashboard (Summary)
- Homework list & details
- Messages from tutor (read-only)
- Payment / Outstanding Payment (hidden by default — unlocked via parent password)
- Profile (name, address, email, phone numbers)
- Mark homework as Done / Not Done
- View & download past year exam papers (filter by subject / year)
- Receive push notifications (via PWA) for new homework and messages
- Take quizzes assigned by tutor (MCQ only in Phase 1)
- View quiz results and marks

#### Tutor / Admin Features
- Login (Email/Password + Google OAuth)
- Create student accounts
- Assign homework with title, subject, description, due date, attached files
- Send messages to individual students
- View homework status per student
- Manage tuition fees & record payments
- Upload past year exam papers (filterable by subject and year)
- WhatsApp billing message generator (generate payment request message to copy & send to parents)
- Create and assign quizzes to students (MCQ only in Phase 1, filtered by subject/level/topic/exam type)

---

### Features NOT in MVP (Future Phases)

- Attendance tracking (Phase 2)
- Exams & Report Cards — auto-generated (Phase 2)
- Short Answer Quiz with AI marking (Phase 2)
- SMS
- Reports & Backup/Export
- Staff salary
- Batch management
- Parent–student separate roles
- Message replies (students)
- Edit profile (student self-edit)
- PWA offline mode (Phase 2)
- Native mobile app — React Native (Phase 3, only if needed)

---

### Platform Scope

| Phase | Scope |
|---|---|
| Phase 1 (Now) | Wowlo Web App — Laravel + Blade + Tailwind CSS + Alpine.js + PWA |
| PWA (Phase 1) | Progressive Web App — installable on phone, push notifications for parents/students |
| Responsive | Mobile-responsive design (parents mainly use phone browser) |
| Phase 2+ | PWA enhancements — offline mode, advanced caching |
| Phase 3 (Future) | Native mobile app (React Native) — only if genuinely needed |

> 🎯 **App Goal:** A secure tuition management web app where students log in to view assigned homework and tutor messages, and tutors can manage homework assignments and payment collections efficiently.

---

## 2. User Flow

### Authentication Flow (All Users)

1. User lands on Landing Page (Wowlo homepage)
2. Clicks Login
3. Enters Email + Password (or clicks Google OAuth)
4. System verifies credentials
5. Redirect based on role: Student → Student Dashboard, Tutor → Tutor Dashboard
6. If invalid → show error message
7. First login → PWA install prompt shown once

> 📌 No public registration — accounts are created by the tutor/admin only.

---

### Student / Parent User Flow

**Dashboard (Landing Page after login)**
After login, student sees:
- Upcoming homework (next due date)
- Latest tutor message
- Quick navigation menu
- Outstanding payment summary

**Homework Flow**
1. Click Homework
2. See Homework List — Title, Subject, Due Date, Status (Pending / Done)
3. Click a homework item
4. See Homework Details — Full description, due date, attachments, start date
5. Mark homework as Done / Not Done

**Messages Flow**
1. Click Messages
2. See Inbox — list of messages from tutor
3. Click message → Read message details
4. Cannot reply (one-way in MVP)

**Profile Flow**
- View: Name, Parent's Name, Email, Phone numbers (Student / Father / Mother / NOK / Home), Address
- Confidentiality notice displayed
- Edit profile — Phase 2

**Tuition Fee Flow**
- Only accessible after parent enters the special fee unlock password
- View: Monthly fee, payment history, outstanding balance
- Cannot edit — read only for parents

---

### Tutor / Admin User Flow

**Dashboard (Landing Page after login)**
- Total students count
- Pending homework count
- Recent activity log

**Student Management Flow**
1. Click Students → See Student List
2. Click Add Student
3. Enter: Name, Email, Password, Contact details, Address
4. Save → Student account created
5. Edit / Delete student profile

**Homework Management Flow**
1. Click Homework → See Homework List
2. Click Create Homework
3. Enter: Title, Subject, Description, Due Date, Assigned Student, Attachment (optional)
4. Save → Homework visible to assigned student immediately

**Messaging Flow**
1. Click Messages → Click Compose Message
2. Select student → Write subject + message body
3. Send → Message appears in student inbox

### Key Rules

> ⚠️
> - Students only see their own data
> - Tutors see all students
> - No batch logic in MVP
> - No in-app payment processing — only fee viewing and recording
> - No WhatsApp/SMS integration in MVP

---

## 3. Wireframe

### Global Layout (All Authenticated Pages)
- **Top Navigation / Header:** App name (Wowlo), logged-in user name, logout button
- **Side Navigation (role-based):** menu items change based on user role
- **Main Content Area:** page-specific content

---

### Landing Page (Public — Homepage) ✦ Phase 1 MVP

> MVP: Simple landing page. Full marketing site (About Us, Courses, Contact) in Phase 2.

- Wowlo logo + tagline (warm, encouraging)
- Hero section: headline + short description + Login button
- Brief features overview (3 key benefits)
- Footer: Privacy Policy link
- Navigation: Login button only in MVP

### Login Page (Public)
- Wowlo logo
- Email input
- Password input
- Google OAuth login button
- Login button
- Error message area
- Forgot Password link
- Back to Home link

### Privacy Policy Page (Public)
- Required for PDPA compliance (Singapore)
- What data is collected and why
- How data is stored and protected
- Contact information for data queries

---

### Student / Parent Pages

#### Student Dashboard
- Welcome message
- Upcoming homework (max 3)
- Latest tutor message
- Navigation: Homework, Messages, Profile, Tuition Fee & Details

#### Homework List Page
- Page title: My Homework
- Homework table: Title, Subject, Due Date, Status
- Clickable homework items

#### Homework Detail Page
- Homework title, Subject
- Description (long text)
- Start date / Due date
- Status indicator
- Attachment file (if any)
- Mark as Done button

#### Messages Inbox Page
- Page title: Messages
- Message list: Subject/preview, Date
- Clickable message items

#### Message Detail Page
- Message subject, Sender (Tutor), Date sent, Full message body

#### Profile Page
- Name, Parent Name, Email
- Phone numbers (Student, Father, Mother, NOK, Home)
- Address
- Confidentiality notice
- Read-only in MVP

#### Tuition Fee Page
- Password-protected entry
- Monthly fee amount
- Payment history
- Outstanding balance (calculated)

#### Exam Papers Page
- Filter by Subject dropdown
- Filter by Year dropdown
- Papers list: Title, Subject, Year, Download button
- All students see all papers

#### Quiz List Page
- List of assigned quizzes
- Status: Not Started / In Progress / Completed
- Score shown after completion

#### Take Quiz Page
- MCQ: select A/B/C/D
- Submit button
- Cannot change answers after submit

#### Quiz Result Page
- Total score: X / Y marks
- Per question: correct/wrong indicator
- Corrections section: student can write corrections on-screen for wrong answers

#### PWA Install Prompt
- Shown once after first login only
- Banner: "Add Wowlo to your home screen for a better experience!"
- Install button + Dismiss button
- Never shown again after dismissed
- Uses browser `beforeinstallprompt` event

---

### Tutor / Admin Pages

#### Tutor Dashboard
- Total students count, Pending homework count, Recent homework created
- Navigation: Students, Homework, Messages, Homework Status, Finance

#### Student List Page
- Student table: Name, Email
- Add Student button, Clickable student rows

#### Add / Edit Student Page
- Name, Email, Password inputs
- Phone number inputs x5 (Student, Mother, Father, NOK, Home)
- Address input, Save button

#### Homework List (Tutor)
- Homework table: Title, Student, Due Date, Status
- Sort & filter by all columns
- Delete homework button, Create Homework button

#### Create / Edit Homework Page
- Title, Subject, Description textarea
- Due date picker, Student dropdown
- Attachment upload, Save button

#### Messages Page (Tutor)
- Sent message list, Sort/filter
- Compose Message button

#### Compose Message Page
- Student dropdown, Subject input, Message textarea, Send button

#### Homework Status Page
- Student dropdown
- Homework list with status: Pending / Done

#### Finance Page
- Student dropdown, Tuition fee details
- Record payment form, Payment history, Outstanding balance

#### Exam Papers Page (Tutor)
- Upload paper form: Title, Subject, Year, File upload, Remarks
- List of all uploaded papers, Delete paper button

#### Quiz List Page (Tutor)
- All quizzes created, Create Quiz button
- View results per student

#### Create Quiz Page (Tutor)
- Title, Subject, Level, Topic, Exam Type dropdowns
- Add questions: question text, 4 options, correct answer, marks
- Save and publish button

#### WhatsApp Billing Page (Tutor) ✦ Updated (post-v2.1)
- Select student dropdown → auto-loads their `fee_rate_per_hour`
- Select billing **month**
- **Itemised lessons** — tutor adds one line per lesson: **date + actual hours**. App computes each line = `fee_rate_per_hour × hours` (hours vary per lesson). Add/remove lines as needed.
- **Additional charges (optional, repeatable)** — description + amount each (e.g. "Assessment book — SGD 12.50")
- **Outstanding balance** — carried-over unpaid amount (entered by tutor in MVP)
- **Live grand-total** preview as lines are added
- **Generated WhatsApp message** preview
- **Copy to clipboard** button (PayNow number included)

**Calculation:**
```
per_lesson_amount = fee_rate_per_hour × actual_hours_of_that_lesson
lessons_subtotal  = Σ per_lesson_amount  (sum of all lesson lines)
grand_total       = lessons_subtotal + additional_charges + outstanding_balance
```

**Generated WhatsApp message template:**
```
Hi! Here is the tuition fee for {Student Name} — {Month}.

Lessons ({rate}/hr):
1) {date} — {hours}h = ${amount}
2) {date} — {hours}h = ${amount}
...
Lessons subtotal: ${lessons_subtotal}

Additional charges:
- {description}: ${amount}      (only if any)

Outstanding balance: ${outstanding}   (only if any)

*Grand total due: ${grand_total}*

PayNow: {paynow_number}
Thank you!
```

> 📌 **Open decision for build (Finance slice):** whether to **persist** these lessons/bills (recommended — a `lessons` and/or `bills` table) so billing history is kept and outstanding can be auto-derived, OR keep the page as a pure ephemeral calculator+generator with tutor-entered outstanding. To be decided when the Finance slice is built.

---

## 4. Design (UI / UX Decisions)

### Design Goals

> Simple · Clean · Warm · Encouraging · No distractions · Easy for parents & students (non-tech users)
>
> 📌 Think: tuition parents, not developers.

### Target Users

| User | Design Implication |
|---|---|
| Parents | 30–55 years old. Need clarity and simplicity. |
| Students | Primary / Secondary school age. Simple UI. |
| Tutor | Daily heavy usage. Needs efficiency. |

---

### 🎨 Colour Scheme

> ✦ Updated to match Wowlo brand: warm, encouraging and connected vibe.

| Colour Role | Purpose & Reasoning |
|---|---|
| Primary — Warm Violet / Purple | Creative, educational, inspiring — feels like growth and learning |
| Background — Soft Cream / Off-white | Warmer than pure white, easier on eyes for long study sessions |
| Accent — Amber / Golden Yellow | Achievement, motivation, encouragement — reward feeling |
| Success — Soft Green | Homework done, payment confirmed states |
| Error — Soft Red | Error messages only |

> 📌 Reference apps using similar warm palette: Duolingo, Notion, Khan Academy

---

### ✍️ Typography

> ✦ Font updated to Nunito — rounded, friendly and warm with real personality.

- **Font:** Nunito (Google Fonts — free)
- **Headings:** Nunito Bold — warm, clear, strong
- **Body text:** Nunito Regular — easy to read for long sessions
- **Labels/captions:** Nunito Light — subtle, clean
- No decorative fonts — readability is priority

---

### Layout Rules
- Cards-based dashboard layout — each card = one purpose
- One-column forms — clear labels above inputs
- Big clickable buttons (parents using one hand on phone)
- Fixed sidebar on desktop, collapsible drawer on mobile
- Tables become stacked cards on mobile
- Full-width buttons on mobile

### Status & Feedback Design

| Element | Design |
|---|---|
| Homework Pending | Grey / Orange indicator |
| Homework Done | Green indicator |
| Message Unread | Bold text |
| Message Read | Normal text weight |
| Error states | Red text, simple plain language |

### Accessibility
- High contrast text
- Clickable areas not too small
- No reliance on colour alone — use text + colour for status
- Enlarged elements on hover

---

### 🖼️ App Logo ✦ Phase 1 MVP

> ✅ Official Wowlo logo to be used across the app, landing page, PWA icon, and all branding.

**Logo file:** `wowlo_logo.png`
- Red icon (triangle/connector shape representing tutor–student–parent connection)
- WOWLO text
- SINCE 2026

> ⚠️ **IMPORTANT LOGO NOTE:** The square grid lines visible in the logo source file are the design canvas/grid from the design tool — they are **NOT** part of the actual logo.
>
> When using this logo:
> - Export logo on a **transparent background (PNG)** — grids will disappear
> - Ask designer to export final version without the grid
> - Final logo = red icon + WOWLO text + SINCE 2026 text only
> - Background should be transparent for use on any colour surface

---

### 🔖 Favicon ✦ Phase 1 MVP (post-v2.1)

> ✅ A dedicated favicon is used for the browser tab / bookmarks (distinct from the main logo PNG).

**Favicon file:** `public/images/favicon/wowlo_favicon.ico` (16×16 `.ico`)
- Added to the `<head>` of all pages via `<link rel="icon" type="image/x-icon" href="{{ asset('images/favicon/wowlo_favicon.ico') }}">`
- Wired into: `layouts/app.blade.php`, `layouts/guest.blade.php`, `welcome.blade.php`
- Will also serve as the basis for the PWA icon set later.

---

### 🎨 Design Reference ✦ Phase 1 MVP

> ✅ This design reference applies to the Wowlo landing page and overall app UI for Phase 1.

**Source:** https://dribbble.com/shots/27108318-Friendly-Conversion-focused-Tuition-Centre-Website

Key design elements to take inspiration from:
- Warm colour palette with colourful accent dots
- Clean hero section with strong headline and CTA buttons
- Friendly student imagery
- Stats section for credibility (students, satisfaction rate)
- Navigation: Home, About Us, Courses, Login

---

## 5. Tech Stack & Architecture Decisions

### Final Tech Stack

| Component | Choice | Reason |
|---|---|---|
| Backend | Laravel (PHP) | Built-in auth, MVC, security defaults, perfect for CRUD apps |
| Frontend | Laravel Blade | Server-side rendering, faster MVP, no API layer needed |
| Styling | Tailwind CSS | Fast prototyping, consistent design, mobile-first utilities |
| Interactivity | Alpine.js | Lightweight (15kb), Blade-native, handles modals/toggles without React complexity |
| Database | PostgreSQL (Neon) | Free generous tier, scale-to-zero, works perfectly with Laravel Eloquent |
| Authentication | Laravel Breeze + Socialite | Session auth + Google OAuth in one setup |
| PWA | Service Worker + Web Manifest | Makes app installable on phone, enables push notifications |
| Push Notifications | laravel-notification-channels/webpush | Notifies parents/students on new homework and messages |
| App Hosting | Render (free tier) | Easy Laravel deploy, free, upgradeable to $7/mo when needed |
| Keep-Alive | UptimeRobot | Pings app every 5 min to prevent Render cold starts — free |
| DB Hosting | Neon | 100 free projects, 0.5GB each, scale-to-zero, never deletes on free tier |

### Why Blade Over React?

This is a dashboard/admin tool — not a flashy consumer app. Parents and students care about clarity and ease of use, not animations.

- Blade + Tailwind can achieve all required UI patterns (cards, tables, forms, sidebars)
- Alpine.js handles all small interactive needs without React complexity
- One codebase, one deployment, faster MVP
- Can always migrate to React later — Laravel backend stays untouched

### Why Neon Over MySQL / Supabase?

| | Neon ✅ (Chosen) | Supabase | MySQL (Old) |
|---|---|---|---|
| Free projects | 100 | 2 | Local only |
| Storage | 0.5GB per project | 500MB total | N/A |
| Idle behaviour | Scale-to-zero | Pauses after 7 days | N/A |
| Best for | Irregular traffic ✅ | Extra features not needed | Not production-suitable |

### Hosting Setup

| Component | Service | Cost |
|---|---|---|
| Laravel App | Render | Free tier |
| Keep-Alive | UptimeRobot | Free forever (personal use) |
| Database | Neon | Free tier |
| **Total** | | **$0/month** |

> Upgrade to Render Starter ($7/month) when real students use it daily.

### Environment Variables (.env)

```
APP_NAME=Wowlo
APP_URL=https://yourapp.onrender.com
APP_DEBUG=false

DB_CONNECTION=pgsql
DB_HOST=<neon-host>
DB_DATABASE=<neon-db-name>
DB_USERNAME=<neon-user>
DB_PASSWORD=<neon-password>

GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT_URI=https://yourapp.onrender.com/auth/google/callback

FEE_VIEW_PASSWORD=your-secret-parent-password

VAPID_PUBLIC_KEY=your-vapid-public-key
VAPID_PRIVATE_KEY=your-vapid-private-key
VAPID_SUBJECT=mailto:your@email.com
```

---

## 6. Database Planning

> **Database:** PostgreSQL hosted on Neon
> **ORM:** Laravel Eloquent
> **Tables:** 12 core tables

### Core Tables

1. `users`
2. `homeworks` ✦ (fully defined)
3. `homework_statuses`
4. `messages`
5. `tuition_fees`
6. `payments`
7. `exam_papers` ✦ (new)
8. `push_subscriptions` ✦ (new — PWA)
9. `quizzes` ✦ (new)
10. `quiz_questions` ✦ (new)
11. `quiz_attempts` ✦ (new)
12. `quiz_answers` ✦ (new)

---

### 1. users Table

| Column | Type | Notes |
|---|---|---|
| id | Primary Key | Auto-increment |
| name | string | Full name |
| email | string (unique) | Login email |
| password | string (hashed) | bcrypt hashed |
| google_id | string (nullable) ✦ | Google OAuth ID — required for Google login |
| role | enum: student \| tutor | Role-based access |
| phone_1 | string (nullable) | Student's own number |
| phone_2 | string (nullable) | Father's number |
| phone_3 | string (nullable) | Mother's number |
| phone_4 | string (nullable) | Next of kin |
| phone_5 | string (nullable) | Home phone (optional) |
| address | text (nullable) | Home address |
| created_at | timestamp | Auto |
| updated_at | timestamp | Auto |

> 📌 `google_id` is nullable — only populated when user logs in via Google OAuth.
> ⚠️ At least one phone number must be filled. If none provided, throw a validation error.

---

### 2. homeworks Table ✦ Phase 1 MVP

| Column | Type | Notes |
|---|---|---|
| id | Primary Key | Auto-increment |
| tutor_id | FK → users.id | The tutor who created the homework |
| student_id | FK → users.id | The student it's assigned to |
| title | string | Homework title |
| subject | string | Subject (e.g. Maths, English) |
| description | text | Full homework instructions |
| due_date | date | When homework is due |
| attachment_path | string (nullable) | File path in Laravel storage — Phase 1 MVP |
| created_at | timestamp | Auto (used as 'given date') |
| updated_at | timestamp | Auto |

---

### 3. homework_statuses Table

| Column | Type | Notes |
|---|---|---|
| id | Primary Key | Auto-increment |
| homework_id | FK → homeworks.id | |
| student_id | FK → users.id | |
| status | enum: pending \| done | Default: pending |
| updated_at | timestamp | Auto |

---

### 4. messages Table

| Column | Type | Notes |
|---|---|---|
| id | Primary Key | |
| sender_id | FK → users.id | Tutor |
| receiver_id | FK → users.id | Student/Parent |
| subject | string | Message subject |
| body | text | Full message content |
| is_read | boolean | Default: false |
| created_at | timestamp | Auto |

---

### 5. tuition_fees Table

> ✦ **Updated (post-v2.1):** Billing is now **per lesson by actual hours**, not a flat monthly fee. The table stores the hourly rate per student; the actual bill is built from itemised lessons on the WhatsApp Billing page.

| Column | Type | Notes |
|---|---|---|
| id | Primary Key | |
| student_id | FK → users.id | One fee structure per student |
| fee_rate_per_hour | decimal(8,2) | ✦ Hourly rate (e.g. 50.00). Replaces `monthly_fee`. |
| currency | string | Default: SGD |
| remarks | text (nullable) | Any special notes |
| created_at | timestamp | Auto |
| updated_at | timestamp | Auto |

> 📌 Per-lesson amount = `fee_rate_per_hour × actual_hours_of_that_lesson` (hours vary per lesson). `billing_cycle` and `start_date` removed — they assumed fixed monthly/weekly billing which no longer applies.

---

### 6. payments Table

| Column | Type | Notes |
|---|---|---|
| id | Primary Key | |
| student_id | FK → users.id | |
| amount_paid | decimal(8,2) | Actual amount paid |
| payment_date | date | |
| payment_method | enum: cash\|bank_transfer\|paynow\|paypal | |
| remarks | text (nullable) | Any notes |
| created_at | timestamp | Auto |

> ⚠️ **Outstanding payment is NOT stored — it is CALCULATED dynamically.**
>
> `Outstanding = (Total Tuition Fee Due) - (Total Payments Made)`
>
> ✦ **Updated (post-v2.1):** "Total Tuition Fee Due" is now the sum of lessons conducted (`Σ fee_rate_per_hour × hours`) plus any additional charges — not `monthly_fee × periods`. Whether this is auto-derived (requires persisting lessons/bills) or tutor-entered is an open decision for the Finance slice (see WhatsApp Billing Page).

---

### 7. exam_papers Table ✦ Phase 1 MVP (New)

| Column | Type | Notes |
|---|---|---|
| id | Primary Key | Auto-increment |
| tutor_id | FK → users.id | The tutor who uploaded the paper |
| title | string | e.g. 2023 PSLE Maths Paper 1 |
| subject | string | e.g. Maths, English, Science |
| year | integer | e.g. 2023, 2022 — used for filtering |
| file_path | string | Path in Laravel storage |
| remarks | text (nullable) | Optional notes about the paper |
| created_at | timestamp | Auto |
| updated_at | timestamp | Auto |

> 📌 Access: All students and parents can view ALL exam papers. Tutor manages uploads.
> 📌 Filters: By subject and/or year. Both filters can be combined.

---

### 8. push_subscriptions Table ✦ Phase 1 MVP (PWA)

| Column | Type | Notes |
|---|---|---|
| id | Primary Key | Auto-increment |
| user_id | FK → users.id | The user who subscribed |
| endpoint | text | Browser push endpoint URL |
| public_key | text | VAPID public key for this subscription |
| auth_token | text | Auth token for this subscription |
| created_at | timestamp | Auto |
| updated_at | timestamp | Auto |

> 📌 Package: `laravel-notification-channels/webpush` handles this automatically.
> 📌 VAPID keys stored in `.env`: `VAPID_PUBLIC_KEY` and `VAPID_PRIVATE_KEY`.
> 📌 When to notify: New homework assigned, new message received.

---

### 9. quizzes Table ✦ Phase 1 MVP (New)

| Column | Type | Notes |
|---|---|---|
| id | Primary Key | Auto-increment |
| tutor_id | FK → users.id | The tutor who created the quiz |
| title | string | e.g. P4 Science WA1 Chapter 3 |
| subject | string | e.g. Science, Maths, English |
| level | string | e.g. Primary 4, Primary 5 |
| topic | string | e.g. Photosynthesis, Fractions |
| exam_type | enum: WA1\|MidYear\|WA2\|EndYear | Singapore primary school exam schedule |
| created_at | timestamp | Auto |
| updated_at | timestamp | Auto |

---

### 10. quiz_questions Table ✦ Phase 1 MVP (New)

| Column | Type | Notes |
|---|---|---|
| id | Primary Key | |
| quiz_id | FK → quizzes.id | |
| question_text | text | The question |
| question_type | enum: mcq\|short_answer | Phase 1: mcq only. Phase 2: short_answer |
| option_a | string (nullable) | MCQ option A |
| option_b | string (nullable) | MCQ option B |
| option_c | string (nullable) | MCQ option C |
| option_d | string (nullable) | MCQ option D |
| correct_answer | string | MCQ: A/B/C/D. Short answer: full correct answer text |
| marks | integer | Marks for this question |
| order | integer | Display order in quiz |
| created_at | timestamp | Auto |

---

### 11. quiz_attempts Table ✦ Phase 1 MVP (New)

| Column | Type | Notes |
|---|---|---|
| id | Primary Key | |
| quiz_id | FK → quizzes.id | |
| student_id | FK → users.id | |
| total_marks | integer | Total possible marks |
| obtained_marks | integer | Marks student scored |
| completed_at | timestamp (nullable) | Null if still in progress |
| created_at | timestamp | Auto |

---

### 12. quiz_answers Table ✦ Phase 1 MVP (New)

| Column | Type | Notes |
|---|---|---|
| id | Primary Key | |
| attempt_id | FK → quiz_attempts.id | |
| question_id | FK → quiz_questions.id | |
| student_answer | text | What the student answered |
| is_correct | boolean | MCQ: auto-checked. Short answer: AI-checked (Phase 2) |
| marks_awarded | integer | 0 or full marks per question |
| ai_feedback | text (nullable) | Phase 2 only — AI marking explanation |
| created_at | timestamp | Auto |

> 📌 **Phase 1 — MCQ only:** `is_correct` checked by comparing `student_answer` to `correct_answer` directly. No AI needed.
> 📌 **Phase 2 — Short Answer:** Send question + correct_answer + student_answer to Google Gemini API (free tier, 1500 calls/day). Fallback: Claude Haiku API (~$0.001 per question).
> 📌 **Corrections:** After completing quiz, student can review wrong answers on-screen and write corrections.

---

### Outstanding Payment Design Decision

> ⚠️ Outstanding payment is **NOT a stored column** — it is **CALCULATED dynamically.**
>
> `Outstanding = (Total Tuition Fee Due) - (Total Payments Made)`
>
> Prevents data inconsistency. Always accurate. Industry best practice.
>
> ✦ **Updated (post-v2.1):** With per-lesson billing, "Total Tuition Fee Due" = `Σ(fee_rate_per_hour × lesson_hours) + additional charges`. The v2.1 monthly-accrual formula (`monthly_fee × periods`) is **superseded**.

### Fee Unlock Design Decision

> Fee section hidden from students by default. Parent enters a global password to unlock.
>
> Store as `FEE_VIEW_PASSWORD` in `.env`
>
> Phase 2 upgrade: Per-student unlock password if needed.

---

### Relationships Summary

| Model | Relationship | Related Model |
|---|---|---|
| User (Student) | hasMany | Homeworks |
| User (Student) | hasMany | HomeworkStatuses |
| User (Student) | hasMany | Payments |
| User (Student) | hasOne | TuitionFee |
| User (Student) | hasMany | Messages (received) |
| User (Tutor) | hasMany | Homeworks (created) |
| User (Tutor) | hasMany | Messages (sent) |
| User (Tutor) | hasMany | ExamPapers (uploaded) |
| User (Tutor) | hasMany | Quizzes (created) |
| User (Student) | hasMany | QuizAttempts |
| TuitionFee | belongsTo | User (Student) |
| Payment | belongsTo | User (Student) |
| Homework | belongsTo | User (Student) + User (Tutor) |
| Homework | hasOne | HomeworkStatus |
| ExamPaper | belongsTo | User (Tutor) |
| User | hasMany | PushSubscriptions |
| Quiz | hasMany | QuizQuestions |
| Quiz | hasMany | QuizAttempts |
| QuizAttempt | hasMany | QuizAnswers |
| QuizQuestion | hasMany | QuizAnswers |

### Security & Visibility Rules

| Action | Student | Parent | Tutor |
|---|---|---|---|
| View homework | ✅ | ✅ | ✅ |
| Create homework | ❌ | ❌ | ✅ |
| Upload attachment | ❌ | ❌ | ✅ |
| View fees | ❌ | ✅ (password) | ✅ |
| Record payment | ❌ | ❌ | ✅ |
| Manage students | ❌ | ❌ | ✅ |
| Send messages | ❌ | ❌ | ✅ |
| Mark homework done | ✅ | ✅ | ✅ |
| Take quiz | ✅ | ✅ | ✅ |
| Create quiz | ❌ | ❌ | ✅ |
| Upload exam papers | ❌ | ❌ | ✅ |
| View exam papers | ✅ | ✅ | ✅ |

---

## 7. Code Architecture

### Models

| Model File | Responsibility |
|---|---|
| `app/Models/User.php` | Auth, role checking, all relationships |
| `app/Models/Homework.php` | Homework data, belongs to student + tutor, has status |
| `app/Models/HomeworkStatus.php` | Tracks completion, belongs to homework + student |
| `app/Models/Message.php` | Tutor→Student comms, read/unread tracking |
| `app/Models/TuitionFee.php` | Expected monthly fee per student |
| `app/Models/Payment.php` | Actual payments, used to calculate outstanding |
| `app/Models/ExamPaper.php` | Past year exam papers — uploaded by tutor, viewed by all |
| `app/Models/PushSubscription.php` | PWA push notification subscriptions per user device |
| `app/Models/Quiz.php` | Quiz — belongs to tutor, has many questions and attempts |
| `app/Models/QuizQuestion.php` | Individual question — MCQ options + correct answer |
| `app/Models/QuizAttempt.php` | Student attempt — stores total and obtained marks |
| `app/Models/QuizAnswer.php` | Student answer per question — is_correct, marks, AI feedback |

### Controllers

| Controller File | Responsibility |
|---|---|
| `Auth/LoginController.php` | Email/password + Google OAuth login, logout |
| `DashboardController.php` | Load role-based dashboard data |
| `StudentController.php` | Tutor only — create/edit/delete student accounts |
| `HomeworkController.php` | Tutor creates/assigns; Student views/marks done |
| `MessageController.php` | Tutor sends; Student reads |
| `TuitionFeeController.php` | Tutor sets fee; Parent views with password |
| `PaymentController.php` | Tutor records payments; Parent views history + outstanding |
| `ExamPaperController.php` | Tutor uploads; Students/parents view and download; filter by subject + year |
| `PushSubscriptionController.php` | Handles PWA push subscription registration and notification dispatch |
| `QuizController.php` | Tutor creates quizzes + questions; Student takes quiz; Marks calculated automatically |
| `WhatsAppBillingController.php` | Generates formatted WhatsApp payment request message for tutor to copy and send |

### Routes Structure

```
routes/web.php

/                       → public landing page
/privacy                → privacy policy
/login                  → login page
/logout                 → logout
/auth/google            → Google OAuth redirect
/auth/google/callback   → Google OAuth callback

/student/*              → auth + role:student middleware
/tutor/*                → auth + role:tutor middleware
```

> 📌 Students CANNOT access tutor routes.
> 📌 Tutors CAN access student data for management.

### Middleware

| Middleware | Purpose |
|---|---|
| `RoleMiddleware.php` | Checks user role before allowing route access. Prevents URL hacking. |
| `FeePasswordMiddleware.php` | Checks if parent has entered the fee unlock password for this session. |

### Blade Views Structure

```
resources/views/
├── layouts/
│   └── app.blade.php           ← Header + Sidebar + Content slot
├── auth/
│   └── login.blade.php
├── public/
│   ├── landing.blade.php
│   └── privacy.blade.php
├── student/
│   ├── dashboard.blade.php
│   ├── homework/
│   │   ├── index.blade.php
│   │   └── show.blade.php
│   ├── messages/
│   │   ├── index.blade.php
│   │   └── show.blade.php
│   ├── exam-papers/
│   │   └── index.blade.php
│   ├── quiz/
│   │   ├── index.blade.php
│   │   ├── take.blade.php
│   │   └── result.blade.php
│   ├── finance/
│   │   └── index.blade.php
│   └── profile.blade.php
└── tutor/
    ├── dashboard.blade.php
    ├── students/
    │   ├── index.blade.php
    │   ├── create.blade.php
    │   └── edit.blade.php
    ├── homework/
    │   ├── index.blade.php
    │   ├── create.blade.php
    │   └── edit.blade.php
    ├── messages/
    │   ├── index.blade.php
    │   └── compose.blade.php
    ├── exam-papers/
    │   └── index.blade.php
    ├── quiz/
    │   ├── index.blade.php
    │   └── create.blade.php
    ├── whatsapp-billing/
    │   └── index.blade.php
    └── finance/
        └── index.blade.php
```

### Alpine.js Usage

| Feature | Alpine.js Use |
|---|---|
| Mark homework Done/Not Done | Toggle without full page reload |
| Sidebar mobile toggle | Show/hide sidebar on mobile |
| Modal popups | Confirm delete, fee password entry |
| Message read toggle | Mark as read without reload |
| Form validation feedback | Instant input validation UI |

### Business Logic Rules
- Controllers do logic — not views, not models
- Models handle data access and relationships
- Views only handle display
- No raw SQL in controllers — use Eloquent
- No logic in Blade views — keep them clean

---

## 8. Testing

### Manual Testing (Developer)

- Login / logout (email + Google OAuth)
- Student cannot access tutor pages (URL hacking test)
- Tutor cannot access student-only pages
- Homework assignment visibility (correct student only)
- Message delivery
- Tuition fee display + password lock
- Outstanding payment calculation
- File attachment upload and download
- Quiz MCQ marking accuracy
- WhatsApp billing message generation
- Exam paper upload and download
- PWA install prompt (shows once only)
- Push notifications delivery
- Mobile browser usability

### Role-Based Permission Testing

| Action | Student | Parent | Tutor |
|---|---|---|---|
| View homework | ✅ | ✅ | ✅ |
| Create homework | ❌ | ❌ | ✅ |
| Upload attachment | ❌ | ❌ | ✅ |
| View fees | ❌ | ✅ (password) | ✅ |
| Record payment | ❌ | ❌ | ✅ |
| Google OAuth login | ✅ | ✅ | ✅ |
| Take quiz | ✅ | ✅ | ✅ |
| Create quiz | ❌ | ❌ | ✅ |

### User Testing (Realistic)
- Ask 1 parent and 1 student to use the app
- Observe: confusion points, missed buttons, slow actions
- No feedback is too small — all noted for Phase 2

### Bug Handling Rules

| Type | Action |
|---|---|
| Critical bugs | Fix before launch — app cannot go live with these |
| Minor UI bugs | List for Phase 2 — not blockers |
| Feature creep | Do NOT add features during testing phase |

---

## 9. Deployment

### Deployment Stack

| Component | Service | Cost | Notes |
|---|---|---|---|
| Laravel App | Render | $0 (free tier) | Auto-deploy from GitHub. Spins down after 15 min idle. |
| Keep-Alive | UptimeRobot | $0 (free forever) | Pings app every 5 min. Prevents cold starts. |
| Database | Neon | $0 (free tier) | PostgreSQL. 0.5GB. Scale-to-zero. |
| Domain | Custom domain | ~$10-15/year (optional) | App works on render URL without custom domain. |
| SSL/HTTPS | Render built-in | $0 | Automatic SSL. No setup needed. |

### Keep-Alive Fallback Plan

Try each option in sequence:

| Priority | Service | Cost | Notes |
|---|---|---|---|
| 1st (Try first) | UptimeRobot | Free | uptimerobot.com — pings every 5 min. Personal use only. |
| 2nd (If fails) | cron-job.org | Free | cron-job.org — same function. No commercial restriction. |
| 3rd (If still fails) | Koyeb | Free | Switch app hosting entirely. Native PHP, no cold starts. |
| 4th (Last resort) | InfinityFree | Free | Traditional shared PHP hosting. MySQL included. |

### UptimeRobot Setup
1. Deploy app to Render → get URL (e.g. `myapp.onrender.com`)
2. Sign up at uptimerobot.com (free, no credit card)
3. Add new monitor → HTTP(S) monitor
4. Enter your Render app URL
5. Set check interval: every 5 minutes
6. Done — app stays awake 24/7 for free

### When to Upgrade Render ($7/month)
- You have consistent daily users
- Cold start delay starts annoying real students/parents
- You are actively collecting tuition fees
- You need guaranteed uptime

### Post-Deployment Checklist
- [ ] Login works (email + Google OAuth)
- [ ] Correct role redirection (student vs tutor)
- [ ] Homework visible to correct student only
- [ ] File attachments upload and download correctly
- [ ] Fee section hidden from students, unlockable by parent
- [ ] Outstanding payment calculation correct
- [ ] Messages deliver correctly
- [ ] Quiz MCQ marking works correctly
- [ ] WhatsApp billing message generates correctly
- [ ] Exam papers upload and download correctly
- [ ] PWA install prompt shows once after first login
- [ ] Push notifications deliver correctly
- [ ] Mobile browser usable
- [ ] UptimeRobot pinging correctly

---

## 10. Improvement & Phased Growth

### Phase 2 Features (Planned)
- Full marketing website: About Us, Courses page (Primary 1-6 / Secondary 1-5 / Subjects), Contact Us
- Attendance tracking
- Exams & report cards — auto-generated (for tutor portfolio and all parties to track student progress)
- Short Answer Quiz questions with AI marking (Google Gemini free tier primary, Claude Haiku fallback)
- Quiz PDF question extraction — extract questions from exam paper PDFs automatically
- Parent & student separate roles / logins
- Message replies (students can reply to tutor)
- Student self-edit profile
- PWA offline mode (cached pages work without internet)
- Per-student fee unlock password (upgrade from global)
- Push notification toggle settings (user can choose which notifications to receive)

### Phase 3 Features (Future)
- Native mobile app (React Native) — only if users genuinely demand it. PWA should cover most needs.
- SMS integration
- Reports & data export (PDF/Excel)
- Backup & restore
- Multi-tenant version for tuition centres
- Subscription plans for tutors (if productising for other tutors)

### Business Growth Possibilities
- Multi-tenant version for tuition centres
- Subscription plans for tutors
- White-label for tuition centres
- Staff salary management (if serving centres)

### Performance & Maintenance
- Bug fixes post-launch
- UI refinements based on user feedback
- Database optimisation as data grows
- Regular security updates (Laravel + PHP)
- Upgrade Render to paid tier when usage justifies it

---

### 💰 Pricing Strategy (Quiz AI Marking Cost)

**Recommended pricing: SGD $5 per student per month**

| Item | Cost |
|---|---|
| Google Gemini API (free tier) | Free — primary AI marker |
| Claude Haiku fallback | ~USD $0.001 per question = ~USD $0.08/student/month |
| Render hosting | Free tier |
| Neon database | Free tier |
| **Total costs (10 students)** | **~USD $0.80/month** |
| **Revenue (10 × SGD $5)** | **SGD $50/month** |
| **Net profit** | **~SGD $49/month** |

> SGD $5/student/month is very comfortable. Could even charge SGD $3 and still profit well.

---

*Wowlo — Tuition Management App — Full Product Document v2.0*
