<?php
/**
 * Rewrite of CMS page model
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Model_Cms_Page extends Mage_Cms_Model_Page
{
    const ENTITY = 'cms_page';

    protected function _beforeSave()
    {
        parent::_beforeSave();
        Mage::getSingleton('index/indexer')->processEntityAction(
            $this, self::ENTITY, Mage_Index_Model_Event::TYPE_SAVE
        );
        return $this;
    }

    protected function _afterSave()
    {
        Mage::getSingleton('index/indexer')->processEntityAction(
            $this, self::ENTITY, Mage_Index_Model_Event::TYPE_SAVE
        );
        parent::_afterSave();
        return $this;
    }

    protected function _beforeDelete()
    {
        Mage::getSingleton('index/indexer')->logEvent(
            $this, self::ENTITY, Mage_Index_Model_Event::TYPE_DELETE
        );
        return parent::_beforeDelete();
    }

    protected function _afterDelete()
    {
        parent::_afterDelete();
        Mage::getSingleton('index/indexer')->indexEvents(
            self::ENTITY, Mage_Index_Model_Event::TYPE_DELETE
        );
    }

}
