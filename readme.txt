# Custom GlotPress Source
Contributors: agencenous, bastho, enzomangiante, aureliefoucher    
Tags: glotpress, translation, localisation, internationalization, premium, custom  
Donate link: https://apps.avecnous.eu/product/custom-glotpress-source/?mtm_campaign=wp-plugin&mtm_kwd=custom-glotpress-source&mtm_medium=wp-repo&mtm_source=donate
Requires at least: 5.3  
Requires PHP: 7.4  
Tested up to: 6.4  
Stable tag: 1.5.2  
License: GPLv2  
License URI: https://www.gnu.org/licenses/gpl-2.0.html  

Allows to manage translations from a custom GlotPress install.

## Description

This plugin allows to manage translations from a custom glotpress install in parralel of the main WordPress tranlsation repository.

It is particullary interresting for translating premium themes or plugins.

Downloads can be done manually from the upgrade page.


## Installation

1. Upload to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress admin
3. You can edit defaults settings in Settings > GlotPress
4. Fill your custom glotpress URL

## Frequently asked questions

### Does the plugin support multisite install?
Yes, but it has to be network actived.
## Changelog

### 1.5.2

- Load after `plugins_loaded` hook
- Update dependencies to latest version

### 1.5.1

- Set cache expiration to 1 day

### 1.5

- Adds support for CiviCRM "Language Update" extension
- Neww `custom_glotpress_source_civicrm_l10n_path` filter hook
- Adds option for error reporting
- Adds requirement for PHP 7.4

### 1.4.2

- Fix Fatal error:  Uncaught Error: Class 'WP_CLI'

### 1.4.1

- More flexible params for  Custom_GlotPress_Source::update_core(), Removes Fatal error in some contexts

### 1.4

- Add "Select all" checkbox in upgrades page
- Add wp-cli commands:
  - Show translation list with WP-CLI command `wp language custom list`
  - Update translation with WP-CLI command `wp language custom update [project-name] [--all]`
- Fix "Undefined property: stdClass::$sub_projects" warning
- Fix "Undefined array key "values" warning"
- Use `wp_get_themes()` instead of deprecated `get_themes()`

### 1.3.1

- Fix Theme interpretation (Bug introduced in 1.2.2)

### 1.3

- Manage "per project" updates
- Use cache for available translation storage

### 1.2.2

- Cleanup domains

### 1.2.1

- Add filter hook on projects
- Add filter hook on file URL

### 1.2

- Add support for CiviCRM extensions

### 1.1

- Initial release
