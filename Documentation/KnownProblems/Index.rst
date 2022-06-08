.. include:: ../Includes.txt


.. _known-problems:

==============
Known Problems
==============

Caching
=======

We cache the file information for each bynder file by default in database based cache. If you add new files
at bynder it may happen that you will not see this file in filelist module. Please execute the
synchonisation command of EXT:bynder2. If you don't have access to CLI you also can create a
new scheduler task `Execute CLI command` and select `fal:bynder:sync` from selectbox.

Executing this command will clear full FAL bynder cache and start a re-sync of all files.
