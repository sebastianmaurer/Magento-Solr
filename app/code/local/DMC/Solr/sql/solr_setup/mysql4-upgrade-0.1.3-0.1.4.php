<?php

$installer = $this;

$installer->startSetup();

$installer->run("ALTER TABLE catalog_eav_attribute ADD is_filterableBySolr smallint(5) unsigned NOT NULL DEFAULT 0");

$installer->endSetup();
