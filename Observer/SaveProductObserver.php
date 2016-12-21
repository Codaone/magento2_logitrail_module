<?php
/**
 * Copyright © 2016 Codaone Oy. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Codaone\LogitrailModule\Observer;

class SaveProductObserver implements \Magento\Framework\Event\ObserverInterface
{
    protected $logitrail;
    protected $scopeConfig;
    protected $messageManager;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Codaone\LogitrailModule\Model\Logitrail $logitrail,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->messageManager = $context->getMessageManager();
        $this->scopeConfig = $scopeConfig;
        $this->logitrail = $logitrail;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $autosaveProduct = $this->scopeConfig->getValue('carriers/logitrail/autosaveproduct', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if ($autosaveProduct == 0) {
            return; // do nothing
        }
        $product = $observer->getData('product');
        $result = $this->logitrail->addProducts(array($product->getId()));
        if ($result === true) {
            $this->messageManager->addSuccessMessage(__('Product successfully added/updated to Logitrail'));
        } else {
            $this->messageManager->addErrorMessage(__('Error: Adding product failed: %1', $result));
        }
    }
}
