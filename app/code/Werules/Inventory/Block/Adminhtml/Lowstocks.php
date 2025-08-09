<?php

namespace Werules\Inventory\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\Catalog\Model\CategoryFactory;

class Lowstocks extends Template
{
    protected $_productCollectionFactory;
    protected $_stockState;
    protected $_categoryFactory;

    public function __construct(
        Context $context,
        CollectionFactory $productCollectionFactory,
        StockStateInterface $stockState,
        CategoryFactory $categoryFactory,
        array $data = []
    ) {
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_stockState = $stockState;
        $this->_categoryFactory = $categoryFactory;
        parent::__construct($context, $data);
    }

    public function getLowStockProducts()
    {
        $lowStockProducts = [];
        $collection = $this->_productCollectionFactory->create();
        $collection->addAttributeToSelect(['name', 'cost', 'category_ids']);
        $collection->joinTable(
            ['stock_item' => 'cataloginventory_stock_item'],
            'product_id=entity_id',
            ['qty', 'notify_stock_qty'],
            '{{table}}.stock_id=1',
            'left'
        );
        $collection->getSelect()->where('stock_item.qty < stock_item.notify_stock_qty');

        foreach ($collection as $product) {
            $currentStock = $product->getQty();
            $status = ($currentStock <= 2) ? 'Critical' : 'Low';
            $categoryNames = [];
            $categoryIds = $product->getCategoryIds();
            if ($categoryIds) {
                foreach ($categoryIds as $categoryId) {
                    $category = $this->_categoryFactory->create()->load($categoryId);
                    $categoryNames[] = $category->getName();
                }
            }

            $lowStockProducts[] = [
                'name' => $product->getName(),
                'category' => implode(', ', $categoryNames),
                'current_stock' => (int)$currentStock,
                'reorder_level' => (int)$product->getNotifyStockQty(),
                'unit_cost' => $product->getCost(),
                'status' => $status
            ];
        }

        return $lowStockProducts;
    }
}
