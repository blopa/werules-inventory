<?php

namespace Werules\Inventory\Block\Adminhtml\Total;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogInventory\Api\StockStateInterface;

class Cost extends Template
{
    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var GetSalableQuantityDataBySkuInterface
     */
    protected $_getSalableQuantityDataBySku;

    /**
     * @param Template\Context $context
     * @param CollectionFactory $productCollectionFactory
     * @param GetSalableQuantityDataBySkuInterface $getSalableQuantityDataBySku
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        CollectionFactory $productCollectionFactory,
        GetSalableQuantityDataBySkuInterface $getSalableQuantityDataBySku,
        array $data = []
    ) {
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
        parent::__construct($context, $data);
    }

    /**
     * @return float
     */
    public function getTotalCost()
    {
        $totalCost = 0;
        $collection = $this->_productCollectionFactory->create();
        $collection->addAttributeToSelect(['cost', 'stock_status', 'sku']);
        $collection->addAttributeToFilter('stock_status', ['eq' => 93]);

        foreach ($collection as $product) {
            $salableQty = 0;
            $salableQuantityData = $this->_getSalableQuantityDataBySku->execute($product->getSku());
            foreach ($salableQuantityData as $stockData) {
                $salableQty += $stockData['qty'];
            }

            if ($salableQty > 0) {
                $totalCost += $product->getCost() * $salableQty;
            }
        }
        return $totalCost;
    }
}
