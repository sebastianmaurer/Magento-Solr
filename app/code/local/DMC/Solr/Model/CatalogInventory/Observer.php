<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Model_CatalogInventory_Observer extends Mage_CatalogInventory_Model_Observer
{
     /**
     * Refresh stock index for specific stock items after succesful order placement
     *
     * @param $observer
     */
    public function reindexQuoteInventory($observer)
    {
        $quote = $observer->getEvent()->getQuote();
        $productIds = array();
        $items = $quote->getAllItems();
        foreach ($items as $item) {
            $productIds[$item->getProductId()] = $item->getProductId();
            $children   = $item->getChildrenItems();
            if ($children) {
                foreach ($children as $childItem) {
                    $productIds[$childItem->getProductId()] = $childItem->getProductId();
                }
            }
        }
        Mage::getResourceSingleton('cataloginventory/indexer_stock')->reindexProducts($productIds);
        $productIds = array();
        foreach ($this->_itemsForReindex as $item) {
            $item->save();
            $productIds[] = $item->getProductId();
        }
        Mage::getResourceSingleton('catalog/product_indexer_price')->reindexProductIds($productIds);

        if ((int)Mage::getStoreConfig('solr/indexer/product_update')) {
            Mage::helper('solr')->reindexProductIds($productIds);
        }
        return $this;
    }
}
?>
