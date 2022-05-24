# TYPO3 Extension `fal_bynder`

![Build Status](https://github.com/jweiland-net/fal_bynder/workflows/CI/badge.svg)

fal_bynder is a TYPO3 extension which registers a TYPO3 FAL driver for
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
composer require jweiland/fal-bynder
```

#### Installation as extension from TYPO3 Extension Repository (TER)

Download and install `fal_bynder` with the extension manager module.

### 2.2 Minimal setup

1) Create new file storage on your root page (Page with UID 0)
2) Set credentials for Bynder
3) You should see, if authentication was successfully after you click save.
