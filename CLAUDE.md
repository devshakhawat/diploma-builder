# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Diploma Builder** is a WordPress plugin for creating customizable high school diplomas with live preview. Users can design diplomas with various styles, emblems, and paper colors, then download or purchase printed versions through WooCommerce integration.

**Tech Stack:**
- WordPress 5.0+ / PHP 7.4+
- jQuery for frontend interactivity
- html2canvas (1.4.1) for generating diploma images
- jsPDF (2.5.1) for PDF generation
- Custom database table for diploma storage

## Development Environment

This is a **WordPress plugin** with no build process. Development happens directly in the local WordPress environment at `/home/shakhawat/Local Sites/diplomabuilder/app/public/wp-content/plugins/diploma-builder`.

### Installation/Setup
1. Ensure WordPress is running with PHP 7.4+
2. Plugin is already installed - activate via WordPress admin if needed
3. After activation, navigate to "Diploma Builder" admin menu
4. Configure settings: guest access, user quotas, WooCommerce product IDs

### Testing Changes
- Frontend: Visit any page with `[diploma_builder]` shortcode
- Admin: Navigate to WordPress Admin → Diploma Builder
- Clear browser cache if assets don't update (CSS/JS are versioned)

## Code Architecture

### Plugin Structure
```
diploma-builder/
├── diploma-builder.php           # Main plugin file (193 lines)
├── includes/
│   ├── class-diplomabuilder-admin.php       # 107 lines
│   ├── class-diplomabuilder-ajax.php        # 743 lines - Core AJAX logic
│   ├── class-diplomabuilder-assets.php      # 116 lines
│   ├── class-diplomabuilder-database.php    # 285 lines
│   ├── class-diplomabuilder-frontend.php    # 723 lines - Shortcode rendering
│   └── admin/partials/                      # Admin page templates
├── assets/
│   ├── diploma-builder.js        # 1,164 lines - Frontend logic
│   ├── diploma-builder.css       # 3,115 lines - Main styles
│   ├── diploma-builder-admin.js  # 164 lines
│   ├── diploma-builder-admin.css # 174 lines
│   ├── emblems/
│   │   ├── generic/              # PNG emblems (graduation_cap, laurel_wreath, etc.)
│   │   └── states/               # US state-specific emblems
│   ├── font/
│   └── previews/
```

### Key Components

**1. Main Plugin Class (`diploma-builder.php`)**
- Singleton pattern with autoloader for `DiplomaBuilder*` classes
- Converts CamelCase to snake-case for file loading (e.g., `DiplomaBuilder_Admin` → `class-diplomabuilder-admin.php`)
- Activation hooks create database table and upload directories (`/wp-content/uploads/diplomas/{temp,generated}`)
- Uninstall removes database table, options, and upload directories

**2. Database Layer (`class-diplomabuilder-database.php`)**
- Custom table: `wp_diploma_configurations` with 16 columns
- Key fields: `diploma_style`, `paper_color`, `emblem_type`, `school_name`, `student_name`, `graduation_date`, `configuration_data` (JSON), `image_path`
- Methods: `save_diploma()`, `get_diploma()`, `get_user_diplomas()`, `delete_diploma()`, `get_statistics()`
- Uses `dbDelta()` for schema management
- Quota enforcement: `user_can_create_diploma()` checks guest allowance and per-user limits

**3. Frontend (`class-diplomabuilder-frontend.php`)**
- Registers `[diploma_builder]` shortcode
- Conditionally loads assets only on pages with shortcode
- Renders multi-step form with live preview area
- Helper methods return diploma options:
  - `get_diploma_styles()` - Available diploma designs
  - `get_paper_colors()` - Paper color options
  - `get_generic_emblems()` - Generic emblem list
  - `get_us_states()` - US states for location/emblem selection
- WooCommerce integration: `limit_one_product_in_cart()` clears cart for single-product checkout

**4. AJAX Handler (`class-diplomabuilder-ajax.php`)**
Eight endpoints split between public and admin:

**Public Endpoints:**
- `save_diploma` - Validates and persists diploma configuration
- `generate_diploma_image` - Converts HTML to image via html2canvas data, saves as attachment
- `get_diploma_preview` - Returns rendered HTML with watermark for guests
- `load_state_emblem` - Returns emblem URL for selected state

**Admin Endpoints:**
- `delete_diploma` - Single diploma deletion
- `bulk_delete_diplomas` - Batch deletion with prepared statements
- `export_diplomas` - CSV export with BOM for Excel compatibility
- `get_diploma_stats` - Analytics dashboard data

**Security Architecture:**
- Nonce verification on all endpoints (`diploma_builder_nonce` for public, `diploma_builder_admin_nonce` for admin)
- Input sanitization via `sanitize_diploma_data()`
- Data validation via `validate_diploma_data()` enforcing required fields
- Capability checks: `current_user_can('manage_options')` for admin actions
- User ownership validation for diploma access/modification
- Prepared statements for SQL operations

