# Magento2 logitrail module

This is Logitrail's Magento 2.x shipping module. The module is maintained by Codaone.

## System requirements

The Logitrail shipping module for Magento was tested on and requires the following set of applications in order to fully work:

* Magento >= 2.0
* PHP version 5.6 or higher
* PHP cURL support

There is no guarantee that the module is fully functional in any other environment which does not fulfill the requirements.

## Installation

Prior to any change in your environment, it is strongly recommended to perform a full backup of the entire Magento installation.
It is also strongly recommended to do installation first in development environment, and only after that in production environment.

On the console when path is the magento folder
Inside the magento2 installation folder

1. Run `composer require codaone/magento2-logitrail-module`
2. Run `php bin/magento module:enable Codaone_LogitrailModule`
3. Run `php bin/magento setup:upgrade`
4. Run `php bin/magento setup:di:compile`
5. Clean Magento cache
6. Configure the module
7. Verify the shipping work

## Configuration

Configuration for the module can be found from standard location under *Store -> Configuration -> Sales-> Shipping Methods -> Logitrail*.

##### Test mode
If enabled, communication is pointed at logitrail test url, otherwise production url is used.

#### Automatic creation or update to Logitrail on product save
If enabled, when products are saved they will be automatically created to logitrail

#### Automatic shipment creation
If enabled, shipment is created when order is confirmed
