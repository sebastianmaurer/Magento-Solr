<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Block_Adminhtml_Partreindex extends Mage_Core_Block_Template
{
    protected $_inProcess = null;
    
    public function getStoreSelectOptions()
    {
        $storeModel = Mage::getSingleton('adminhtml/system_store');

        $url = Mage::getModel('adminhtml/url');

        $options = array();
        $options[0] = array(
            'label'    => Mage::helper('adminhtml')->__('All Stores'),
            'selected' => false,
            'style'    => 'background:#ccc; font-weight:bold;',
        );

        foreach ($storeModel->getWebsiteCollection() as $website) {
            $websiteShow = false;
            foreach ($storeModel->getGroupCollection() as $group) {
                if ($group->getWebsiteId() != $website->getId()) {
                    continue;
                }
                $groupShow = false;
                foreach ($storeModel->getStoreCollection() as $store) {
                    if ($store->getGroupId() != $group->getId()) {
                        continue;
                    }
                    if (!$websiteShow) {
                        $websiteShow = true;
                        $options['website_' . $website->getCode()] = array(
                            'is_group'  => true,
                            'is_close'  => false,
                            'label'    => $website->getName(),
                            'selected' => false,
                            'style'    => 'padding-left:16px; background:#DDD; font-weight:bold;',
                        );
                    }
                    if (!$groupShow) {
                        $groupShow = true;
                        $options['group_' . $group->getId() . '_open'] = array(
                            'is_group'  => true,
                            'is_close'  => false,
                            'label'     => $group->getName(),
                            'style'     => 'padding-left:32px;'
                        );
                    }
                    $options[$store->getId()] = array(
                        'label'    => $store->getName(),
                        'selected' => false,
                        'style'    => '',
                    );
                }
                if ($groupShow) {
                    $options['group_' . $group->getId() . '_close'] = array(
                        'is_group'  => true,
                        'is_close'  => true,
                    );
                }
            }
            if ($websiteShow) {
                $options['group_' . $website->getId() . '_close'] = array(
                    'is_group'  => true,
                    'is_close'  => true,
                );
            }
        }
        return $options;
    }
    
    public function getSolrPartReindexUrl()
    {
        return $this->getUrl('solr/adminhtml_index/partreindex');
    }
    
    public function solrIndexingIsInProcess()
    {
        if (is_null($this->_inProcess)) {
            $indexProcess = Mage::getSingleton('index/indexer')->getProcessByCode("solr_indexer");
            $this->_inProcess = ($indexProcess->getStatus() == Mage_Index_Model_Process::STATUS_RUNNING);
        }
        return $this->_inProcess;
    }
    
}
