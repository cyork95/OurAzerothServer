# Antigravity Task: Secure Admin Credentials

## Overview
A recent security fix in the codebase removed hardcoded database and SOAP credentials from `scripts/admin_index.php`. The frontend script now relies entirely on environment variables to authenticate with the database and SOAP service.

## Required Action
You need to update the backend server configuration to supply these necessary environment variables when serving or executing the admin script.

### Variables that must be provided to the PHP environment:
- `DB_USER`
- `DB_PASS`
- `SOAP_USER`
- `SOAP_PASS`

### Implementation Details
Depending on how the server is running (e.g., Apache, Nginx with PHP-FPM, or CLI), you must configure the backend to inject these variables securely. For example, if using Apache, you might need to update the VirtualHost or `.htaccess` configuration. If using PHP-FPM, you might need to update the pool configuration.

### Important Follow-Up
Currently, `scripts/admin_index.php` contains fallback values that are empty strings (e.g., `getenv('DB_PASS') ?: ''`). **After** you have successfully configured the backend to supply these environment variables, you must revisit the codebase and remove the fallback placeholders to ensure it strictly relies on the provided environment variables (e.g., `getenv('DB_PASS')`).
