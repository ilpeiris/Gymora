# Gymora | Rule-Based DSS Engine with RBAC, GDPR Audit Logging & BI Dashboard

![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=flat-square&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat-square&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=flat-square&logo=bootstrap&logoColor=white)
![Chart.js](https://img.shields.io/badge/Chart.js-4.x-FF6384?style=flat-square&logo=chartdotjs&logoColor=white)
![License](https://img.shields.io/badge/License-GPL%20v3-blue?style=flat-square)
![Status](https://img.shields.io/badge/Status-Active-brightgreen?style=flat-square)

> A full-stack PHP 8 / MySQL platform built around a deterministic Rule-Based Decision 
Support System (DSS) engine, a four-tier RBAC architecture, AES-256 encrypted data 
pipelines, automated GDPR audit logging, and a Chart.js-powered Business Intelligence 
dashboard  deployed across four isolated role portals with a shared real-time AJAX 
communication layer.

---

## 📌 Table of Contents

- [Overview](#overview)
- [Key Features](#key-features)
- [Tech Stack](#tech-stack)
- [System Architecture](#system-architecture)
- [Role Portals](#role-portals)
- [DSS Engine](#dss-engine)
- [Security & Data Governance](#security--data-governance)
- [Database Schema](#database-schema)
- [API Endpoints](#api-endpoints)
- [Project Structure](#project-structure)
- [Getting Started](#getting-started)
- [Screenshots](#screenshots)
- [Roadmap](#roadmap)

---

## Overview

Most gym management platforms (MindBody, ZenPlanner) are built purely for billing and class scheduling  they have no mechanism to handle medical data or enforce clinical safety rules.

**Gymora** solves this by placing clinical data at the centre of the gym workflow:

1. A **Doctor** conducts a structured digital health assessment for each member.
2. The **DSS engine** processes that assessment and generates a personalised set of safe/blocked exercises and class types.
3. A **Trainer** builds a workout plan using only DSS-approved activities.
4. The **Member** books classes and tracks progress  with unsafe options silently blocked by the system.
5. The **Admin** monitors platform health via a real-time BI analytics dashboard with full GDPR audit visibility.

---

## Key Features

### 🧠 Rule-Based Decision Support System
- Database-driven contraindication rules  fully configurable by admins without code changes
- Silently intercepts class bookings and workout plan assignments at the server layer
- Real-time AJAX DSS check fires on the client before a booking is confirmed
- Supports 20+ medical conditions mapped to blocked/allowed exercise classifications

### 🔐 Role-Based Access Control (RBAC)
- Four isolated portals: `user`, `doctor`, `trainer`, `admin`
- Central `requireRole()` gateway in `config/session.php`  every protected page enforces this
- Dynamic navbar renders only role-appropriate navigation links
- Unauthorized access attempts are logged and redirected

### 📋 Structured Clinical Assessments
- Doctors complete structured digital forms (dropdowns, checkboxes)  no free-text ambiguity
- Assessment data is machine-readable, feeding directly into the DSS engine
- Doctor notes stored AES-256 encrypted at rest
- Full patient history and medical report generation per member

### 📊 Business Intelligence Dashboard
- Admin BI dashboard powered by Chart.js via a dedicated JSON analytics API
- KPI metrics: total users, revenue, assessments submitted, DSS-blocked bookings
- Charts: injury distribution, class popularity, booking statuses, user growth
- CSV export for offline reporting

### 💬 Real-Time Messaging
- AJAX long-polling chat (4-second intervals) between Members ↔ Doctors and Members ↔ Trainers
- Incremental message fetch using `last_message_id`  avoids re-fetching full history
- Keyword-triggered audit log entries when medical topics are discussed

### 🔒 GDPR Audit Logger
- `logAudit()` fires automatically on every sensitive data interaction
- Records: `user_id`, `action`, `data_type`, `record_id`, `ip_address`, `user_agent`, `timestamp`
- Append-only table design  records are never updated or deleted
- Admin UI provides filterable audit log viewer

### 📈 Member Progress Tracking
- Weight and BMI trend charts (Chart.js) on the member dashboard
- Trainers and Doctors can view the same progress data from their own portals
- Progress entries logged via API endpoint with timestamp

---

## Tech Stack

| Layer | Technology | Purpose |
|---|---|---|
| Backend | PHP 8.3 | Server-side logic, routing, session management, DSS engine |
| Database | MySQL 8.0 | Relational storage for all domain entities |
| Frontend | Bootstrap 5 + Vanilla JS | Responsive UI, AJAX interactions, form validation |
| Data Viz | Chart.js | BI dashboard charts, progress graphs |
| Async | XMLHttpRequest / Fetch API | Real-time chat polling, live DSS booking check |
| Styling | Custom CSS (`gymora-theme.css`) | Brand-level overrides on Bootstrap 5 variables |
| Icons | Bootstrap Icons | Consistent icon set across all portals |
| Fonts | Google Fonts (DM Sans, Poppins) | Typography |
| DB Access | PDO (PHP Data Objects) | Parameterized queries, SQL injection prevention |
| Auth | Session-based (`$_SESSION`) | Stateful role-gated authentication |

---

## System Architecture

Gymora follows a classic **Three-Tier Client-Server Architecture**:

```
┌─────────────────────────────────────────────────────────────┐
│                    PRESENTATION TIER                        │
│   Bootstrap 5 · gymora-theme.css · Chart.js · Vanilla JS   │
│          Server-rendered PHP templates per role             │
└───────────────────────────┬─────────────────────────────────┘
                            │ HTTP/AJAX
┌───────────────────────────▼─────────────────────────────────┐
│                  APPLICATION LOGIC TIER                     │
│                                                             │
│  ┌─────────────┐  ┌──────────────┐  ┌──────────────────┐   │
│  │ RBAC Gateway│  │  DSS Engine  │  │  Audit Logger    │   │
│  │ requireRole()│  │ dss_engine.php│  │ logAudit()      │   │
│  └─────────────┘  └──────────────┘  └──────────────────┘   │
│                                                             │
│  Role Portals: /user  /doctor  /trainer  /admin  /api       │
└───────────────────────────┬─────────────────────────────────┘
                            │ PDO (parameterized)
┌───────────────────────────▼─────────────────────────────────┐
│                       DATA TIER                             │
│                     MySQL 8.0                               │
│   users · assessments · conditions · dss_rules · classes   │
│   bookings · plans · messages · audit_logs · packages       │
└─────────────────────────────────────────────────────────────┘
```

---

## Role Portals

### 👤 Member Portal (`/user`)
- Dashboard with package status, upcoming appointments, and DSS clearance summary
- Browse and book classes  blocked options display a reason from the DSS
- View personal medical report (read-only, doctor-issued)
- View trainer-assigned workout plan
- Weight/BMI progress tracker with Chart.js trend graphs
- Real-time chat with assigned Doctor and Trainer
- Membership package purchase

### 🩺 Doctor Portal (`/doctor`)
- Assigned patient list with assessment status
- Structured digital assessment form (BMI, BP, HR, conditions, dietary notes)
- AES-encrypted clinical notes field
- Patient history  full timeline of all previous assessments
- Medical report generation and print view
- Appointment calendar management
- Real-time chat with patients

### 🏋️ Trainer Portal (`/trainer`)
- Assigned member list with DSS restriction summaries
- **DSS-filtered workout plan builder**  contraindicated exercises are removed from the selection
- Class schedule management
- Member progress viewer (weight/BMI graphs)
- Progress entry logging on behalf of members
- Real-time chat with members

### ⚙️ Admin Portal (`/admin`)
- BI dashboard: KPI cards + Chart.js charts (revenue, users, injuries, bookings)
- Full user management and role assignment
- Membership package configuration
- **DSS rules editor**  add/edit/delete contraindication rules without code changes
- GDPR audit log viewer with date, user, and action filters
- Analytics CSV export

---

## DSS Engine

The core of Gymora is a **Rule-Based Expert System** (`/dss/dss_engine.php`) that operates as middleware between the user request and the database write.

### How it works

```
User attempts to book a class or receive a workout plan
          │
          ▼
  getDSSRestrictionsForUser($user_id)
          │
          ▼
  Query: Get all active medical_conditions for this user
          │
          ▼
  Query: Cross-reference conditions against dss_rules table
          │
          ├── Match found → BLOCK action, return reason string
          └── No match    → ALLOW action, proceed to booking
```

### DSS Rules (examples)

| Condition | Blocked Activities | Safe Alternatives |
|---|---|---|
| Hypertension | Heavy compound lifts >80% 1RM, Deadlift | Zone-2 cardio, resistance machines, yoga |
| Lumbar disc herniation | Overhead press, barbell row, spinal loading | Chest-supported rows, planks |
| BMI > 35 | Box jumps, plyometrics, HIIT classes | Elliptical, low-impact strength training |
| Knee injury | HIIT, Zumba, CrossFit, running | Yoga, Pilates, aqua aerobics, cycling |
| Cardiovascular risk | Max-effort sprints, very high intensity | Supervised moderate cardio, walking |
| Post-surgery | All exercise until cleared | Physiotherapy-only exercises |

### Key design decisions
- Rules are **stored in the database**, not hardcoded  Admins can add new contraindications via the UI
- The engine is **deterministic** (IF-THEN logic), not probabilistic  zero ambiguity on safety-critical decisions
- DSS runs **server-side** on every booking attempt  client-side AJAX check is a UX enhancement only, not the enforcement point
- DSS results are **logged** for audit purposes

---

## Security & Data Governance

### Authentication & Authorization
- Session-based auth with `$_SESSION['user_id']`, `['role']`, `['name']`
- `requireRole()` enforces access on every protected page and API endpoint
- Unauthorized requests are redirected; the event is logged

### SQL Injection Prevention
- All database interactions use **PDO prepared statements** with bound parameters
- Numeric inputs are explicitly cast with `intval()`
- No raw string interpolation into SQL queries anywhere in the codebase

### Password Security
- Passwords hashed with `PASSWORD_BCRYPT` on registration
- Plain-text passwords are never stored or logged

### Medical Data Encryption
- Doctor clinical notes stored encrypted at rest (`AES-256`)
- GDPR Special Category Data flagged and handled separately from standard user data

### GDPR Compliance
- `consent_given` + `consent_date` fields tracked on every user record
- `logAudit()` fires automatically on: `READ_MEDICAL_REPORT`, `SUBMIT_ASSESSMENT`, `READ_MEDICAL_CHAT` (keyword-triggered), and all admin data access events
- Audit log is append-only  no UPDATE or DELETE operations permitted on `audit_logs`

### Known Improvement Areas
- [ ] CSRF tokens not yet implemented on form submissions and API write endpoints
- [ ] Class booking capacity check has a potential race condition under concurrent load  `SELECT ... FOR UPDATE` guard would resolve this
- [ ] No automated test suite (PHPUnit)  scenario-based manual testing currently used

---

## Database Schema

14-table relational schema. Core entities:

```
users ──────────────────────────────────────────────┐
  │                                                  │
  ├──< medical_assessments >──< medical_conditions   │
  │         │                        │               │
  │         │                   dss_rules ───────────┤
  │         │                                        │
  ├──< class_bookings >──< classes                   │
  │                                                  │
  ├──< workout_plans >──< workout_exercises           │
  │                                                  │
  ├──< messages (sender_id / receiver_id)            │
  │                                                  │
  ├──< progress_logs                                 │
  │                                                  │
  ├──< appointments                                  │
  │                                                  │
  └──< audit_logs (actor + target) ──────────────────┘
```

Key tables:

| Table | Purpose |
|---|---|
| `users` | Central entity  credentials, role, package, GDPR consent |
| `medical_assessments` | Structured clinical baseline per member  AES-encrypted notes |
| `medical_conditions` | Active diagnoses per assessment  feeds DSS engine |
| `dss_rules` | DSS knowledge base  condition → blocked exercise mappings |
| `classes` | Available gym classes with type, trainer, capacity, schedule |
| `class_bookings` | Junction table  resolves user ↔ class many-to-many |
| `workout_plans` + `workout_exercises` | Trainer-built, DSS-filtered exercise programmes |
| `packages` | Membership tiers with consultation slot allocations |
| `messages` | Chat messages with sender, receiver, read timestamp |
| `progress_logs` | Weight/BMI entries over time per user |
| `audit_logs` | Append-only GDPR audit trail with IP + user agent |

SQL files are in `/sql/`:
- `schema.sql`  full CREATE TABLE definitions
- `seed_dss_rules.sql`  20+ starter contraindication rules
- `seed_exercises.sql`  exercise library
- `seed_classes.sql`  sample class schedule
- `seed_packages.sql`  membership package tiers

---

## API Endpoints

All endpoints under `/api/` return `Content-Type: application/json` and require an authenticated session.

| Method | Endpoint | Auth Required | Description |
|---|---|---|---|
| `GET` | `/api/analytics.php?type=admin` | `admin` | KPI metrics and chart data for BI dashboard |
| `GET` | `/api/analytics.php?type=progress` | `user` | Weight/BMI trend data for member dashboard |
| `GET` | `/api/get_messages.php?contact_id=&last_id=` | Any | Incremental message fetch since `last_message_id` |
| `POST` | `/api/send_message.php` | Any | Send a chat message to a contact |
| `POST` | `/api/log_progress.php` | `trainer` / `user` | Log a weight or BMI progress entry |
| `POST` | `/api/dss_check.php` | `user` | Real-time DSS validation for a class/exercise |
| `GET` | `/api/get_schedule.php` | Any | Fetch available class schedule |

### Response format

```json
{
  "status": "success",
  "data": { ... }
}
```

```json
{
  "status": "error",
  "message": "Unauthorized"
}
```

---

## Project Structure

```
gymora/
├── index.php                  # Public landing page
├── about.php
├── contact.php
├── packages.php               # Public pricing page
├── schedule.php               # Public class schedule
│
├── config/
│   ├── db.php                 # PDO connection (include in every DB-touching file)
│   ├── session.php            # requireRole() RBAC gateway
│   └── constants.php          # Role constants, app-wide config
│
├── auth/
│   ├── login.php
│   ├── register.php
│   └── logout.php
│
├── includes/
│   ├── header.php             # Role-aware navbar + Bootstrap CDN links
│   ├── footer.php             # JS script links
│   └── alert.php             # Reusable success/error alert component
│
├── user/                      # Member portal
│   ├── dashboard.php
│   ├── profile.php
│   ├── medical_report.php     # Read-only view of doctor-issued report
│   ├── workout_plan.php       # DSS-filtered trainer-assigned plan
│   ├── progress.php           # Chart.js weight/BMI tracker
│   ├── classes.php            # Browse classes (DSS safety filter applied)
│   ├── book_class.php         # POST handler  runs DSS check before commit
│   ├── appointments.php
│   ├── chat.php
│   └── packages.php
│
├── doctor/                    # Doctor portal
│   ├── dashboard.php
│   ├── assessment.php         # Structured clinical assessment form
│   ├── assessment_save.php    # POST handler  saves + triggers DSS update
│   ├── patient_history.php
│   ├── report_view.php
│   ├── appointments.php
│   ├── chat.php
│   └── profile.php
│
├── trainer/                   # Trainer portal
│   ├── dashboard.php
│   ├── workout_builder.php    # DSS-filtered exercise plan builder
│   ├── plan_save.php
│   ├── member_view.php        # Member restrictions + progress
│   ├── progress_update.php
│   └── classes.php
│
├── admin/                     # Admin portal
│   ├── dashboard.php          # BI dashboard (Chart.js)
│   ├── users.php
│   ├── assign_roles.php
│   ├── packages.php
│   ├── dss_rules.php          # DSS rule management UI
│   ├── audit_logs.php         # GDPR audit log viewer
│   └── reports.php            # CSV export
│
├── api/                       # JSON endpoints (AJAX targets)
│   ├── analytics.php
│   ├── get_messages.php
│   ├── send_message.php
│   ├── dss_check.php
│   ├── log_progress.php
│   └── get_schedule.php
│
├── dss/
│   ├── dss_engine.php         # Core DSS logic  getDSSRestrictionsForUser()
│   └── audit_logger.php       # logAudit()  GDPR append-only logger
│
├── assets/
│   ├── css/
│   │   └── gymora-theme.css   # Bootstrap 5 variable overrides + custom utilities
│   ├── js/
│   │   ├── chat_v2.js         # AJAX polling chat (setInterval 4s)
│   │   ├── charts.js          # Chart.js initialisation
│   │   └── dss_live.js        # Live DSS check on booking page
│   └── img/
│       └── logo.png
│
└── sql/
    ├── schema.sql
    ├── seed_dss_rules.sql
    ├── seed_exercises.sql
    ├── seed_classes.sql
    └── seed_packages.sql
```

---

## Getting Started

### Prerequisites

- PHP 8.1+
- MySQL 8.0+
- Apache with `mod_rewrite` enabled (or Nginx equivalent)

### Installation

**1. Clone the repository**
```bash
git clone https://github.com/yourusername/gymora.git
cd gymora
```

**2. Create the database**
```bash
mysql -u root -p -e "CREATE DATABASE gymora;"
mysql -u root -p gymora < sql/schema.sql
mysql -u root -p gymora < sql/seed_dss_rules.sql
mysql -u root -p gymora < sql/seed_exercises.sql
mysql -u root -p gymora < sql/seed_classes.sql
mysql -u root -p gymora < sql/seed_packages.sql
```

**3. Configure the database connection**

Edit `config/db.php`:
```php
$host = 'localhost';
$dbname = 'gymora';
$username = 'your_db_user';
$password = 'your_db_password';
```

**4. Configure your web server**

Point your virtual host document root to the `gymora/` directory. For Apache:
```apache
<VirtualHost *:80>
    DocumentRoot /path/to/gymora
    ServerName gymora.local
    <Directory /path/to/gymora>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**5. Open in browser**
```
http://gymora.local
```

### Default Seeded Accounts

After running the seed files, the following test accounts are available:

| Role | Email | Password |
|---|---|---|
| Admin | admin@demo.com | password |
| Doctor | doctor@demo.com | password |
| Trainer | tra@demo.com | password |
| Member | dem@demo.com | password |

> ⚠️ Change all passwords before deploying to any non-local environment.

---


## Screenshots

| Home | Member Dashboard | Workout Plan |
|---|---|---|
| ![Home](screenshots/home.png) | ![Member Dashboard](screenshots/member-dashboard.png) | ![Workout Plan](screenshots/workout-plan.png) |

| DSS Block | BI Dashboard | Audit Log |
|---|---|---|
| ![DSS Block](screenshots/dss-block.png) | ![BI Dashboard](screenshots/bi-dashboard.png) | ![Audit Log](screenshots/audit-log.png) |

| Assessment Form | Trainer Builder | Chat |
|---|---|---|
| ![Assessment Form](screenshots/assessment-form.png) | ![Trainer Builder](screenshots/trainer-builder.png) | ![Chat](screenshots/chat.png) |

| RBAC Role Portals |
|---|
| <img src="screenshots/role-dashboard.png" width="700"/> |

---

## Roadmap

- [ ] CSRF token protection on all form submissions and API write endpoints
- [ ] PHPUnit automated test suite for DSS engine logic
- [ ] Docker + docker-compose setup for one-command local environment
- [ ] `SELECT ... FOR UPDATE` concurrency guard on class booking capacity check
- [ ] OAuth2 / SSO login option
- [ ] Email notification system (assessment ready, booking confirmed)
- [ ] REST API versioning (`/api/v1/`)
- [ ] PDF export for medical reports

---

## Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature-name`
3. Follow the PDO query pattern in `config/db.php` for all DB access
4. Gate any new protected page with `requireRole()`  never rely on UI-only hiding
5. Any endpoint that touches `medical_assessments` or `medical_conditions` **must** call `logAudit()`
6. Open a pull request with a clear description of the change

---

## Author

**Isuru Lakmal Peiris**
GitHub: [@ilpeiris](https://github.com/ilpeiris)
LinkedIn: [linkedin.com/in/ilpeiris](https://linkedin.com/in/ilpeiris)

## License

This project is licensed under the MIT License. See [LICENSE](LICENSE) for details.
