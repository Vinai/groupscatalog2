Groups Catalog 2
================
This Magento extension enables you to hide categories and products from customers depending on their customer group.
(It is a rewrite of the extension Netzarbeiter_GroupsCatalog for Magento 1.6 and newer.)

Facts
-----
- version: check the [config.xml](https://github.com/Vinai/groupscatalog2/blob/master/app/code/community/Netzarbeiter/GroupsCatalog2/etc/config.xml)
- extension key: Netzarbeiter_GroupsCatalog2
- extension on Magento Connect: -
- Magento Connect 1.0 extension key: -
- Magento Connect 2.0 extension key: -
- [extension on GitHub](https://github.com/Vinai/groupscatalog2)
- [direct download link](https://github.com/Vinai/groupscatalog2/zipball/master)

Description
-----------
This Magento extension enables you to hide categories and products from customers depending on their customer group.
It is a rewrite of the extension Netzarbeiter_GroupsCatalog for Magento 1.6 and newer.

If you use Magento 1.5 or 1.4 please DO NOT use this extension.

This rewrite not only cleans up the code base, it also adds several new features and improvements:

- Configurable if you want to hide everything and select products and categories to show or vica versa.
- Use of an index to support of an unlimited number of customer groups (without DB table hacks).
- Faster frontend usage, especially noticeable with large catalogs and complex settings.
- Fully configurable on a store view level.
- Should work with Magento using Oracel and MSSQL as well as MySQL (I can't test this, though).

Usage
-----
You can specify a default visibility setting for all categories and products under
System - Configuration - Netzarbeiter Extensions - Groups Catalog 2

There you can also choose to disable the extension (on a store view level).

The default after installation is no categories or products are hidden.
You can override the default settings for every product and category in the Product
Management and Category Management pages.

If you use some non-standard mechanism or import for products and categories, it might be necessary to
rebuild the GroupsCatalog index. You can do so by visiting the Page System - Index Management.
There check the checkboxes beside the indexes "GroupsCatalog Products" and "GroupsCatalog Categories", select the
"Reindex Data" action and click the "Submit" button.


Compatibility
-------------
- Magento >= 1.6

Installation Instructions
-------------------------
If you are using the Magento compiler, disable compilation before the installation, and after the module is installed, you need to run the compiler again.

1. Install the extension via Magento Connect with the key shown above or copy all the files into your document root.
2. Clear the cache, logout from the admin panel and then login again.
3. Configure and activate the extension under System - Configuration - Netzarbeiter Extensions - Groups Catalog 2
4. Go to the "Manage Indexes" page and rebuild the two GroupsCatalog indexs. Without this step all products will be hidden on the frontend!
5. If you use the Magento compiler tool, recompile after installation

Uninstallation
--------------
To uninstall this extension you need to run the following SQL after removing the extension files:
```sql
  DELETE FROM `eav_attribute` WHERE attribute_code = 'groupscatalog2_groups';
  DELETE FROM `core_resource` WHERE code = 'netzarbeiter_groupscatalog2_setup';
  DELETE FROM `index_process` WHERE indexer_code = 'groupscatalog2_product';
  DELETE FROM `index_process` WHERE indexer_code = 'groupscatalog2_category';
  DROP TABLE IF EXISTS `groupscatalog_product_idx`;
  DROP TABLE IF EXISTS `groupscatalog_category_idx`;
```

Payment Service Provider Notification Requests
----------------------------------------------
Many payment service providers notify Magento about a payment success or failure by sending a request from their server to a Magento URL.
In those cases, the GroupsCatalog2 extension might interfere with that process since the PSP request might be running with a different
Customer Group (e.g. General) then the customer who placed the order.  
The right way to handle such cases is to disable the GroupsCatalog2 extension on the routes of such a payment module.  
This can be achieved by adding the modules route name to the config section `global/netzarbeiter_groupscatalog2/disabled_on_routes`.  
For example: 

```xml
    <global>
        <netzarbeiter_groupscatalog2>
            <disabled_on_routes>
                <paypal/>
                <authorizenet/>
            </disabled_on_routes>
        </netzarbeiter_groupscatalog2>
    </global>
```

The PSP route name can be looked up within the payment modules *etc/config.xml* file.
On requests to routes listed under that node the extension will be inactive.

Upgrade from Magento 1.5
------------------------
To upgrade, first create a backup (file system and database).
Then install the GroupsCatalog 2 module, and visit the admin page at
System - Tools - Groups Catalog 2 Migration
There you will find a step-by-step wizard assisting you to migrate all settings for the system configuration,
all products and all categories.

Support
-------
If you have any issues with this extension, open an issue on GitHub (see URL above)

Contribution
------------
Any contributions are highly appreciated. The best way to contribute code is to open a
[pull request on GitHub](https://help.github.com/articles/using-pull-requests).

Developer
---------
Vinai Kopp  
[http://www.netzarbeiter.com](http://www.netzarbeiter.com)  
[@VinaiKopp](https://twitter.com/VinaiKopp)

Licence
-------
[OSL - Open Software Licence 3.0](http://opensource.org/licenses/osl-3.0.php)

Copyright
---------
(c) 2014 Vinai Kopp
