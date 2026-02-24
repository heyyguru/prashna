# HeyyGuru Assistant

## Overview
HeyyGuru Assistant is a doubt-solving web application where students can ask academic doubts and mentors can reply. Built with PHP 8+, vanilla HTML/CSS/JavaScript, and SQLite (locally) / MySQL (production).

## Current State
- Fully functional MVP with student registration, login, doubt submission, mentor replies
- Chat-like UI on landing page with auto-reply for greetings
- SQLite for local Replit development, MySQL init.sql provided for Hostinger deployment

## Architecture
- **Backend:** PHP 8+ with PDO (supports both SQLite and MySQL)
- **Frontend:** HTML, CSS, Vanilla JavaScript
- **Database:** SQLite locally, MySQL for production (Hostinger)
- **Auth:** PHP sessions with CSRF protection
- **Security:** Prepared statements, password hashing, role-based access

## Project Structure
```
/config.php          - Database & app configuration
/helpers.php         - Auth, CSRF, flash messages, utility functions
/router.php          - PHP built-in server router
/index.php           - Landing page with chat UI
/register.php        - Student registration
/login.php           - Student login
/logout.php          - Session logout
/chat_handler.php    - AJAX chat auto-reply handler
/student/dashboard.php - Student doubt list
/student/new_doubt.php - Submit new doubt
/mentor/login.php    - Mentor login
/mentor/dashboard.php - Mentor doubt list with filters
/mentor/view_doubt.php - View doubt details & reply
/db/init.sql         - MySQL schema + seed data
/css/style.css       - All styles
/js/chat.js          - Chat UI JavaScript
```

## Default Mentor Credentials
- Phone: 9999999999
- Password: password

## Deployment
- For MySQL (Hostinger): Change DB_DRIVER to 'mysql' in config.php, update credentials, import db/init.sql
- For Replit: Uses SQLite automatically, no setup needed

## User Preferences
- No external AI APIs
- No paid SMS services
- Simple, clean UI
