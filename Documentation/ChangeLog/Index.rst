..  _changelog:

=========
ChangeLog
=========

Version 3.0.0 Release Notes
===========================

#.  **Compatibility Enhancements**

    *   Added compatibility for TYPO3 version 12.
    *   Removed support for older TYPO3 versions to streamline future development.

#.  **File Synchronization**

    *   File synchronization will now exclusively be handled via scheduler tasks to enhance stability
        and reduce complexity.

#.  **Performance Improvements**

    *   Implemented CDN thumbnail (`mini`) from Bynder for backend previews,
        significantly improving preview performance.
    *   Utilized CDN thumbnail (`webimage`) from Bynder for public URLs in the backend,
        ensuring faster and more reliable access.

#.  **Technical Updates**

    *   Switched the Bynder driver to use the `sys_file` table as a foundational data source.
    *   Converted the `fileinfo` cache to a transient cache. This cache now facilitates the transfer
        of file responses between objects without long-term storage.

#.  **Deprecated Features**

    *   Removed page navigation cache (`pagenav cache`) functionality to improve responsiveness
        and adaptability.

Version 2.0.4 Release Notes
===========================

*   Add BynderServiceFactory to simplify DI

Version 2.0.3 Release Notes
===========================

*   Exclude .github folder in .gitattributes
*   Update .gitignore file

Version 2.0.2 Release Notes
===========================

*   Add logger to BynderService and SyncBynderFilesCommand
*   Add specific log file for EXT:bynder2
*   Use PHP generator/yield to reduce memory while retrieving thousands of files
*   Inject TYPO3 Guzzle config from TYPO3_CONF_VARS/HTTP to BynderClient

Version 2.0.1 Release Notes
===========================

*   Update ext icon
*   Update title in documentation
*   Remove minimal example from documentation

Version 2.0.0 Release Notes
===========================

*   Update to new Bynder PHP SDK
*   Make use of new OAuth 2.0 authorization

Version 1.0.7 Release Notes
===========================

*   Move all bynder calls into its own BynderService
*   Catch various Exceptions of Bynder calls
*   Better usage of caches
*   Use FAL indexer to fetch all files and mark missing ones
*   Add OrderingUtility

Version 1.0.6 Release Notes
===========================

*   Clear the two new caches in sync command

Version 1.0.5 Release Notes
===========================

*   Use 2 caches to differ between pagenav and fileinfo cache

Version 1.0.4 Release Notes
===========================

*   Add option to use transient cache to retrieve most current files

Version 1.0.3 Release Notes
===========================

*   Add option to set number of files in file browser
*   Add functional tests

Version 1.0.0 Release Notes
===========================

*   Initial upload
*   Only TYPO3 10 compatibility
*   Supports only OAuth 1
