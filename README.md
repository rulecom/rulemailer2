<a href="https://rule.io/">
    <img src="https://app.rule.io/img/logo-full.svg" alt="Rule logo" title="Rule" align="right" height="60" />
</a>

# Magento 2 Integration (Rulemailer2)

Magento 2 extension for [Rule](https://www.rule.se/). This extension allows Magento to send subscriber data to a customer's Rule account. Data includes: customer (email, first name, last name, date of birth and gender), cart info (product list, quantity, total price), and orders (product list, shipping address, product categories, total price). Also providing optional functionality for sending emails from Magento using the [RULE Transactional API](https://rule.se/apidoc/#transactions).

Note: Tested up to Magento 2.4.6

## Contents

- [Magento 2 Integration (Rulemailer2)](#magento-2-integration-rulemailer2)
  - [Contents](#contents)
  - [Installation](#installation)
    - [Install via composer](#install-via-composer)
  - [Configuration](#configuration)

## Installation

### Install via composer

Install like usual per [Adobe Commerce instructions](https://experienceleague.adobe.com/docs/commerce-operations/installation-guide/tutorials/extensions.html?lang=en).

In the terminal `cd` to the root of your Magento project. Then run:

```bash
bin/magento maintenance:enable

composer require rulecom/rulemailer2

bin/magento module:enable Rule_RuleMailer --clear-static-content

bin/magento setup:upgrade

bin/magento setup:di:compile

bin/magento cache:clean

bin/magento maintenance:disable
```

## Configuration

In the admin part of your Magento app select `Stores/Configuration` section. Then select `RULE/Rulemailer` from the left menu.
In the form enter your API key from the RULE application. If 'Use transactional' field set to 'Yes' all mail from the your Magento app are going to be sent via RULE Transactional API. Be sure to have a valid email set in "Store Email Adresses" in the Configuration.
