Provides the ability to change the currency shown for products in multi currency environment.
At this point of time, for making this module to work, you need to create one field per currency.

STEPS:
1. After enabling the module, Go to admin/commerce/config/product-variation-types/default/edit/fields, and addfields per currency in the following format: field_price_CURRENCY_CODE ex: for usd field_price_usd, for inr field_price_inr.

For the individual field settings, enable only that currency. Ex. the field field_price_usd should only have USD currency enabled in list of currencies in its field settings.

2. Then place the block "commerce currency switcher" in header or whatever region you want.

3. Then go to admin/commerce/config/product-variation-types/default/edit/display and move the new currency fields fields to disabled, and change the formatter for price field to "calculated price".
