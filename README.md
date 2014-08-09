# SiteConfig

## Introduction

SiteConfig provides a `Settings` tab in the admin section allowing users to set
site wide global configuration.

## Requirements

 * SilverStripe 3.2

## Installation

Installation can be done either by composer or by manually downloading the
release from Github.

### Via composer

`composer require "silverstripe/siteconfig:*"`

### Manually

 1.  Download the module from [the releases page](https://github.com/silverstripe/silverstripe-siteconfig/releases).
 2.  Extract the file (if you are on windows try 7-zip for extracting tar.gz files
 3.  Make sure the folder after being extracted is named 'siteconfig' 
 4.  Place this directory in your sites root directory. This is the one with framework and cms in it.

### Configuration

After installation, make sure you rebuild your database through `dev/build`.