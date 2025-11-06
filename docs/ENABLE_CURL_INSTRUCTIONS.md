# How to Enable cURL Extension in XAMPP

The cURL extension is required for sending email notifications. Follow these steps to enable it:

## Steps:

1. **Open php.ini file**
   - Navigate to: `C:\xampp\php\php.ini`
   - Or open it from XAMPP Control Panel → Apache → Config → PHP (php.ini)

2. **Find the cURL extension line**
   - Press `Ctrl+F` and search for: `extension=curl`
   - You should find a line like: `;extension=curl` or `;extension=php_curl.dll`

3. **Uncomment the line**
   - Remove the semicolon (`;`) at the beginning of the line
   - Change from: `;extension=curl`
   - To: `extension=curl`
   - Or: `extension=php_curl.dll` (depending on your PHP version)

4. **Save the file**

5. **Restart Apache**
   - Go to XAMPP Control Panel
   - Stop Apache (if running)
   - Start Apache again

6. **Verify cURL is enabled**
   - Create a test file `test_curl.php` in your `htdocs` folder:
   ```php
   <?php
   if (function_exists('curl_init')) {
       echo "cURL is enabled!";
   } else {
       echo "cURL is NOT enabled!";
   }
   ?>
   ```
   - Visit `http://localhost/test_curl.php` in your browser
   - You should see "cURL is enabled!"

## Alternative: Check via phpinfo()

You can also check by creating a `phpinfo.php` file:
```php
<?php phpinfo(); ?>
```

Then visit it in your browser and search for "curl" to see if it's enabled.

## Note:

After enabling cURL, the email notification system will work properly. Until then, proposals will still be created successfully, but email notifications won't be sent (this is logged in the error log).

