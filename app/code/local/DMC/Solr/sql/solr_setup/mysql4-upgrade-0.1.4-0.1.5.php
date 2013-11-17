<?php
$installer = $this;
$connection = $installer->getConnection();

$installer->startSetup();

$attribute_data = array(
        'dm_search_boosting' => array(
                'group' => 'General',
                'type' => 'int',
                'backend' => '',
                'frontend' => '',
                'label' => 'Elevate',
                'input' => 'text',
                'class' => '',
                'source' => '',
                'global' => true,
                'visible' => true,
                'required' => false,
                'user_defined' => true,
                'default' => 0,
                'searchable' => true,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'unique' => false )
);

foreach( $attribute_data as $key => $item )
{
    $installer->addAttribute( 'catalog_product', $key, $item );
}
$installer->endSetup();
