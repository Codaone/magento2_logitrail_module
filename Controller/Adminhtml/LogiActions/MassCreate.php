<?php
/**
 * Copyright © 2016 Codaone Oy. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Codaone\LogitrailModule\Controller\Adminhtml\LogiActions;

class MassCreate extends \Magento\Backend\App\Action
{
    protected $logitrail;
    protected $messageManager;
    protected $productBuilder;
    protected $filter;
    protected $collectionFactory;

    public function __construct(
        \Codaone\LogitrailModule\Model\Logitrail $logitrail,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Catalog\Controller\Adminhtml\Product\Builder $productBuilder,
        \Magento\Ui\Component\MassAction\Filter $filter,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory
    ) {
        parent::__construct($context);
        $this->logitrail = $logitrail;
        $this->messageManager = $context->getMessageManager();
        $this->productBuilder = $productBuilder;
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
    }

    /*
     *  Return logitrail form for checkout
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $productIds = array();
        foreach ($collection->getItems() as $product) {
            $productIds[] = $product->getId();
        }
        $result = $this->logitrail->addProducts($productIds);
        if ($result === true) {
            $this->messageManager->addSuccessMessage(__('Products successfully added to Logitrail'));
        } else {
            $this->messageManager->addErrorMessage(__('Error: Adding products failed: %1', $result));
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath($this->getUrl("catalog/product/index"));
    }
}
