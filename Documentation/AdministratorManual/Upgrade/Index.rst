.. include:: ../../Includes.txt


.. _upgrade:

Upgrade
=======

If you upgrade EXT:bynder2 to a newer version, please read this section carefully!

Update to Version 2.0.0
-----------------------

This version makes use of the new OAuth system. That means that all values in file storage record are invalid now.
At first you have to update your Bynder App to use the new OAuth 2.0 technology or create a new one.
Now you can fill in the new credentials of your Bynder App to the bynder file storage record.

Update to Version 1.0.5
-----------------------

We have split the `bynder2` into two new caches `bynder2_fileinfo` and `bynder2_pagenav`.
Please update your database schema. You can remove the old `bynder2` cache tables from DB.

Update to Version 1.0.0
-----------------------

First release. Nothing to update ;-)
