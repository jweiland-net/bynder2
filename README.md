# TYPO3 Extension `bynder2`

[![Packagist][packagist-logo-stable]][extension-packagist-url]
[![Latest Stable Version][extension-build-shield]][extension-ter-url]
[![Total Downloads][extension-downloads-badge]][extension-packagist-url]
[![Monthly Downloads][extension-monthly-downloads]][extension-packagist-url]
[![TYPO3 13.4][TYPO3-shield]][TYPO3-13-url]

bynder2 is a TYPO3 extension which registers a TYPO3 FAL driver for
Bynder https://www.bynder.com/

## 1 Features

* Browse and search your files in filelist module
* Support for image cropping
  * Image will be downloaded for cropping
* Use Bynder CDN URLs where possible to speed up rendering

## 2 Usage

### 2.1 Installation

#### Installation using Composer

The recommended way to install the extension is using Composer.

Run the following command within your Composer based TYPO3 project:

```
composer require jweiland/bynder2
```

#### Installation as extension from TYPO3 Extension Repository (TER)

Download and install `bynder2` with the extension manager module.

### 2.2 Minimal setup

1) Create new file storage on your root page (Page with UID 0)
2) Set credentials for Bynder
3) You should see, if authentication was successfully after you click save.

<!-- MARKDOWN LINKS & IMAGES -->

[extension-build-shield]: https://poser.pugx.org/jweiland/bynder2/v/stable.svg?style=for-the-badge
[extension-downloads-badge]: https://poser.pugx.org/jweiland/bynder2/d/total.svg?style=for-the-badge
[extension-monthly-downloads]: https://poser.pugx.org/jweiland/bynder2/d/monthly?style=for-the-badge
[extension-ter-url]: https://extensions.typo3.org/extension/bynder2/
[extension-packagist-url]: https://packagist.org/packages/jweiland/bynder2/
[packagist-logo-stable]: https://img.shields.io/badge/--grey.svg?style=for-the-badge&logo=packagist&logoColor=white
[TYPO3-13-url]: https://get.typo3.org/version/13
[TYPO3-shield]: https://img.shields.io/badge/TYPO3-13.4-green.svg?style=for-the-badge&logo=typo3
