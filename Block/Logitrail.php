<?php
/**
 * Copyright © 2016 Codaone Oy. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Codaone\LogitrailModule\Block;

/**
 * Sales order view block
 */
class Logitrail extends \Magento\Framework\View\Element\Template
{
    /** @var \Codaone\LogitrailModule\Model\Carrier\LogitrailCarrier  */
    protected $logitrailCarrier;

    /** @var \Magento\Store\Model\StoreManagerInterface  */
    protected $_storeManager;

    /** @var \Magento\Directory\Model\CurrencyFactory  */
    protected $_currencyFactory;
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Magento\Payment\Helper\Data $paymentHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Codaone\LogitrailModule\Model\Carrier\LogitrailCarrier $logitrailCarrier,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        array $data = []
    ) {
        $this->logitrailCarrier = $logitrailCarrier;
        $this->_storeManager = $context->getStoreManager();
        $this->_currencyFactory = $currencyFactory;
        parent::__construct($context, $data);
    }

    public function getForm()
    {
        return $this->logitrailCarrier->getForm();
    }

    public function isTestMode()
    {
        return $this->logitrailCarrier->isTestMode();
    }

    public function getCurrencySign()
    {
        $currencyCode = $this->_storeManager->getStore()->getCurrentCurrencyCode();
        /** @var \Magento\Directory\Model\Currency $currency **/
        $currency = $this->_currencyFactory->create()->load($currencyCode);
        return $currency;
    }
}
