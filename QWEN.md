# Diploma Builder WordPress Plugin - Development Context

## Project Overview

This is a WordPress plugin named "Diploma Builder" that allows users to create custom high school diplomas with a live preview feature. The diplomas can be downloaded or purchased as digital downloads, printed versions, or premium packages. The plugin includes both frontend and backend (admin) functionality.

### Key Features
- Interactive diploma builder with multi-step form
- Live preview of the diploma as users customize it
- Multiple diploma styles and paper colors
- Emblem selection (generic or state-specific)
- Integration with WooCommerce for purchasing
- Admin dashboard to manage created diplomas
- Database storage of diploma configurations

### Technologies Used
- **WordPress Plugin Architecture**: Uses standard WordPress hooks, filters, and coding practices
- **PHP**: Backend logic, database operations, admin functionality
- **JavaScript/jQuery**: Frontend interactivity, AJAX calls, live preview updates
- **HTML/CSS**: UI structure and styling
- **External Libraries**: 
  - `html2canvas` for generating diploma images
  - `jsPDF` (referenced but not heavily used in current JS)
- **Database**: WordPress `$wpdb` for custom table storage

## Code Structure

```
diploma-builder/
├── assets/
│   ├── css/
│   │   ├── diploma-builder.css
│   │   └── diploma-builder-admin.css
│   ├── js/
│   │   ├── diploma-builder.js
│   │   └── diploma-builder-admin.js
│   ├── emblems/
│   │   ├── generic/
│   │   └── states/
│   └── previews/
├── includes/
│   ├── class-diplomabuilder-admin.php
│   ├── class-diplomabuilder-ajax.php
│   ├── class-diplomabuilder-assets.php
│   ├── class-diplomabuilder-database.php
│   └── class-diplomabuilder-frontend.php
├── diploma-builder.php (Main plugin file)
└── ... (other files like images, license, etc.)
```

### Main Components

1.  **Main Plugin File (`diploma-builder.php`)**:
    *   Plugin header metadata
    *   Defines constants (version, paths, URLs)
    *   Implements an autoloader for plugin classes
    *   Contains the main `DiplomaBuilder` class which initializes the plugin, loads dependencies, handles activation/deactivation/uninstall hooks.

2.  **Frontend (`class-diplomabuilder-frontend.php`)**:
    *   Registers the `[diploma_builder]` shortcode
    *   Enqueues frontend CSS/JS assets
    *   Renders the main diploma builder form and live preview area
    *   Contains helper methods for getting diploma styles, paper colors, emblems, and US states data

3.  **Admin (`class-diplomabuilder-admin.php`)**:
    *   Adds admin menu pages (main "Diploma Builder" page and "Settings" submenu)
    *   Enqueues admin-specific assets
    *   Registers plugin settings
    *   Renders the admin pages (list of diplomas, settings form)

4.  **Assets Management (`class-diplomabuilder-assets.php`)**:
    *   Handles enqueuing of both frontend and admin CSS/JS files
    *   Localizes scripts with necessary data (AJAX URLs, nonces, plugin URL)

5.  **Database (`class-diplomabuilder-database.php`)**:
    *   Handles creation and deletion of the custom database table (`wp_diploma_configurations`)
    *   Provides methods for saving, retrieving, updating, and deleting diploma records
    *   Includes functions for statistics and cleanup

6.  **AJAX Handler (`class-diplomabuilder-ajax.php`)**:
    *   Registers and handles all AJAX actions (both for frontend users and admin)
    *   Actions include saving diplomas, generating images, loading previews, deleting diplomas, exporting data, getting stats
    *   Implements security checks (nonces) for AJAX requests

7.  **Frontend JavaScript (`assets/diploma-builder.js`)**:
    *   Manages the interactive behavior of the diploma builder form
    *   Handles navigation between form steps
    *   Updates the live preview in real-time as users make selections
    *   Manages form validation
    *   Handles diploma saving and downloading via AJAX
    *   Integrates with `html2canvas` to generate diploma images
    *   Handles purchase button actions

8.  **Frontend CSS (`assets/diploma-builder.css`)**:
    *   Styles the entire diploma builder interface
    *   Includes responsive design
    *   Styles for the form, preview area, buttons, modals, etc.

9.  **Admin CSS/JS (`assets/diploma-builder-admin.css`, `assets/diploma-builder-admin.js`)**:
    *   Styles and scripts specific to the admin dashboard pages

## Development Conventions

- **WordPress Coding Standards**: The code generally follows WordPress PHP coding standards.
- **Object-Oriented Programming**: Core functionality is encapsulated in classes.
- **Autoloading**: Classes are automatically loaded based on naming conventions.
- **Hooks**: Uses WordPress actions and filters extensively for extensibility.
- **Security**: Nonces are used for form submissions and AJAX requests. Data is sanitized and validated.
- **Internationalization**: Text strings are wrapped in `__()` and `_e()` functions for translation.
- **Database**: Custom database tables are used for storing diploma configurations, managed via `dbDelta`.

## Building and Running

As this is a WordPress plugin, there's no traditional build process. The plugin is installed by placing the `diploma-builder` directory in the WordPress `wp-content/plugins/` directory.

### Prerequisites

- A working WordPress installation (version 5.0 or higher)
- PHP 7.4 or higher
- Web server (Apache/Nginx)
- MySQL database

### Installation

1.  Place the `diploma-builder` directory in your WordPress installation's `wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in the WordPress admin dashboard.

### Configuration

-   After activation, navigate to "Diploma Builder" in the WordPress admin menu.
-   Use the "Settings" submenu to configure plugin options like guest diploma creation allowance and maximum diplomas per user.

### Usage

-   To use the diploma builder on a page or post, add the shortcode `[diploma_builder]`.

## Testing

There are no explicit unit or integration tests in the provided codebase. Testing would typically be done manually through the WordPress interface or by writing custom tests using a framework like PHPUnit with WordPress testing utilities.