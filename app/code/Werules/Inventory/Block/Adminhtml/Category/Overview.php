<?php

namespace Werules\Inventory\Block\Adminhtml\Category;

use Magento\Framework\View\Element\Template;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\InventorySalesApi\Api\GetSalableQuantityDataBySkuInterface;

class Overview extends Template
{
    protected $_productCollectionFactory;
    protected $_categoryCollectionFactory;
    protected $_getSalableQuantityDataBySku;

    public function __construct(
        Template\Context $context,
        ProductCollectionFactory $productCollectionFactory,
        CategoryCollectionFactory $categoryCollectionFactory,
        GetSalableQuantityDataBySkuInterface $getSalableQuantityDataBySku,
        array $data = []
    ) {
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        $this->_getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
        parent::__construct($context, $data);
    }

    public function getCategoryInventoryCosts()
    {
        $categoryCosts = [];
        $mainCategories = $this->_categoryCollectionFactory->create()
            ->addAttributeToSelect('name')
            ->addAttributeToFilter('level', 2)
            ->addAttributeToFilter('is_active', 1);

        foreach ($mainCategories as $category) {
            $totalCost = 0;
            $products = $this->_productCollectionFactory->create()
                ->addAttributeToSelect(['cost', 'stock_status', 'sku'])
                ->addCategoryFilter($category)
                ->addAttributeToFilter('stock_status', ['eq' => 93]);

            foreach ($products as $product) {
                $salableQty = 0;
                $salableQuantityData = $this->_getSalableQuantityDataBySku->execute($product->getSku());
                foreach ($salableQuantityData as $stockData) {
                    $salableQty += $stockData['qty'];
                }

                if ($salableQty > 0) {
                    $totalCost += $product->getCost() * $salableQty;
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
