# Diploma Builder WordPress Plugin - Project Documentation

## Project Overview

Diploma Builder is a WordPress plugin that allows users to create custom high school diplomas with live preview and print-ready output. The plugin provides an interactive form-based interface where users can customize diplomas with various styles, colors, emblems, and text fields.

## Completed Features

All required features have been implemented:

1. **5 U.S. High School Diploma Styles**:
   - Classic Traditional (1 emblem)
   - Modern Elegant (2 emblems)
   - Formal Certificate (1 emblem)
   - Decorative Border (2 emblems)
   - Minimalist Clean (1 emblem)

2. **4 Paper Color Options**:
   - Classic White
   - Ivory Cream
   - Light Blue
   - Light Gray

3. **Emblems**:
   - 5 Generic Options: Graduation Cap, Diploma Seal, Academic Torch, Laurel Wreath, School Crest
   - State Emblems: All 50 U.S. states with automatic loading when selected

4. **State Emblem Function**:
   - Users can select "State Emblem" and choose from a dropdown of all 50 states
   - The corresponding state emblem automatically loads into the design

5. **Custom Text Fields**:
   - School Name (required)
   - Student Name (optional)
   - Date of Graduation (required)
   - City (required)
   - State (required)

6. **Live Preview**:
   - All changes update instantly without page reload
   - Real-time rendering of diploma with all customizations

7. **Save & Output**:
   - Store editable configuration in database
   - Generate high-resolution print-ready PNG files
   - Download functionality for generated diplomas

## User Flow

1. **Select Diploma Style**: Choose from 5 professionally designed templates
2. **Select Paper Color**: Choose from 4 color options
3. **Select Emblem Type**:
   - Generic: Choose from 5 options
   - OR State Emblem: Select state from dropdown → emblem loads automatically
4. **Enter Custom Text**:
   - School Name
   - Date of Graduation
   - City
   - State
5. **Live Preview**: Updates instantly with all changes
6. **Review and Edit**: Make adjustments as needed
7. **Save Configuration**: Store diploma for future access
8. **Generate Print-Ready File**: Download high-resolution diploma

## Technology Stack

- **Backend**: PHP (WordPress plugin architecture)
- **Frontend**: HTML, CSS, JavaScript (jQuery)
- **Database**: WordPress database with custom tables
- **Libraries**: html2canvas for image generation
- **Assets**: SVG-based placeholder images for all emblems and previews

## File Structure

```
diploma-builder/
├── diploma-builder.php          # Main plugin file
├── assets/
│   ├── diploma-builder.css      # Frontend styling
│   ├── diploma-builder.js       # Frontend JavaScript functionality
│   ├── diploma-builder-admin.css # Admin styling
│   ├── diploma-builder-admin.js # Admin JavaScript functionality
│   ├── emblems/
│   │   ├── generic/             # Generic emblem SVG files
│   │   └── states/              # State emblem SVG files
│   └── previews/                # Diploma style preview SVG files
└── includes/
    ├── class-diploma-builder-ajax.php     # AJAX handling
    ├── class-diploma-builder-frontend.php # Frontend functionality
    ├── class-diploma-builder-admin.php    # Admin functionality
    ├── class-diplomabuilder-assets.php    # Asset management
    └── class-diplomabuilder-database.php  # Database operations
```

## Plugin Architecture

### Main Plugin File (diploma-builder.php)
- Plugin metadata and initialization
- Autoloader for classes with fixed naming convention
- Activation/deactivation hooks
- Core plugin class with singleton pattern

### Database Layer (class-diplomabuilder-database.php)
- Database table creation and management
- Diploma CRUD operations
- Statistics and cleanup functions

### Frontend (class-diploma-builder-frontend.php)
- Shortcode implementation (`[diploma_builder]`, `[diploma_gallery]`)
- Form rendering and user interface
- Asset enqueueing (CSS/JS)
- Template data (styles, colors, emblems)
- Fixed JavaScript escaping issues

### AJAX Handler (class-diploma-builder-ajax.php)
- Save diploma configurations
- Generate diploma images
- Load diploma previews
- State emblem loading
- Admin-only actions (delete, export, stats)
- Fixed JavaScript escaping issues in onerror attributes

### Admin (class-diploma-builder-admin.php)
- WordPress admin menu integration
- Diploma management interface
- Settings configuration
- Statistics dashboard

### Assets (class-diplomabuilder-assets.php)
- Frontend and admin asset enqueueing
- Localization script setup

### Frontend Assets
- **CSS**: Responsive styling for the builder interface and diploma templates
- **JS**: Interactive form handling, live preview updates, AJAX communication
- **Images**: SVG emblem graphics and style previews
- **Error Handling**: Graceful fallback for missing images

## Database Structure

### wp_diploma_configurations
- id (primary key)
- user_id (nullable)
- diploma_style
- paper_color
- emblem_type
- emblem_value
- school_name
- student_name
- graduation_date
- city
- state
- configuration_data (JSON)
- image_path
- is_public
- download_count
- created_at
- updated_at

## Shortcodes

### `[diploma_builder]`
Main diploma creation interface
Parameters:
- `style`: Default diploma style (default: 'default')
- `show_gallery`: Display recent diplomas (default: 'false')
- `max_width`: Maximum width for container (default: '1400px')

### `[diploma_gallery]`
Display gallery of created diplomas
Parameters:
- `limit`: Number of diplomas to show (default: 12)
- `columns`: Number of columns (default: 3)
- `show_user_only`: Show only current user's diplomas (default: 'false')
- `show_public_only`: Show only public diplomas (default: 'true')

## Admin Features
- Diploma management (view, delete, bulk delete)
- Export diplomas to CSV
- Statistics dashboard
- Settings configuration (guest access, limits, defaults)

## Security Considerations
- Nonce verification for all AJAX requests
- User capability checks for admin actions
- Data sanitization and validation
- Direct access prevention in all PHP files

## Performance Considerations
- SVG-based assets for small file sizes
- Efficient database queries with proper indexing
- Caching of frequently accessed data
- Cleanup of old temporary files

## Recent Fixes
- Fixed JavaScript escaping issues in onerror attributes
- Fixed autoloader naming convention
- Improved error handling for missing images
- Verified all PHP files have correct syntax

## Future Enhancements
- Additional diploma templates
- More emblem options
- Custom font support
- Multi-language support
- Social sharing features
- Email delivery options