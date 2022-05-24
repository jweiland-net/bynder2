.. include:: ../Includes.txt


.. _known-problems:

==============
Known Problems
==============

TYPO3 11 compatibility
======================

As long as we don't have any OAuth 2 credentials we still have to use the old Bynder SDK 1.0.9. But this
package needs Guzzle in version 6. As TYPO3 11 needs at least Guzzle in version 7.3 we can
not upgrade to new Bynder SDK.

Caching
=======

We cache the file information for each bynder file by default in database based cache. If you add new files
at bynder it may happen that you will not see this file in filelist module. Please execute the
synchonisation command of EXT:bynder2. If you don't have access to CLI you also can create a
new scheduler task `Execute CLI command` and select `fal:bynder:sync` from selectbox.

Executing this command will clear full FAL bynder cache and start a re-sync of all files.
