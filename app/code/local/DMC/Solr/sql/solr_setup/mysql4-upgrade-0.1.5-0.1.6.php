<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */

$installer = $this;

$installer->startSetup();

$installer->run("DROP TABLE IF EXISTS `{$installer->getTable('solr/landingpage')}`;");
$installer->run("
CREATE TABLE `{$installer->getTable('solr/landingpage')}` (
        `landingpage_id`          int(11)      unsigned NOT NULL auto_increment,
        `query_text`       varchar(255) NOT NULL default '',
        `redirect`          varchar(255) NOT NULL default '',
        `is_active`        int(1)       NOT NULL default 0,
        `store_id`      int(11)      unsigned NOT NULL,
        PRIMARY KEY (`landingpage_id`)
) ENGINE=InnoDb DEFAULT CHARSET=utf8;
");

$installer->run("DROP TABLE IF EXISTS `{$installer->getTable('solr/synonym')}`;");
$installer->run("
        CREATE TABLE `{$installer->getTable('solr/synonym')}` (
        `synonym_id` int(11)      unsigned NOT NULL auto_increment,
        `word`       varchar(255) NOT NULL default '',
        `synonyms`   text         NOT NULL default '',
        `store`      int(11)      unsigned NOT NULL,
        PRIMARY KEY (`synonym_id`)
) ENGINE=InnoDb DEFAULT CHARSET=utf8;
");

$installer->run("DROP TABLE IF EXISTS `{$installer->getTable('solr/stopword')}`;");
$installer->run("
        CREATE TABLE `{$installer->getTable('solr/stopword')}` (
        `stopword_id` int(11)      unsigned NOT NULL auto_increment,
        `word`        varchar(255) NOT NULL default '',
        `store`       int(11)      unsigned NOT NULL,
    PRIMARY KEY (`stopword_id`)
) ENGINE=InnoDb DEFAULT CHARSET=utf8;
");

$installer->run("DROP TABLE IF EXISTS `{$installer->getTable('solr/promotion')}`;");
$installer->run("
        CREATE TABLE `{$installer->getTable('solr/promotion')}` (
        `promotion_id` int(11)      unsigned NOT NULL auto_increment,
        `query_text`       varchar(255) NOT NULL default '',
        `snippet`   text         NOT NULL default '',
        `position`        varchar(255) NOT NULL default '',
        `store`      int(11)      unsigned NOT NULL,
        `is_active`        int(1)       NOT NULL default 0,
        PRIMARY KEY (`promotion_id`)
) ENGINE=InnoDb DEFAULT CHARSET=utf8;
");

$installer->endSetup();
