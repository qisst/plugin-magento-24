<img src="https://qisstpay.com/images/qisstpayLogoHd.png?2c17eccafe68477653388f509c7037bf" alt="Qisstpay.com" width="380"/>

## Qisstpay.com Magento 2.4 Extension

## Installation
The easiest and recommended way to install the Qisstpay.com Magento 2 extension is to run the following commands in a terminal, from your Magento 2 root directory:

```bash
composer require qisst/magento24:dev-main
php bin/magento setup:upgrade
rm -rf var/cache var/generation/ var/di
bin/magento setup:di:compile
php bin/magento cache:clean