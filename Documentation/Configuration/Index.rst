..  _configuration:

=============
Configuration
=============

..  toctree::
    :maxdepth: 2

    StorageRecord/Index
    Extension/Index

Content Security Policies
=========================

Starting with TYPO3 13, Content Security Policy (CSP) is enabled by default
for the TYPO3 backend. Therefore, a specific configuration is required;
otherwise, images and thumbnails will not be displayed or available
for download.

Since the URLs differ for each Bynder customer, you will need to provide the
following configuration with your respective URL values in your
TYPO3 SitePackage.

**EXT:my_extension/Configuration/ContentSecurityPolicies.php**

..  code-block:: php

    <?php

    declare(strict_types=1);

    use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
    use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Mutation;
    use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationCollection;
    use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationMode;
    use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
    use TYPO3\CMS\Core\Security\ContentSecurityPolicy\UriValue;
    use TYPO3\CMS\Core\Type\Map;

    return Map::fromEntries(
        [
            Scope::backend(),

            new MutationCollection(
                new Mutation(
                    MutationMode::Extend,
                    Directive::ImgSrc,
                    new UriValue('https://medienpool.example.com'),
                    new UriValue('https://sub-domain.cloudfront.net'),
                ),
                new Mutation(
                    MutationMode::Extend,
                    Directive::FrameSrc,
                    new UriValue('https://medienpool.example.com'),
                    new UriValue('https://sub-domain.cloudfront.net'),
                ),
            ),
        ],
    );
