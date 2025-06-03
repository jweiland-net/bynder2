..  _upgrade:

Upgrade Guide
=============

If you are updating the TYPO3 extension :t3ext:`bynder2` to a newer version, please ensure
you read this section attentively to avoid potential issues.

Upgrade to Version 2.0.0
------------------------

This version introduces support for the new OAuth 2.0 authentication system. As a result, all
existing credentials stored in the file storage configuration are no longer valid.

**Steps to Upgrade**:

#.  Update your Bynder application to use OAuth 2.0, or alternatively, create a new application
    in Bynder configured for OAuth 2.0.
#.  Input the new OAuth 2.0 credentials into the Bynder file storage record within your
    TYPO3 installation.

Upgrade to Version 1.0.5
------------------------

In this version, the `bynder2` cache has been divided into two distinct cache tables:

*   bynder2_fileinfo
*   bynder2_pagenav

**Steps to Upgrade**:

#.  Update your database schema to accommodate the new cache tables.
#.  Remove the outdated `bynder2` cache tables from your database.

Upgrade to Version 1.0.0
------------------------

This was the initial release of the extension. No specific updates are required.
