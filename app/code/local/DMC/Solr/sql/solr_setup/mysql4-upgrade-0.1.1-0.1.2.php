<?php
$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$pageTable = $installer->getTable('cms/page');

$installer->getConnection()->addColumn($pageTable, 'solr_ignore',
    "INT NOT NULL DEFAULT 0");

$installer->endSetup();
