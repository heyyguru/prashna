# HeyyGuru Assistant

## Overview

HeyyGuru Assistant is a doubt-solving web application where students can ask academic doubts and mentors can reply. Students register, log in, and submit questions organized by subject. Mentors (seeded via database, no public registration) view open doubts, see student details, and post answers. The app also features a chat-like UI on the landing page with rule-based auto-replies for greetings. Built with PHP 8+, vanilla HTML/CSS/JavaScript, and SQLite for local development (with MySQL support for production deployment on Hostinger).

## User Preferences

Preferred communication style: Simple, everyday language.

- No external AI APIs (no OpenAI, etc.)
- No paid SMS services — use in-app notifications only
- Simple, clean, modern UI with "HeyGuruBot" branding
- Keep things minimal and functional — no unnecessary frameworks or libraries
- SQLite for local/Replit development; MySQL support via config toggle for production
- PHP built-in server for local development

## System Architecture

### Backend: PHP 8+ with PDO
- **Why PHP:** Specified requirement; simple deployment on shared hosting (Hostinger). No framework — plain PHP files handle routing, auth, and business logic.
- **Database Abstraction:** PDO is used with a configurable driver (`DB_DRIVER` in `config.php`) supporting both SQLite (local) and MySQL (production). This allows seamless switching between environments.
- **Router:** `router.php` serves as the entry point for PHP's built-in development server, handling static file serving and routing.

### Frontend: Vanilla HTML/CSS/JavaScript
- No frontend framework. Pages are server-rendered PHP with embedded HTML.
- `css/style.css` contains all styling with CSS custom properties for theming.
- `js/chat.js` handles client-side interactivity: mentor search/filter on dashboard, CSV download of records, and the landing page chat UI.
- Responsive design with modern styling (backdrop blur, gradients, card-based layout).

### Authentication & Authorization
- **Sessions:** PHP sessions manage login state for both students and mentors.
- **CSRF Protection:** CSRF tokens are generated and validated on all forms via `helpers.php`.
- **Role-based Access:** Students cannot access `/mentor/*` routes and vice versa. Route guards are implemented in each page file.
- **Password Security:** `password_hash()` and `password_verify()` for credential storage.

### Database Schema
- **Users/Students table:** name, phone, email (optional), hashed password
- **Mentors table:** Seeded via `db/init.sql` (default: phone `9999999999`, password `password`)
- **Doubts table:** subject, question_text, status (`open`/`answered`), student reference, timestamps
- **Replies table:** answer_text, mentor reference, doubt reference, answered_at timestamp
- All queries use prepared statements to prevent SQL injection.

### Key Pages & Routing
| Path | Purpose |
|------|---------|
| `/index.php` | Landing page with chat-like UI and auto-reply |
| `/register.php` | Student registration form |
| `/login.php` | Student login |
| `/logout.php` | Session destruction |
| `/chat_handler.php` | AJAX endpoint for rule-based chat auto-replies |
| `/student/dashboard.php` | Student's doubt list with answers |
| `/student/new_doubt.php` | Submit a new doubt |
| `/mentor/login.php` | Mentor login |
| `/mentor/dashboard.php` | All doubts with filters and search |
| `/mentor/view_doubt.php` | View doubt detail and post reply |

### Chat Auto-Reply System
- Rule-based (no AI): matches keywords like "hi", "hello", "hey", "how are you" and returns canned responses.
- If user is not logged in, prompts them to log in.
- If user is logged in and sends a non-greeting message, it gets stored as a doubt in the database.

### Configuration
- `config.php` holds database driver selection, credentials, app settings, and optional SMTP config (disabled by default).
- `helpers.php` contains shared utility functions: auth checks, CSRF helpers, flash messages, and placeholder functions for future SMS integration.

## External Dependencies

### Database
- **SQLite** (default for Replit): Zero-config, file-based database. No external service needed.
- **MySQL** (production on Hostinger): Switch `DB_DRIVER` to `'mysql'` in `config.php` and import `db/init.sql`.

### Third-Party Services (All Optional/Placeholder)
- **Email (SMTP):** PHP mail function with SMTP settings in `config.php`, disabled by default. Can be enabled for notification emails when deployed to a host with SMTP support.
- **SMS (Twilio/Fast2SMS):** Placeholder `sendSMS()` function with comments for future integration. Not active — no paid services required.
- **No external APIs:** No OpenAI, no analytics, no CDN dependencies. Everything runs self-contained.

### PHP Extensions Required
- `pdo_sqlite` (for local development)
- `pdo_mysql` (for production MySQL)
- Standard PHP session support