# BP Resume CSV Import/Export

A powerful WordPress plugin that adds CSV import/export functionality to BuddyPress Resume Manager, enabling users to manage their resume data efficiently through bulk operations.

## 🚀 Features

### Core Functionality
- **📥 CSV Template Generation** - Download pre-formatted templates with all available resume fields
- **📤 Bulk Data Import** - Import multiple resume fields simultaneously from CSV files  
- **💾 Data Export** - Export current resume data for backup or external editing
- **🔍 Enhanced Field Detection** - Automatically detects available fields based on user permissions
- **📱 Responsive Design** - Works seamlessly on desktop and mobile devices

### Advanced Features
- **🎯 Drag & Drop Upload** - Modern file upload interface
- **✅ Data Validation** - Comprehensive CSV validation before import
- **🔄 Repeater Field Support** - Handles multiple entries (work experience, education, etc.)
- **👥 Permission Aware** - Respects field group availability based on user roles
- **📊 Progress Tracking** - Real-time upload progress and detailed feedback
- **🛡️ Security** - Secure file handling with automatic cleanup

## 📋 Requirements

| Requirement | Version |
|-------------|---------|
| WordPress | 5.0+ |
| PHP | 7.4+ |
| BuddyPress | Latest |
| BP Resume Manager | Recommended |

## 🔧 Installation

### Automatic Installation (Recommended)

1. Go to **Plugins > Add New** in your WordPress admin
2. Search for "BP Resume CSV Import Export"
3. Click **Install Now** and then **Activate**

### Manual Installation

1. Download the plugin ZIP file
2. Upload to `/wp-content/plugins/` directory
3. Extract the files
4. Activate through the **Plugins** menu in WordPress

### From GitHub

```bash
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/wbcomdesigns/bp-resume-csv.git
```

## 📖 Usage

### For Users

1. **Access the Interface**
   - Navigate to your BuddyPress profile
   - Go to the "Resume" tab
   - Click on "CSV Import/Export"

2. **Download Template**
   - Click "Download Template" to get a CSV file with all your available fields
   - The template includes sample data and proper formatting

3. **Prepare Your Data**
   - Open the template in Excel, Google Sheets, or any CSV editor
   - Fill in your resume information following the provided format
   - Save as CSV format

4. **Import Data**
   - Drag and drop your CSV file or click to browse
   - Review the upload progress
   - Check the import results and any validation messages

5. **Export Data**
   - Click "Export Current Data" to download your resume as CSV
   - Use this for backups or to edit data externally

### CSV Format

The CSV template includes these essential columns:

| Column | Description | Required |
|--------|-------------|----------|
| `group_key` | Field group identifier | ✅ |
| `field_key` | Individual field identifier | ✅ |
| `field_value` | The actual data to import | ✅ |
| `group_instance` | For repeater groups (0 for first) | |
| `field_instance` | For repeater fields (0 for first) | |

## 🎯 Supported Field Types

The plugin supports all BP Resume Manager field types:

- **Text Fields**: Single line text, textarea, email, phone, URL
- **Date Fields**: Calendar picker, year dropdown
- **Selection Fields**: Dropdown, radio buttons, checkboxes
- **Advanced Fields**: Multi-select, text+dropdown combinations
- **Media Fields**: Image uploads (by ID or URL)
- **Location Fields**: Place autocomplete
- **Repeater Fields**: Multiple instances of any field type

## 🔒 Security Features

- **File Validation**: Strict CSV file type checking
- **Size Limits**: Configurable maximum file size (default 5MB)
- **Temporary Files**: Automatic cleanup after processing
- **Permission Checks**: Respects WordPress user capabilities
- **Data Sanitization**: All imported data is properly sanitized

## 🎨 User Interface

### Modern Design Elements
- **Gradient Headers** with statistics display
- **Card-based Layout** for clear organization
- **Interactive Elements** with hover effects and animations
- **Progress Indicators** for file uploads
- **Responsive Grid** that adapts to all screen sizes
- **Icon Integration** for visual clarity

### Accessibility
- **Screen Reader Support** with proper ARIA labels
- **Keyboard Navigation** for all interactive elements
- **High Contrast Mode** support
- **Focus Indicators** for better usability

## 🛠️ Development

### File Structure

```
bp-resume-csv/
├── assets/
│   ├── css/
│   │   ├── csv-style.css              # Basic styles
│   │   └── csv-style-enhanced.css     # Enhanced UI styles
│   └── js/
│       └── csv-handler.js             # Frontend JavaScript
├── includes/
│   ├── class-bp-resume-csv-handler.php           # Core handler
│   ├── class-bp-resume-csv-handler-enhanced.php  # Enhanced features
│   └── helpers.php                    # Utility functions
├── templates/
│   └── csv-interface.php             # Main UI template
├── languages/                        # Translation files
├── bp-resume-csv.php                 # Main plugin file
├── readme.txt                        # WordPress readme
├── readme.md                         # This file
└── uninstall.php                     # Cleanup script
```

