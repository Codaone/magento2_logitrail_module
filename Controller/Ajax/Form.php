<?php
/**
 * Copyright © 2016 Codaone Oy. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Codaone\LogitrailModule\Controller\Ajax;

class Form extends \Magento\Framework\App\Action\Action
{
    protected $logitrail;
    protected $_resolver;

    public function __construct(
        \Codaone\LogitrailModule\Model\Carrier\LogitrailCarrier $logitrail,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Locale\Resolver $resolver
    ) {
        parent::__construct($context);
        $this->logitrail = $logitrail;
        $this->_resolver = $resolver;
    }

    /*
     *  Return logitrail form for checkout
     */
    public function execute()
    {
        $locale = explode("_", $this->_resolver->getLocale())[0];

        echo $this->logitrail->getForm($locale);
    }
}
