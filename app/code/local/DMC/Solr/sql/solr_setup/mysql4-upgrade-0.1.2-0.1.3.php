<?php
$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$pageTable = $installer->getTable('cms/page');

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->run("
    UPDATE `{$pageTable}` SET `solr_ignore` = 1 WHERE `identifier` = 'no-route';
");

$installer->endSetup();