**5. Frontend JavaScript (`diploma-builder.js`)**
- Multi-step form navigation with validation
- Real-time live preview updates using `currentConfig` object
- Form fields: `diploma_style`, `paper_color`, `emblem_type`, `school_name`, `student_name`, `graduation_date`, `city`, `state`, `document_type`, `diploma_size`, `signature_count`
- Dynamic diploma size options based on `document_type`:
  - GED/High School: `8.5x11`, `7.5x9.5`
  - College/University: `8.5x11`, `11x14`
- Signature handling: Shows/hides second signature field based on `signature_count`
- Country selection logic disables fields for non-USA countries
- html2canvas integration for diploma image generation
- Purchase flow: Creates diploma config, adds to cart, redirects to checkout

**6. Assets Management (`class-diplomabuilder-assets.php`)**
- Conditional loading based on shortcode presence
- Enqueues external libraries: html2canvas (CDN), jsPDF (CDN)
- Script localization passes data to JS:
  ```php
  wp_localize_script('diploma-builder', 'diploma_ajax', [
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('diploma_builder_nonce'),
      'is_user_logged_in' => is_user_logged_in(),
      'is_customer' => wc_customer_bought_product(...),
      'is_admin' => current_user_can('manage_options')
  ]);
  ```
- Customer status checked by verifying WooCommerce purchases against product IDs from settings

**7. Admin Interface (`class-diplomabuilder-admin.php`)**
- Menu pages: Main "Diploma Builder" and "Settings" submenu
- Renders diploma list table and settings form
- Settings options: `diploma_allow_guests`, `diploma_max_per_user`, product IDs for WooCommerce integration

## Common Development Tasks

### Adding a New Diploma Style
1. Define style in `class-diplomabuilder-frontend.php::get_diploma_styles()`:
   ```php
   'style_id' => [
       'name' => 'Style Name',
       'description' => 'Description',
       'preview_url' => DIPLOMA_BUILDER_URL . 'assets/previews/style_id.png'
   ]
   ```
2. Add CSS styling in `assets/diploma-builder.css` targeting `.diploma-preview[data-style="style_id"]`
3. Add preview image to `assets/previews/style_id.png`

### Adding a New AJAX Endpoint
1. Register action in `class-diplomabuilder-ajax.php::__construct()`:
   ```php
   add_action('wp_ajax_diploma_action_name', [$this, 'handle_action_name']);
   add_action('wp_ajax_nopriv_diploma_action_name', [$this, 'handle_action_name']); // If public
   ```
2. Implement handler method:
   ```php
   public function handle_action_name() {
       try {
           $this->verify_nonce($_POST['nonce'] ?? '');
           // Sanitize input
           // Validate data
           // Perform operation
           wp_send_json_success(['data' => $result]);
       } catch (Exception $e) {
           wp_send_json_error($e->getMessage());
       }
   }
   ```
3. Call from JS using `diploma_ajax.ajax_url` and `diploma_ajax.nonce`

### Modifying Database Schema
1. Update SQL in `class-diplomabuilder-database.php::create_tables()`
2. Increment `TABLE_VERSION` constant
3. Update `save_diploma()`, `get_diploma()` methods to handle new fields
4. Deactivate and reactivate plugin, or run `update_db_check()` manually

### Adding New Form Fields
1. Add to `currentConfig` object in `diploma-builder.js`
2. Add HTML field in `class-diplomabuilder-frontend.php::render_diploma_builder()`
3. Bind event listener in JS to update `currentConfig` and trigger `updatePreview()`
4. Update database schema and AJAX handlers if field needs persistence

## WordPress Conventions

- **Internationalization**: All user-facing strings use `__()` or `_e()` with `'diploma-builder'` text domain
- **Constants**: Plugin uses `DIPLOMA_BUILDER_VERSION`, `DIPLOMA_BUILDER_URL`, `DIPLOMA_BUILDER_PATH`
- **Hooks**: Use WordPress actions/filters for extensibility
- **Direct Access Prevention**: All files start with `if (!defined('ABSPATH')) exit;`
- **Database**: Uses `$wpdb` for custom tables, `dbDelta()` for schema updates
- **Security**: Nonces, capability checks, input sanitization, output escaping (`esc_html()`, `esc_attr()`)

## Git Workflow

**Current Branch:** `new-requirements`
**Main Branch:** `master`

Recent commits focus on layout options, signature handling, and template updates.

## Important Notes

- **Local Development Path**: `/home/shakhawat/Local Sites/diplomabuilder/app/public/wp-content/plugins/diploma-builder`
- **Upload Directory**: `/wp-content/uploads/diplomas/` (created on activation)
- **External Dependencies**: html2canvas and jsPDF loaded from CDN
- **WooCommerce Integration**: Optional - plugin works standalone but purchase flow requires WooCommerce
- **Database Table**: `wp_diploma_configurations` persists across plugin deactivation but removed on uninstall
- **Asset Versioning**: CSS/JS use `DIPLOMA_BUILDER_VERSION` for cache busting
- **Guest Access**: Controlled by `diploma_allow_guests` option; guests see watermarked previews
- **Emblem Assets**: Located in `assets/emblems/{generic,states}/` as PNG files
