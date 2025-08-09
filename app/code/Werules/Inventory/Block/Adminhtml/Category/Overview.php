<?php

namespace Werules\Inventory\Block\Adminhtml\Category;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\CatalogInventory\Api\StockStateInterface;

class Overview extends Template
{
    protected $_categoryCollectionFactory;
    protected $_productCollectionFactory;
    protected $_stockState;

    public function __construct(
        Context $context,
        CategoryCollectionFactory $categoryCollectionFactory,
        ProductCollectionFactory $productCollectionFactory,
        StockStateInterface $stockState,
        array $data = []
    ) {
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_stockState = $stockState;
        parent::__construct($context, $data);
    }

    public function getCategoryInventoryCosts()
    {
        $categoryCosts = [];
        $categoryCollection = $this->_categoryCollectionFactory->create()
            ->addAttributeToSelect('name')
            ->addAttributeToFilter('level', 2); // Main categories

        foreach ($categoryCollection as $category) {
            $totalCost = 0;
            $productCollection = $this->_productCollectionFactory->create();
            $productCollection->addCategoryFilter($category)
                ->addAttributeToSelect(['cost', 'stock_status'])
                ->addAttributeToFilter('stock_status', 93);

            foreach ($productCollection as $product) {
                $stockQty = $this->_stockState->getStockQty($product->getId(), $product->getStore()->getWebsiteId());
                if ($stockQty > 0 && $product->getCost()) {
                    $totalCost += $product->getCost() * $stockQty;
                }
            }

            if ($totalCost > 0) {
                $categoryCosts[] = [
                    'name' => $category->getName(),
                    'cost' => $totalCost
                ];
            }
        }

        return $categoryCosts;
    }
}
