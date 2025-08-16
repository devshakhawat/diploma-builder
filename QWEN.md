# Diploma Builder WordPress Plugin - Project Documentation

## Project Overview

Diploma Builder is a WordPress plugin that allows users to create custom high school diplomas with live preview and print-ready output. The plugin provides an interactive form-based interface where users can customize diplomas with various styles, colors, emblems, and text fields.

## Key Features

1. **Interactive Diploma Builder**: Multi-step form with live preview
2. **Multiple Diploma Styles**: 5 professionally designed templates
3. **Customization Options**: Paper colors, emblems (generic/state), and text fields
4. **Live Preview**: Real-time rendering of diploma changes
5. **Save and Download**: Save configurations and download high-resolution diplomas
6. **Gallery Display**: Showcase created diplomas
7. **Admin Management**: Backend tools for managing diplomas

## Technology Stack

- **Backend**: PHP (WordPress plugin architecture)
- **Frontend**: HTML, CSS, JavaScript (jQuery)
- **Database**: WordPress database with custom tables
- **Libraries**: html2canvas for image generation

## File Structure

```
diploma-builder/
├── diploma-builder.php          # Main plugin file
├── assets/
│   ├── diploma-builder.css      # Frontend styling
│   ├── diploma-builder.js       # Frontend JavaScript functionality
│   ├── emblems/                 # Emblem images (generic & state)
│   └── previews/                # Diploma style previews
└── includes/
    ├── class-diploma-builder-ajax.php     # AJAX handling
    ├── class-diploma-builder-frontend.php # Frontend functionality
    └── class-diplomabuilder-database.php  # Database operations
```

## Plugin Architecture

### Main Plugin File (diploma-builder.php)
- Plugin metadata and initialization
- Autoloader for classes
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

### AJAX Handler (class-diploma-builder-ajax.php)
- Save diploma configurations
- Generate diploma images
- Load diploma previews
- Admin-only actions (delete, export, stats)

### Frontend Assets
- **CSS**: Responsive styling for the builder interface and diploma templates
- **JS**: Interactive form handling, live preview updates, AJAX communication
- **Images**: Emblem graphics and style previews

## Diploma Customization Options

### Styles
1. Classic Traditional
2. Modern Elegant
3. Formal Certificate
4. Decorative Border
5. Minimalist Clean

### Paper Colors
- Classic White
- Ivory Cream
- Light Blue
- Light Gray

### Emblems
- Generic: Graduation Cap, Diploma Seal, Academic Torch, Laurel Wreath, School Crest
- State: All 50 US states (emblem graphics required)

### Text Fields
- School Name (required)
- Student Name (optional)
- Graduation Date (required)
- City (required)
- State (required)

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

## Development Workflow

### Adding New Diploma Styles
1. Add style definition in `get_diploma_styles()` method in frontend class
2. Create preview image in `assets/previews/` directory
3. Add CSS styling for the new template in `diploma-builder.css`
4. Update JavaScript template definition in `diploma-builder.js`

### Adding New Emblems
1. Add emblem definition in `get_generic_emblems()` method in frontend class
2. Add emblem image in `assets/emblems/generic/` directory
3. Update JavaScript emblem definition in `diploma-builder.js`

### Extending Functionality
1. Add new AJAX actions in `class-diploma-builder-ajax.php`
2. Register actions in constructor
3. Implement frontend JavaScript to call new actions
4. Update database schema if needed

## Admin Features
- Diploma management (delete, bulk delete)
- Export diplomas to CSV
- Statistics dashboard
- Settings configuration

## Security Considerations
- Nonce verification for all AJAX requests
- User capability checks for admin actions
- Data sanitization and validation
- Direct access prevention in all PHP files

## Performance Considerations
- Image optimization for previews
- Efficient database queries with proper indexing
- Caching of frequently accessed data
- Cleanup of old temporary files

## Future Enhancements
- Additional diploma templates
- More emblem options
- Custom font support
- Multi-language support
- Social sharing features
- Email delivery options