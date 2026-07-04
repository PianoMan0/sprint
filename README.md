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
