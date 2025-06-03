..  _known-problems:

==============
Known Problems
==============

Missing Files in the Filelist Module
====================================

Starting from version 3.0.0, not all files are synchronized when accessing the Filelist
module to address performance concerns. To ensure your files are displayed, please set
up a new scheduler task. Select "Execute Console Command," choose "fal:bynder:sync," and
configure the remaining options according to your requirements. Once the scheduler task
has been successfully executed, your Bynder files will appear in the Filelist module.

Performance
===========

Within the Configuration tab, at the bottom of the settings list, you will find the option
Folder for manipulated and temporary images etc.

By default, this folder is located within the Bynder file storage. As a result, all processed
or temporary images are transferred via the Bynder API, which significantly impacts performance
due to the slow transfer rates.

To improve rendering speed and overall performance, it is recommended to relocate this folder
to a fast local file storage. For example, if your fileadmin storage has the storage UID 1,
you can set the value to: 1:/_processed_/bynder2. This ensures that temporary files are handled
locally rather than being transferred through the Bynder API.

Attention!
----------

Cleanup required after changing the processed folder storage.

After changing the Folder for manipulated and temporary images to a local
file storage (e.g., UID 1 for fileadmin), it is necessary to clean up existing processed files.

Specifically, all records in the sys_file_processedfile table that reference the previous
Bynder storage (e.g., UID 2) must be deleted. Otherwise, TYPO3 may continue to reference outdated
processed files stored in the remote storage.

For further details, refer to the related TYPO3 issue: https://forge.typo3.org/issues/84069
