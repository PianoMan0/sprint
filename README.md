# sprint
unified hackathon dashboard for the next generation of hack club events


## Recent additions

- User bios and recorded hackathons on user profiles (`public/profile.php`).
- Emergency incident reporting for attendees and an organizer alerts view (`public/report_incident.php`, `organizer/alerts.php`).
- Event judging modes: judges or peer judging (`events.judging_mode`, set when creating events).
- Simple JSON API at `/api/index.php` for events, users, submissions, and incidents.
- GitHub account linking via OAuth (`auth/github.php`, `auth/github_callback.php`).
- Global analytics dashboard for organizers (`organizer/site_analytics.php`).
- UI improvements to buttons, header, and styles in `assets/style.css`.

## Recent fixes & additions (automated changes)

- Replaced raw `die()`/`exit()` calls in public pages with a friendly `abort_page()` helper so errors render within the site layout.
- Added a maintenance mode: create a `MAINTENANCE` file in the project root to show a site-wide maintenance page to non-admin users.
- Hardened URL generation to avoid double-embedding `BASE_URL` (fixed `url()` helper in `config.php`).
- Added common security headers in `config.php` (X-Frame-Options, X-Content-Type-Options, Referrer-Policy, X-XSS-Protection).
- Escaped and cast numerous template outputs (IDs, timestamps, counts) to reduce XSS and injection risk.
- Added an organizer CSV export: `organizer/export_submissions.php` (download submissions for an event).

If you'd like, I can continue to:

- Add automated unit tests (PHPUnit) and CI checks.
- Run a deeper security audit (dependency, token handling, OAuth redirect checks).
- Implement more admin utilities (bulk invites, scheduled exports, or Slack alerts on incidents).
