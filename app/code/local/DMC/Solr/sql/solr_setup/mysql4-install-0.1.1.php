<?php
$installer = $this;
$installer->startSetup();
//$installer->run("INSERT INTO `index_process` (`indexer_code`, `status`, `started_at`, `ended_at`, `mode`) VALUES('solr_indexer', 'pending', '2012-02-02 10:59:35', '2012-02-02 11:03:39', 'real_time')");
#$installer->run("INSERT INTO `index_process` (`indexer_code`) VALUES('solr_indexer')");
$installer->endSetup();
