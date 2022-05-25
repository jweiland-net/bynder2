.. include:: ../../Includes.txt


.. _extensionSettings:

==================
Extension Settings
==================

Some general settings for bynder2 can be configured in *Admin Tools -> Settings*.

Tab: Basic
==========

numberOfFilesInFileBrowser
""""""""""""""""""""""""""

Default: 100
Maximum: 1000 (This is a limit of Bynder API)

With this option you can set the number of files which should be initially be loaded
in FileBrowser, the PopUp window to choose files from.

This setting is not valid for module filelist.

useTransientCache
"""""""""""""""""

Default: false

By default (false) all retrieved file information of Bynder will be cache in a databased
cache. This will speedup performance a lot. Please create a scheduler task to sync new Bynder
files with TYPO3 FAL. With each run, the cache will be flushed.

If you need most current files and can not wait until next scheduler run, please set this
value to `1`. This will activate a transient cache which is only valid for one TYPO3 request.
