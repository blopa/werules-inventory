<?php

namespace Werules\Inventory\Block\Adminhtml;

use Magento\Framework\View\Element\Template;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\CategoryFactory;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;

class Lowstocks extends Template
{
    protected $_productCollectionFactory;
    protected $_categoryFactory;
    protected $_getSalableQuantityDataBySku;

    public function __construct(
        Template\Context $context,
        CollectionFactory $productCollectionFactory,
        CategoryFactory $categoryFactory,
        GetSalableQuantityDataBySku $getSalableQuantityDataBySku,
        array $data = []
    ) {
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_categoryFactory = $categoryFactory;
        $this->_getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
        parent::__construct($context, $data);
    }

    public function getLowStockProducts()
    {
        $lowStockProducts = [];
        $collection = $this->_productCollectionFactory->create();
        $collection->addAttributeToSelect(['name', 'cost', 'category_ids', 'sku']);

        foreach ($collection as $product) {
            $salableQty = 0;
            $salableQuantityData = $this->_getSalableQuantityDataBySku->execute($product->getSku());
            foreach ($salableQuantityData as $stockData) {
                $salableQty += $stockData['qty'];
            }

            if ($salableQty > 0) {
                $status = $this->getStockStatus($salableQty);
                if ($status !== 'In Stock') {
                    $lowStockProducts[] = [
                        'name' => $product->getName(),
                        'categories' => $this->getProductCategories($product->getCategoryIds()),
                        'current_stock' => $salableQty,
                        'reorder_level' => 'N/A',
                        'unit_cost' => $product->getCost(),
                        'status' => $status
                    ];
                }
            }
        }

        return $lowStockProducts;
    }

    public function getProductCategories($categoryIds)
    {
        $categoryNames = [];
        foreach ($categoryIds as $categoryId) {
            $category = $this->_categoryFactory->create()->load($categoryId);
            $categoryNames[] = $category->getName();
        }
        return implode(', ', $categoryNames);
    }

    public function getStockStatus($currentStock)
    {
        if ($currentStock >= 1 && $currentStock <= 2) {
            return 'Critical';
        }

        if ($currentStock >= 3 && $currentStock <= 5) {
            return 'Low';
        }

        return 'In Stock';
    }
}