### Hooks and Filters

#### Actions
```php
// Fired when CSV data is imported
do_action('bprm_csv_data_imported', $user_id, $imported_count);

// Fired when resume data is saved
do_action('bprm_resume_data_saved', $user_id, $data_type);
```

#### Filters
```php
// Modify available fields for CSV
apply_filters('bprm_csv_available_fields', $fields, $user_id);

// Process field values during import
apply_filters('bprm_csv_process_field_value', $value, $field_type, $field_info);

// Customize CSV headers
apply_filters('bprm_csv_headers', $headers);

// Modify sample CSV data
apply_filters('bprm_csv_sample_rows', $sample_rows, $available_fields);
```

### Contributing

We welcome contributions! Please follow these steps:

1. **Fork** the repository
2. **Create** a feature branch (`git checkout -b feature/amazing-feature`)
3. **Commit** your changes (`git commit -m 'Add amazing feature'`)
4. **Push** to the branch (`git push origin feature/amazing-feature`)
5. **Open** a Pull Request

#### Development Setup

```bash
# Clone the repository
git clone https://github.com/wbcomdesigns/bp-resume-csv.git

# Install dependencies (if any)
cd bp-resume-csv

# Create a symlink to your WordPress plugins directory
ln -s $(pwd) /path/to/wordpress/wp-content/plugins/bp-resume-csv
```

## 🐛 Troubleshooting

### Common Issues

**Q: Import fails with "Security check failed"**
- Ensure you're logged in and have proper permissions
- Check if nonce verification is working correctly

**Q: CSV file is rejected**
- Verify the file is saved as .csv format
- Check file size is under the limit (default 5MB)
- Ensure the file has the required columns

**Q: Fields not showing in template**
- Confirm BP Resume Manager is active and configured
- Check that fields are set to "Display" in field settings
- Verify user has access to the field groups

**Q: Import shows "No valid data found"**
- Check that field_value column contains data
- Verify group_key and field_key match your configuration
- Ensure no required columns are empty

### Debug Mode

For troubleshooting, you can enable WordPress debug mode:

```php
// Add to wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check `/wp-content/debug.log` for detailed error messages.

## 📈 Performance

- **Optimized Processing**: Efficient CSV parsing with minimal memory usage
- **Caching**: Smart caching of field configurations
- **Batch Processing**: Handles large datasets without timeouts
- **Progressive Enhancement**: Graceful degradation for older browsers

## 🌐 Localization

The plugin is translation-ready with:
- **Text Domain**: `bp-resume-csv`
- **POT File**: Available for translators
- **RTL Support**: Right-to-left language support
- **Date Formats**: Respects WordPress locale settings

## 📊 Analytics & Tracking

- **Import Statistics**: Track successful imports and field counts
- **Error Logging**: Detailed logging for troubleshooting
- **Performance Metrics**: Monitor upload and processing times
- **User Activity**: Optional activity logging (respects privacy)

## 🔗 Integration

### BuddyPress Integration
- **Profile Navigation**: Seamlessly integrated into resume navigation
- **User Context**: Respects current user and profile viewing permissions
- **Activity Stream**: Optional integration with BuddyPress activity

### Third-party Compatibility
- **Multisite**: Full WordPress multisite support
- **Member Types**: Integrates with BuddyPress member types
- **User Roles**: Respects WordPress user role capabilities

## 📝 License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

## 👥 Support & Community

- **Documentation**: [docs.wbcomdesigns.com](https://docs.wbcomdesigns.com/)
- **Support Forum**: [wbcomdesigns.com/support](https://wbcomdesigns.com/support/)
- **Bug Reports**: [GitHub Issues](https://github.com/wbcomdesigns/bp-resume-csv/issues)
- **Feature Requests**: [GitHub Discussions](https://github.com/wbcomdesigns/bp-resume-csv/discussions)

## 🏆 Credits

**Developed by**: [Wbcom Designs](https://wbcomdesigns.com/)

**Special Thanks**:
- BuddyPress community for the excellent framework
- WordPress community for continuous inspiration
- All beta testers and contributors

---

**Made with ❤️ for the BuddyPress community**

*If you find this plugin helpful, please consider [leaving a review](https://wordpress.org/plugins/bp-resume-csv/) or [contributing to development](https://github.com/wbcomdesigns/bp-resume-csv).*
