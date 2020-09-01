## ElasticSuite TargetRules

This module is a plugin for [ElasticSuite](https://github.com/Smile-SA/elasticsuite).

It allows to manage Magento2 Enterprise Edition Target Rules via Elasticsearch queries.

### Benefits

- Since Magento EE 2.1 and the Staging appearance, TargetRules are reported as broken : https://github.com/magento/magento2/issues/7354

Using our module will make them work again since we do not rely on the Database tables for retrieving products matching a rule.

- Performances : Magento will basically generate many rows in the association table handling relation between products and rules (which rule should be used for a given product, and which product will match conditions of a rule).

This could lead to massive performances issues when you have a large catalog.

We overcome this problem by [percolating](https://www.elastic.co/guide/en/elasticsearch/reference/2.3/search-percolate.html) the rules condition as Elasticsearch queries. This allow to find quickly which product is matching a given rule by just testing the product with the percolator.

Once the rules matching a product are retrieved by the Percolator, we process converting on the fly the TargetRule conditions to an Elasticsearch query, allowing us to match quickly the products that can be displayed by the rule, without even using the Database for complex filtering.

### Compatibility Matrix

Since Magento did change the way they store rules (from serialized string to json) between 2.1 and 2.2, please ensure to use the proper version of the module for your Magento version :

Magento Version         | Version of this module to use
------------------------|------------------------------------------------------------------------
Magento 2.0.* EE        | NOT SUPPORTED
Magento 2.1.* EE        | [Latest 1.1.x release](https://github.com/Smile-SA/magento2-module-elasticsuite-targetrule/releases/tag/1.1.0)
Magento 2.2.* EE        | [Latest 1.2.x release](https://github.com/Smile-SA/magento2-module-elasticsuite-targetrule/releases/tag/1.2.0)
Magento 2.3.* EE        | [Latest 1.4.x release](https://github.com/Smile-SA/magento2-module-elasticsuite-targetrule/releases/tag/1.4.1)

### Requirements

The module requires :

- [ElasticSuite](https://github.com/Smile-SA/elasticsuite) > 2.3.*

- Magento2 Enterprise Edition (since the TargetRules are only available with the EE Edition)

### How to use

1. Install the module via Composer :

``` composer require smile/module-elasticsuite-targetrule ```

2. Enable it

``` bin/magento module:enable Smile_ElasticsuiteTargetRule ```

3. Install the module and rebuild the DI cache

``` bin/magento setup:upgrade ```

4. Process a full reindex of catalogsearch index to reindex the Percolator data

``` bin/magento index:reindex catalogsearch_fulltext ```

