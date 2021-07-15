# Rulemailer2

Magento 2 extension for [Rule](https://www.rule.se/). This extension allows Magento to send subscriber data to a customer's Rule account. Data includes: customer (email, first name, last name, date of birth and gender), cart info (product list, quantity, total price), and orders (product list, shipping address, product categories, total price). Also providing optional functionality for sending emails from Magento using [RULE transactional api](https://rule.se/apidoc/#transactions).

# Installation

## Install via composer
In the terminal `cd` to the root of your magento project. Then run:
```bash
cd ~/stack/apps/magento/htdocs

sudo COMPOSER_MEMORY_LIMIT=-1 composer require rulecom/rulemailer2 -v

sudo bin/magento-cli setup:upgrade

sudo bin/magento-cli setup:di:compile
```

# Configuration
In the admin part of your Magento app select `Stores/Configuration` section. Then select `RULE/Rulemailer` from the left menu.
In the form enter your Api key from the RULE appliction. If 'Use transactional' field set to 'Yes' all mail from the your Magento app are going to be sent via RULE transactional api.

# Note:
Tested up to Magento 2.4.2
