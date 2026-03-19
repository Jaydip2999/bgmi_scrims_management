# BGMI Scrims Management Setup

## Stack
- Frontend: Tailwind CDN + HTML/CSS/JS
- Backend: Core PHP
- Database: MySQL / phpMyAdmin

## 1. Database
1. Create a database named `bgmi_scrims`.
2. Point XAMPP Apache + MySQL at this project.
3. Open the site once in the browser.

The app now auto-creates and auto-upgrades these tables on first load:
- `users`
- `scrims`
- `prizes`
- `bookings`
- `payments`
- `results`
- `scrim_templates`

## 2. Default Admin
Create an admin manually in phpMyAdmin.

Example SQL:

```sql
INSERT INTO users (name, email, password, role)
VALUES (
  'Admin',
  'admin@bgmi.test',
  '$2y$10$tn2eAt.mNSXHG3EMMFHYku9WnS0dMzJMtHqUCnzjm50GgVKqGdKuy',
  'admin'
);
```

Password for that hash:

```text
admin123
```

If you prefer, generate a new hash with PHP:

```php
<?php echo password_hash('your-password', PASSWORD_DEFAULT); ?>
```

## 3. Main Features Implemented
- Admin dashboard analytics
- Scrim create/edit with auto prize pool
- Default or manual prize distribution
- Scrim details page with:
  - basic info
  - countdown
  - registration status
  - approved players/teams with slot number
  - prize table
  - 10-minute room reveal
  - leaderboard
  - rules
- Player registration/login
- Payment proof upload
- Approval/rejection with auto slot assignment
- Room control
- Result entry with auto point calculation
- Payout status + transaction tracking
- Match history and public leaderboard

## 4. Payment Uploads
Payment screenshots are stored in:

```text
assets/payments/
```

The folder is created automatically when the first payment proof is uploaded.

## 5. Cron Job for Auto Scrim Creation
The file `cron_auto_scrims.php` reads active rows from `scrim_templates` and creates future scrims if they do not already exist.

Example Windows Task Scheduler / command:

```bat
php C:\xampp\htdocs\php\bgmi_scrims_management\cron_auto_scrims.php
```

Example template insert:

```sql
INSERT INTO scrim_templates
(title, mode, map, entry_fee, total_slots, rules_text, start_time, create_days_ahead, is_active)
VALUES
('Daily Evening Scrim', 'Squad', 'Erangel', 50, 20, 'No hacks. Respect timings.', '20:00:00', 1, 1);
```

## 6. Important Notes
- Existing older columns like `date`, `time`, and `slots` are kept compatible.
- The app uses prepared statements in the main write flows.
- Prize pool is always recalculated as `entry_fee * total_slots`.
- Room details become visible when match time is within 10 minutes.
