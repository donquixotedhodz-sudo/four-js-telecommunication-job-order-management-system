<?php
/**
 * Email Configuration Settings
 * Configure your SMTP settings here for sending emails to customers
 */

// Email Configuration
define('SMTP_HOST', 'smtp.gmail.com'); // Change to your SMTP server
define('SMTP_PORT', 587); // SMTP port (587 for TLS, 465 for SSL)
define('SMTP_SECURE', 'tls'); // 'tls' or 'ssl'
define('SMTP_AUTH', true); // Enable SMTP authentication
define('SMTP_USERNAME', 'fourjsairconservicesmanagement@gmail.com'); // Your email address
define('SMTP_PASSWORD', 'umvp gbqz vojr vsok'); // Your email password or app password

// Email Settings
define('FROM_EMAIL', 'fourjsairconservicesmanagement@gmail.com'); // From email address
define('FROM_NAME', 'FourJS Ticket System'); // From name
define('REPLY_TO_EMAIL', 'fourjsairconservicesmanagement@gmail.com'); // Reply-to email
define('REPLY_TO_NAME', 'FourJS Support'); // Reply-to name

// Email Templates
define('EMAIL_CHARSET', 'UTF-8');
define('EMAIL_DEBUG', 0); // 0 = off, 1 = client messages, 2 = client and server messages

/**
 * Instructions for Gmail Setup:
 * 1. Enable 2-Factor Authentication on your Gmail account
 * 2. Generate an App Password: https://myaccount.google.com/apppasswords
 * 3. Use the App Password instead of your regular password
 * 4. Update SMTP_USERNAME and SMTP_PASSWORD above
 */

/**
 * Instructions for other email providers:
 * 
 * Outlook/Hotmail:
 * SMTP_HOST: smtp-mail.outlook.com
 * SMTP_PORT: 587
 * SMTP_SECURE: tls
 * 
 * Yahoo:
 * SMTP_HOST: smtp.mail.yahoo.com
 * SMTP_PORT: 587 or 465
 * SMTP_SECURE: tls or ssl
 */
?>