=== BP Resume CSV Import/Export ===
Contributors: wbcomdesigns
Tags: buddypress, resume, csv, import, export, profile, bulk-edit
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

CSV Import/Export functionality for BuddyPress Resume Manager. Allows users to download sample CSV templates and upload resume data in bulk.

== Description ==

**BP Resume CSV Import/Export** is a powerful add-on for BuddyPress Resume Manager that enables users to import and export their resume data using CSV files. This plugin streamlines the process of managing resume information by allowing bulk data entry and backup capabilities.

= Key Features =

* **CSV Template Generation** - Download pre-formatted CSV templates with all available resume fields
* **Bulk Data Import** - Import multiple resume fields at once from CSV files
* **Data Export** - Export current resume data for backup or external editing
* **Enhanced Field Detection** - Automatically detects available resume fields based on user permissions
* **Drag & Drop Upload** - Modern file upload interface with drag and drop support
* **Data Validation** - Validates CSV data before import to prevent errors
* **Field Type Support** - Supports all BP Resume Manager field types including text, email, URL, dates, dropdowns, and more
* **Repeater Field Support** - Handles repeater fields for multiple entries (work experience, education, etc.)
* **Group Permissions** - Respects field group availability based on user roles and member types
* **Progress Tracking** - Real-time upload progress and detailed import feedback
* **Mobile Responsive** - Works seamlessly on desktop and mobile devices

= How It Works =

1. **Download Template**: Get a CSV template with all your available resume fields
2. **Edit in Spreadsheet**: Fill in your data using Excel, Google Sheets, or any CSV editor
3. **Upload & Import**: Drag and drop your CSV file to import all data at once
4. **Export Anytime**: Create backups by exporting your current resume data

= Perfect For =

* Users with extensive resume data to enter
* Bulk updates to existing resume information
* Creating backups of resume data
* Migrating data between profiles
* Administrative data management

= Requirements =

* WordPress 5.0 or higher
* PHP 7.4 or higher
* BuddyPress plugin (active)
* BP Resume Manager plugin (recommended)

= Compatibility =

* Works with all BP Resume Manager field types
* Supports multisite installations
* Compatible with member types and user role restrictions
* Responsive design for all devices

== Installation ==

= Minimum Requirements =

* WordPress 5.0 or greater
* PHP version 7.4 or greater
* BuddyPress plugin activated
* BP Resume Manager plugin (recommended for full functionality)

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Navigate to Plugins > Add New
3. Search for "BP Resume CSV Import Export"
4. Click "Install Now" and then "Activate"

= Manual Installation =

1. Download the plugin zip file
2. Log in to your WordPress admin panel
3. Navigate to Plugins > Add New > Upload Plugin
4. Choose the downloaded zip file and click "Install Now"
5. Activate the plugin

= After Installation =

1. Ensure BuddyPress and BP Resume Manager are installed and activated
2. Configure your resume fields in BP Resume Manager
3. Users can access CSV import/export via their profile Resume tab

== Frequently Asked Questions ==

= Do I need BP Resume Manager for this plugin to work? =

While the plugin can function without BP Resume Manager, it's highly recommended for full functionality. Without it, the plugin will provide sample templates for demonstration purposes.

= What CSV format should I use? =

Download the template from your resume page to get the exact format required. The template includes all necessary columns and sample data to guide you.

= Can I import partial data? =

Yes! You don't need to fill all fields in the CSV. Empty fields will be skipped during import, and you can import additional data later.

= What happens to my existing data during import? =

Import will update existing fields with new values from the CSV. We recommend exporting your current data as a backup before importing new data.

= Are there file size limits? =

Yes, the default maximum file size is 5MB. This can be adjusted by administrators and is also limited by your server's upload limits.

= What file types are supported? =

Only CSV files are supported. If you have Excel files, save them as CSV format before uploading.

= Can I import repeater fields? =

Yes! The plugin fully supports repeater fields like multiple work experiences, education entries, etc. The template will show you how to format these correctly.

= Is my data secure? =

Yes, all uploads are processed server-side and temporary files are cleaned up after import. The plugin follows WordPress security best practices.

= Can I undo an import? =

There's no automatic undo feature. We strongly recommend exporting your current data before importing new data so you can restore it if needed.

= Does it work with custom field types? =

The plugin supports all standard BP Resume Manager field types. Custom field types may work but aren't guaranteed to be fully compatible.

== Screenshots ==

1. CSV Import/Export interface showing statistics and available options
2. Export options with template download and current data export
3. File upload area with drag and drop functionality
4. Available fields display showing all resume fields organized by groups
5. Upload progress and success message after import
6. Sample CSV template showing proper formatting

== Changelog ==

= 1.0.0 =
* Initial release
* CSV template generation with all available fields
* Bulk data import with validation
* Current data export functionality
* Enhanced field detection based on user permissions
* Support for all BP Resume Manager field types
* Repeater field support for multiple entries
* Drag and drop file upload interface
* Real-time upload progress tracking
* Mobile responsive design
* Comprehensive error handling and validation
* Security measures for file uploads

== Upgrade Notice ==

= 1.0.0 =
Initial release of BP Resume CSV Import/Export plugin.

== Support ==

For support, documentation, and feature requests, please visit:

* [Plugin Documentation](https://docs.wbcomdesigns.com/)
* [Support Forum](https://wbcomdesigns.com/support/)
* [Contact Us](https://wbcomdesigns.com/contact/)

== Privacy Policy ==

This plugin does not collect or store any personal data beyond what is already stored by WordPress and BuddyPress. All CSV files are processed locally on your server and are automatically cleaned up after import.

== Credits ==

* Developed by [Wbcom Designs](https://wbcomdesigns.com/)
* Requires [BuddyPress](https://buddypress.org/) and [BP Resume Manager](https://wbcomdesigns.com/downloads/buddypress-resume-manager/)

== Contribute ==

We welcome contributions! If you'd like to contribute to the development of this plugin, please visit our development resources or contact us.
