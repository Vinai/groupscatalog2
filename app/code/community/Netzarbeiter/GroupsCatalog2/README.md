/**
 * Netzarbeiter
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this Module to
 * newer versions in the future.
 *
 * @category   Netzarbeiter
 * @package    Netzarbeiter_GroupsCatalog2
 * @copyright  Copyright (c) 2012 Vinai Kopp http://netzarbeiter.com
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

== ABOUT ==

This extension enables you to hide categories and products from customers depending on their customer
group. It is a rewrite of the Magento extension Netzarbeiter_GroupsCatalog for Magento 1.6 and newer.

If you use Magento 1.5 or 1.4 please refer to the older extension:
http://www.magentocommerce.com/magento-connect/netzarbeiter-groupscatalog.html

This rewrite not only cleans up the code base, it also adds several new features and improvements:
- Configurable if you want to hide everything and select products and categories to show or vica versa.
- Use of an index which means support of an unlimited customer groups without DB table hacks.
- Faster frontend usage, especially noticeable with large catalogs and complex settings.
- Fully configurable on a store view level.

The source to this extension can be found on github: https://github.com/Vinai/groupscatalog2

== USAGE ==

You can specify a default visibility setting for all categories and products under
System / Configuration / Netzarbeiter Extensions / Groups Catalog 2

There you can also choose to disable the extension (on a store view level).

The default after installation is no categories or products are hidden.
You can override the default settings for every product and category in the Product
Management and Category Management pages.

If you use some non-standard mechanism or import for products and categories, it might be necessary to
rebuild the GroupsCatalog index. You can do so by visiting the Page System / Index Management.
There check the checkboxes beside the indexes "GroupsCatalog Products" and "GroupsCatalog Categories", select the
"Reindex Data" action and click the "Submit" button.


== INSTALL ==

After installation please refresh the (config, layout and block_html) cache, and then log out of the admin area
and log back in again to avoid getting a 404 error on the Module configuration page.
Then visit the configuration page at
System / Configuration / Netzarbeiter Extensions / Groups Catalog 2
and configure as needed.

== UPGRADE from 1.5 ==

To upgrade, first create a backup (file system and database).
Then install the GroupsCatalog 2 module, and visit the admin page at
System - Tools - Groups Catalog 2 Migration
There you will find a step-by-step wizard assisting you to migrate all settings for the system configuration,
all products and all categories.

== UNINSTALL ==

If you ever uninstall the extension (I don't hope so ;)) your site will be broken, because
Magento does not support a mechanism to automatically execute a script when an extension is
removed. This script adds two attributes with custom source, frontend and backend models,
and when the extension is removed Magento can't find those models anymore.
To fix the Error, you have to execute the following SQL:

    DELETE FROM `eav_attribute` WHERE attribute_code = 'groupscatalog2_groups';
    DELETE FROM `core_resource` WHERE code = 'netzarbeiter_groupscatalog2_setup';

Don't forget to clear the cache, afterwards.


== CHANGES ==
0.2.3 - Add notice to reindex when a new customer group is created
0.2.2 - Filter out no longer valid customer groups during indexing
0.2.1 - Fix issue with frontend sitemap
0.2.0 - Provide ability to (de)activate te module on the fly (PHP code level)
      - Set after-auth-url for redirects to login page
0.1.9 - Fix bug I couldn't reproduct when the flat catalog is enabled
0.1.6 - Fix indexer to work with large catalogs and identical settings for most stores
        Add dutch translation
0.1.5 - Avoid joinTable which seems to trigger an exception for some users
0.1.4 - Fix bug that didn't apply website and store default settings during indexing
0.1.3 - Fix bug that stopped the hidden message to be displayed for categories
0.1.2 - Add italian translation - thanks to Marco Gallopin!
0.1.0 - Initial release of the GroupsCatalog2 module


== KNOWN ISSUES ==

Currently, none.
If you find any please write me an email to "vinai (you know what) netzarbeiter [the little round thing] com"
with Netzarbeiter_GroupsCatalog2 as part of the subject. Thanks.

== CONTACT ==

Please write me an email with ideas or bugreports to "vinai (you know what) netzarbeiter [the little round thing] com"
with Netzarbeiter_GroupsCatalog2 as part of the subject.
