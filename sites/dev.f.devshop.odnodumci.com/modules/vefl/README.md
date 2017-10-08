CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Credits
 * Maintainers


INTRODUCTION
------------
Provides an ability to output Views exposed filters in layouts.

 * For a full description of the module, visit the project page:
   https://drupal.org/project/vefl

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/vefl


FEATURES
--------
 * Provides Default and supports Panels layouts.
 * You can define custom layouts with regions.
 * You can override exposed form template as usual.
 * Supports Better exposed filters module.


REQUIREMENTS
------------
This module requires the following modules:
 * Views (https://drupal.org/project/views)


INSTALLATION
------------
Install as you would normally install a contributed drupal module.
See: https://drupal.org/documentation/install/modules-themes/modules-7
for further information.


CONFIGURATION
-------------
* For site-builders:
  -On views edit page find Exposed form section.
  -Choose Basic (with layout) or Better Exposed Filters (with layout) Exposed form style.
  -Exposed form settings form find Layout settings fieldset.
  -Choose Layout and click Change. Do you need more default layouts?
  -Set in which region each exposed filter will be outputted.
  -Click Apply and have fun.
* For developers:
  -You can define custom layouts, see an example in vefl.api.php.
  -You can override exposed form template as usual:
  -In your theme define views-exposed-form.tpl.php, use $region_widgets variable to output widgets by regions.
  -views-exposed-form--VIEWNAME.tpl.php or views-exposed-form--VIEWNAME--DISPLAYNAME.tpl.php also work.

MAINTAINERS
-----------
Current maintainers:
 * Sergey Korzh (skorzh) - https://drupal.org/user/813560

@todo:
- Check what should be stored in config file
- Test if all actions works well (see getFormActions())
- Test if theme suggestions work well, like vefl-views-exposed-form--VIEWNAME.html.twig
- Adds BEF support (trait must be used)
- Adds sort_bef_combine support
- Refactor the code
- Add/update comments for code
- Update Readme file
- Update Project page
- check issue: https://www.drupal.org/node/2664258
- check issue: https://www.drupal.org/node/2546576