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
     * @var StockStateInterface
     */
    protected $stockState;

    /**
     * @param Context $context
     * @param CollectionFactory $productCollectionFactory
     * @param StockStateInterface $stockState
     * @param array $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $productCollectionFactory,
        StockStateInterface $stockState,
        array $data = []
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->stockState = $stockState;
        parent::__construct($context, $data);
    }

    /**
     * @return float
     */
    public function getTotalCost()
    {
        $totalCost = 0;
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect(['name', 'price', 'cost']);

        foreach ($collection as $product) {
            $stockQty = $this->stockState->getStockQty($product->getId(), $product->getStore()->getWebsiteId());
            if ($stockQty > 0) {
                $totalCost += $product->getCost() * $stockQty;
            }
        }

        return $totalCost;
    }
}
