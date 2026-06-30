# Task: Secure Production Credentials

This task focuses on finalizing the security improvements for the admin script (`scripts/admin_index.php`) by properly securing the production server and removing legacy fallback credentials.

## Context
Currently, the admin dashboard script relies on `getenv()` to fetch credentials but still has the default insecure passwords hardcoded as fallbacks (e.g., `getenv('SOAP_USER') ?: 'admin'`). This was done temporarily to prevent local setups from breaking. Your objective is to lock down the production server and then remove those fallbacks from the codebase.

## Execution Steps

### 1. Secure Server Credentials
Connect to the headless Linux production server (`192.168.1.168`) via SSH (`coyofroyo`) and perform the following:
* Generate secure, random passwords to replace the default `'admin'` (for SOAP) and `'acore'` (for Database).
* Update the actual configuration files on the server (e.g., worldserver configuration for SOAP and MariaDB permissions for the database user) to use these new secure passwords.

### 2. Configure Environment Variables
* Configure the web server environment (e.g., PHP-FPM, Apache env vars, or standard bash `.env` depending on how `admin_index.php` runs on the server) to set the following environment variables with your newly generated passwords:
  * `SOAP_USER`
  * `SOAP_PASS`
  * `SOAP_URL`
  * `DB_HOST`
  * `DB_USER`
  * `DB_PASS`
  * `DB_WORLD`
  * `CONFIG_PATH`
* Restart the necessary services (e.g., `systemctl restart php-fpm apache2`) and ensure the admin dashboard still successfully connects using the new environment variables.

### 3. Update Codebase (Remove Fallbacks)
**Only proceed with this step after verifying that step 2 is complete and the production server is correctly functioning with the new environment variables.**

* Update `scripts/admin_index.php` in this repository to completely remove the hardcoded fallbacks.
* The definitions should change from:
  ```php
  define('SOAP_USER', getenv('SOAP_USER') ?: 'admin');
  ```
  to strictly requiring the environment variable:
  ```php
  define('SOAP_USER', getenv('SOAP_USER'));
  ```
* Ensure you update all `SOAP_` and `DB_` definitions that were previously given fallbacks.
* Run tests to ensure you have not introduced any syntax errors.
* Submit the changes in a new PR.