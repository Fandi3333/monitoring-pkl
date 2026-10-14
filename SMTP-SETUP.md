# SMTP setup for password recovery

Password recovery sends a six-digit OTP using SMTP. Configure these environment
variables in the environment used by PHP:

- `SMTP_HOST` — SMTP server hostname (for Gmail: `smtp.gmail.com`)
- `SMTP_PORT` — SMTP port (usually `587` for STARTTLS)
- `SMTP_USER` — SMTP account username/email
- `SMTP_PASS` — SMTP password or provider-issued app password
- `SMTP_ENCRYPTION` — `tls` for port 587 or `ssl` for port 465
- `SMTP_FROM_EMAIL` — optional sender address; defaults to `SMTP_USER`
- `SMTP_FROM_NAME` — optional sender name; defaults to `Ruang PKL`

For Gmail, use a Google App Password (which requires 2-Step Verification),
not your normal Google account password. Do not put SMTP credentials in source
code or commit them to the repository.

After setting variables, restart Apache or the PHP server so its PHP process
receives them. For the PHP development server in PowerShell, set the variables
in the same PowerShell process before starting it:

```powershell
$env:SMTP_HOST = 'smtp.gmail.com'
$env:SMTP_PORT = '587'
$env:SMTP_USER = 'your-sender@gmail.com'
$env:SMTP_PASS = 'your-google-app-password'
$env:SMTP_ENCRYPTION = 'tls'
$env:SMTP_FROM_EMAIL = $env:SMTP_USER
$env:SMTP_FROM_NAME = 'Ruang PKL'
& 'C:\xampp\php\php.exe' -S 127.0.0.1:8000 -t 'C:\xampp\htdocs\monitoring-pkl'
```

The OTP expires after 10 minutes and allows at most five attempts. Requests
from the same session are limited to one per minute.
