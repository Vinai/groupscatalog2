About
-----

This Magento extension enables you to hide categories and products from customers
depending on their customer group. It is a rewrite of the extension
Netzarbeiter_GroupsCatalog for Magento 1.6 and newer.

If you use Magento 1.5 or 1.4 please refer to the older extension which is linked
to from this modules page on [Magento Connect][]. Older versions of Magento are no
longer supported (the old module might work, though, but I don't test at all).

This rewrite not only cleans up the code base, it also adds several new features and improvements:

- Configurable if you want to hide everything and select products and categories to show or vica versa.
- Use of an index to support of an unlimited number of customer groups (without DB table hacks).
- Faster frontend usage, especially noticeable with large catalogs and complex settings.
- Fully configurable on a store view level.
- Should work with Magento using Oracel and MSSQL as well as MySQL (I can't test this, though).

Please refer to the [README.txt][] of the extension for more information.

[Magento Connect] http://www.magentocommerce.com/magento-connect/vinai/extension/635/netzarbeiter_groupscatalog "The GroupsCatalog Extension on Magento Connect"

[README.txt]: https://github.com/Vinai/groupscatalog2/blob/master/app/code/community/Netzarbeiter/GroupsCatalog2/README.txt "README.txt"