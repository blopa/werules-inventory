<?php

namespace Werules\Inventory\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;

class Attribute extends Template
{
    protected $_attributeCollectionFactory;
    protected $_productCollectionFactory;

    public function __construct(
        Context $context,
        AttributeCollectionFactory $attributeCollectionFactory,
        ProductCollectionFactory $productCollectionFactory,
        array $data = []
    ) {
        $this->_attributeCollectionFactory = $attributeCollectionFactory;
        $this->_productCollectionFactory = $productCollectionFactory;
        parent::__construct($context, $data);
    }

    public function getFilterableAttributes()
    {
        $collection = $this->_attributeCollectionFactory->create();
        $collection->addIsFilterableFilter();
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

        $costs = [];
        $productCollection = $this->_productCollectionFactory->create();
        $productCollection->addAttributeToSelect([$attributeCode, 'cost', 'stock_status'])
            ->addAttributeToFilter('stock_status', 93)
            ->joinField(
                'qty',
                'cataloginventory_stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left'
            );

        foreach ($productCollection as $product) {
            $attributeValue = $product->getAttributeText($attributeCode);
            if ($attributeValue) {
                $stockQty = $product->getQty();
                if ($stockQty > 0 && $product->getCost()) {
                    if (!isset($costs[$attributeValue])) {
                        $costs[$attributeValue] = 0;
                    }
                    $costs[$attributeValue] += $product->getCost() * $stockQty;
                }
            }
        }
        ksort($costs);
        return $costs;
    }
}
