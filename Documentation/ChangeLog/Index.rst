.. include:: ../Includes.txt


.. _changelog:

=========
ChangeLog
=========

**Version 2.0.3**

- Exclude .github folder in .gitattributes
- Update .gitignore file

**Version 2.0.2**

- Add logger to BynderService and SyncBynderFilesCommand
- Add specific log file for EXT:bynder2
- Use PHP generator/yield to reduce memory while retrieving thousands of files
- Inject TYPO3 Guzzle config from TYPO3_CONF_VARS/HTTP to BynderClient

**Version 2.0.1**

- Update ext icon
- Update title in documentation
- Remove minimal example from documentation

**Version 2.0.0**

- Update to new Bynder PHP SDK
- Make use of new OAuth 2.0 authorization

**Version 1.0.7**

- Move all bynder calls into its own BynderService
- Catch various Exceptions of Bynder calls
- Better usage of caches
- Use FAL indexer to fetch all files and mark missing ones
- Add OrderingUtility

**Version 1.0.6**

- Clear the two new caches in sync command

**Version 1.0.5**

- Use 2 caches to differ between pagenav and fileinfo cache

**Version 1.0.4**

- Add option to use transient cache to retrieve most current files

**Version 1.0.3**

- Add option to set number of files in file browser
- Add functional tests

**Version 1.0.0**

- Initial upload
- Only TYPO3 10 compatibility
- Supports only OAuth 1
