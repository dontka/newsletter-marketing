# API Documentation

## Public Endpoints

### POST /subscribe
Subscribe an email to the newsletter.

**Parameters:**
- `email` (required): Valid email address
- `name` (optional): Subscriber name

**Response:**
- Success: Redirect to confirmation message
- Error: Error message

### GET /confirm?token=...
Confirm subscription with token.

**Parameters:**
- `token` (required): Confirmation token sent via email

**Response:**
- Success message if confirmed
- Error message if token is invalid

### GET /unsubscribe?token=...
Unsubscribe from newsletter.

**Parameters:**
- `token` (required): Unsubscribe token (same as subscription token)

**Response:**
- Success message if unsubscribed
- Error message if token is invalid

## Admin Endpoints (Require AfiaZone OAuth)

### GET /admin/login
Redirect to AfiaZone OAuth login page.

### GET /admin/callback
OAuth callback URL (configure this in AfiaZone dashboard).

**Parameters:**
- `auth_key` (from AfiaZone): Authentication key from OAuth

### GET /admin/dashboard
Admin dashboard (requires login).

### GET /admin/logout
Logout and destroy session.

## Newsletter Management

### GET /newsletter
List all newsletters.

### GET /newsletter/create
Newsletter creation form.

### POST /newsletter/save
Save or send a newsletter.

**Parameters:**
- `subject` (required): Newsletter subject
- `content` (required): HTML content
- `plain_text` (optional): Plain text version
- `action`: 'draft', 'send_now', or 'schedule'
- `scheduled_at` (if action='schedule'): ISO datetime string

### GET /subscribers
List all active subscribers with search capability.

**Parameters:**
- `q` (optional): Search query (email or name)

### GET /subscribers/export
Export active subscribers as CSV.

## Tracking Endpoints

### GET /open.gif?job=ID
Record newsletter open.

**Parameters:**
- `job` (optional): Send job ID

**Response:** 1x1 transparent GIF

### GET /click?u=URL&job=ID
Record link click and redirect.

**Parameters:**
- `u` (required): Target URL to redirect to
- `job` (optional): Send job ID

**Response:** Redirect to target URL

## Database Tables

### subscribers
- `id`: Unique identifier
- `email`: Email address (UNIQUE)
- `name`: Subscriber name
- `token`: Confirmation/unsubscribe token
- `status`: pending, active, unsubscribed, bounced
- `created_at`: Subscription date
- `confirmed_at`: Confirmation date
- `unsubscribed_at`: Unsubscription date

### newsletters
- `id`: Unique identifier
- `subject`: Email subject
- `content`: HTML content
- `plain_text`: Plain text version
- `created_by`: Creator email
- `created_at`: Creation date
- `scheduled_at`: Scheduled send time
- `status`: draft, scheduled, sending, sent, cancelled

### send_jobs
- `id`: Unique identifier
- `newsletter_id`: Newsletter reference
- `subscriber_id`: Subscriber reference
- `status`: pending, sent, failed
- `attempts`: Number of send attempts
- `last_error`: Last error message
- `sent_at`: Actual send time

### events
- `id`: Unique identifier
- `send_job_id`: Send job reference
- `type`: open or click
- `meta`: JSON metadata (URL for clicks)
- `created_at`: Event timestamp

## Cron Job

Configure this in crontab to run every 5 minutes:

```bash
*/5 * * * * php /path/to/scripts/send_cron.php
```

This script:
- Starts scheduled newsletters when their time arrives
- Sends pending emails in batches of 100
- Implements retry logic for failed sends
- Logs all activity to `logs/send_cron.log`

## Configuration (.env)

```
DB_HOST=127.0.0.1
DB_NAME=newsletter
DB_USER=root
DB_PASS=

SMTP_FROM=info@example.com
SMTP_FROM_NAME=Newsletter
SMTP_HOST=localhost
SMTP_PORT=25

BASE_URL=http://example.com/newsletter

AFIAZONE_APP_ID=your_app_id
AFIAZONE_APP_SECRET=your_app_secret
AFIAZONE_REDIRECT_URI=http://example.com/newsletter/admin/callback
AFIAZONE_API_BASE=https://afiazone.com/api
```

## Error Handling

All errors are logged to `logs/send_cron.log` with timestamp and full error message.

For web endpoints, errors display a user-friendly message.

## Rate Limiting

- Newsletter sending is throttled with 500ms delay between emails to avoid SMTP server rejection
- Maximum 3 retry attempts for failed sends
