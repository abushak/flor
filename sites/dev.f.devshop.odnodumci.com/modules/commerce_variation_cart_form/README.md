
INTRODUCTION
------------

This module provides an add-to-cart form for individual Commerce Product 
Variation entities. The form is added to the Product Variation "manage display" 
as a pseudo-field.

The form elements may be configured via a form display mode, 
"Variation Cart Form", on the Commerce Order Item Type "manage form display" 
(similar to configuration for the standard Commerce Product add-to-cart form).

A typical use case is where the standard Commerce Product add-to-cart form 
needs to be replaced with the individual Product Variations as rendered 
entities, each with their own add-to-cart functionality, or where an individual 
Product Variation entity needs to be displayed standalone with its own 
add-to-cart functionality (including Views). Providing the cart form as a 
pseudo-field allows the add-to-cart form to be displayed in any position among 
the entity fields.

* For a full description of the module, visit the project page:
  https://www.drupal.org/sandbox/johnpitcairn/2837381

* To submit bug reports and feature suggestions, or to track changes:
  https://www.drupal.org/project/issues/2837381


REQUIREMENTS
------------

This module requires the Commerce module (https://drupal.org/project/commerce) 
submodules:

 * Commerce Product
 * Commerce Order
 * Commerce Cart


INSTALLATION
------------

Install as you would normally install a contributed Drupal module. See:
  https://drupal.org/documentation/install/modules-themes/modules-8


CONFIGURATION
-------------

* Configure Order Item Types in 
  Administration >> Commerce >> Configuration >> OrderItem Types
  For each Order Item Type:

  - Select Operations >> Manage form display.

  - Select the "Variation Cart Form" form view mode.

  - Show or hide and configure form fields as desired. Typically you will will 
    either want only the "Quantity" field displayed, or no fields displayed 
    (to show an "add to cart" button with default quantity 1).

  - Click "Save" to save your changes.


* Configure Product Variation Types in 
  Administration >> Commerce >> Configuration >> Product Variation Types
  For each Product Variation:

  - Select Operations >> Manage display.

  - Select the appropriate view mode.

  - Set the "Add to cart form" field to "visible".

  - Check the "Combine order items containing the same product variation" 
    checkbox if you want the added Product Variation to be combined with the 
    same item if it already exists in the user's cart.

  - Click "Save" to save your changes.


* Configure Product Types in 
  Administration >> Commerce >> Configuration >> Product Types
  For each Product Type:

  - Select Operations >> Manage display.

  - Select the appropriate display mode.

  - For the "Variations" field, select "Rendered Entity" instead of 
    "Add to cart form".

  - Click the settings icon and select the view mode that you configured in the 
    previous step.

  - Click "Update" to update the settings, then click "Save" to save your 
    changes.
