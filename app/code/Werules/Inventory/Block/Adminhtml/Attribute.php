<?php

namespace Werules\Inventory\Block\Adminhtml;

use Magento\Framework\View\Element\Template;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as EavAttribute;
use Magento\InventorySalesApi\Api\GetSalableQuantityDataBySkuInterface;

class Attribute extends Template
{
    protected $_productCollectionFactory;
    protected $_attributeCollection;
    protected $_getSalableQuantityDataBySku;

    public function __construct(
        Template\Context $context,
        CollectionFactory $productCollectionFactory,
        EavAttribute $attributeCollection,
        GetSalableQuantityDataBySkuInterface $getSalableQuantityDataBySku,
        array $data = []
    ) {
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_attributeCollection = $attributeCollection;
        $this->_getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
        parent::__construct($context, $data);
    }

    public function getFilterableAttributes()
    {
        $collection = $this->_attributeCollection->getCollection();
        $collection->addFieldToFilter('is_filterable', 1);
        return $collection;
    }

    public function getSelectedAttribute()
    {
        return $this->getRequest()->getParam('attribute_code');
    }

    public function getAttributeInventoryCosts()
    {
        $attributeCode = $this->getSelectedAttribute();
        if (!$attributeCode) {
            return [];
        }

        $attributeCosts = [];
        $collection = $this->_productCollectionFactory->create();
        $collection->addAttributeToSelect(['cost', 'stock_status', 'sku', $attributeCode]);
        $collection->addAttributeToFilter('stock_status', ['eq' => 93]);

        foreach ($collection as $product) {
            $salableQty = 0;
            $salableQuantityData = $this->_getSalableQuantityDataBySku->execute($product->getSku());
            foreach ($salableQuantityData as $stockData) {
                $salableQty += $stockData['qty'];
            }

            if ($salableQty > 0) {
                $attributeValue = $product->getAttributeText($attributeCode);
                if ($attributeValue) {
                    if (!isset($attributeCosts[$attributeValue])) {
                        $attributeCosts[$attributeValue] = 0;
                    }
                    $attributeCosts[$attributeValue] += $product->getCost() * $salableQty;
                }
            }
        }

        ksort($attributeCosts);
        return $attributeCosts;
    }
}
