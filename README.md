<img src="https://cdn.checkout.com/img/checkout-logo-online-payments.jpg" alt="Checkout.com" width="380"/>

## Qisstpay.com Magento 2.4 Extension

## Installation
The easiest and recommended way to install the Checkout.com Magento 2 extension is to run the following commands in a terminal, from your Magento 2 root directory:

```bash
composer require qisst/plugin-magento-24
bin/magento setup:upgrade
rm -rf var/cache var/generation/ var/di
bin/magento setup:di:compile && php bin/magento cache:clean