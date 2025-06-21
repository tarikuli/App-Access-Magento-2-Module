# Studio3Marketing_AppAccess Magento 2 Module

## Goal
This module provides a secure and flexible way for mobile or external applications to authenticate and log in customers to a Magento 2 store using UUIDs, custom URLs, and token-based flows. It is designed to facilitate app-based login and customer session management, supporting seamless integration between external apps and Magento.

## Overview
Studio3Marketing_AppAccess extends Magento's customer authentication system to allow external applications (such as mobile apps) to:
- Register or log in customers using their email and UUID.
- Generate secure, private login URLs with reset password tokens.
- Redirect users to specific pages after login.
- Set and persist custom UUID attributes on customer accounts.

The module leverages Magento's API, controller, observer, and plugin mechanisms to provide a robust and extensible login flow for app users.

## Features
- API endpoint for app-based login and registration (`AppLoginManagementInterface`).
- Controller for handling login and redirection logic (`Controller/Account/Login.php`).
- Observer to set UUID on customer after login (`Observer/AppCustomerLogin.php`).
- Plugin to extend Magento's default login post behavior (`Plugin/Frontend/Magento/Customer/Controller/Account/LoginPost.php`).
- Automatic generation of secure reset password tokens and private login URLs.
- Support for redirecting users to custom URLs after login.
- UUID attribute management for customers.

## Installation
1. Copy the `AppAccess` module folder to `app/code/Studio3Marketing/AppAccess` in your Magento 2 installation.
2. Run the following Magento CLI commands:
   ```sh
   php bin/magento setup:upgrade
   php bin/magento setup:di:compile
   php bin/magento cache:flush
   ```
3. Ensure the module is enabled:
   ```sh
   php bin/magento module:status Studio3Marketing_AppAccess
   ```

## Configuration
- No additional configuration is required by default.
- The module adds a `uuid` attribute to customer entities and manages it automatically during app-based login flows.
- You may customize ACL, DI, and event handling via the files in the `etc/` directory as needed.

## File Structure
```
AppAccess/
├── Api/
│   └── AppLoginManagementInterface.php         # API interface for app login
├── Controller/
│   └── Account/
│       └── Login.php                          # Main controller for login and redirection
├── Model/
│   └── AppLoginManagement.php                 # Implements app login logic
├── Observer/
│   └── AppCustomerLogin.php                   # Observer to set UUID after login
├── Plugin/
│   └── Frontend/Magento/Customer/Controller/Account/
│       └── LoginPost.php                      # Plugin for login post action
├── Setup/Patch/Data/UuidCustomerAttribute.php # Adds UUID attribute to customer
├── etc/
│   ├── acl.xml
│   ├── di.xml
│   ├── events.xml
│   ├── module.xml
│   ├── webapi.xml
│   └── frontend/routes.xml
├── view/base/ui_component/customer_form.xml   # UI component for customer form
├── composer.json
├── registration.php
```

## Support
For questions or support, please contact Studio3Marketing or open an issue in your project repository.
