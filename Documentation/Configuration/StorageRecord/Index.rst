..  _record:

==============
Storage Record
==============

Configure Bynder Storage
========================

Permanent Token
---------------

Follow this configuration path, if you have a permanent token available.
You can request a permanent token from Bynder Support: https://support.bynder.com/hc/en-us/articles/360013875300-Permanent-Tokens

..  rst-class:: bignums

    1.  Create new file storage

        Create new file storage record `sys_file_storage` on root page (page with UID 0) and give it a name.

    2.  Storage type

        Switch storage type to `Bynder`

    3.  Add Bynder domain

        Set Bynder domain. Example: `domain.bynder.com`

        .. hint::

            Do not prefix the domain with http:// nor https://

    4.  Permanent Token

        Set permanent token you have get from Bynder Support

    5.  Set temporary folder

        `bynder2` can not write files to Bynder, so you have to use another folder for temporary files. Please set
        `Folder for manipulated and temporary images etc.` to something like `1:/_processed_/`.

Access Token
------------

If you don't have access to permanent tokens you have to configure Bynder storage with access token.

..  rst-class:: bignums

    1.  Create new file storage

        Create new file storage record `sys_file_storage` on root page (page with UID 0) and give it a name.

    2.  Storage type

        Switch storage type to `Bynder`

    3.  Add Bynder domain

        Set Bynder domain. Example: `domain.bynder.com`

        ..  hint::

            Do not prefix the domain with http:// nor https://

    4.  Add Redirect Callback

        Set redirect callback. It must be exactly the same value you have set in your Bynder App. We prefer to set this
        value to something like `https://[yourDomain]/typo3/index.php` in your Bynder App.

    5.  Add Client ID

        Set Client ID. You will find this value in your Bynder App.

    6.  Add Client Secret

        Set Client Secret. You will find this value in your Bynder App.

    7.  Save

        Save record. That will update the authorization link for Bynder App.

    8.  Authorization

        Click the link to authorize the Bynder App. Maybe you have to login to Bynder. You will automatically
        redirected to the configured `Redirect Callback` URL in a new browser tab.

    9.  Copy, copy, copy

        Copy `access token`, `refresh token` and `expires` into your bynder storage record in the other browser tab.

    10. Set temporary folder

        `bynder2` can not write files to Bynder, so you have to use another folder for temporary files. Please set
        `Folder for manipulated and temporary images etc.` to something like `1:/_processed_/`.

    11. Save

        Click save. That will try to call the Bynder API the first time.

    12. Check

        If everything works you should now see the Bynder Status at the bottom of your new bynder storage record.
