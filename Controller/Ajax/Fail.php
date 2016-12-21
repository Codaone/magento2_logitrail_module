<?php
/**
 * Copyright © 2016 Codaone Oy. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Codaone\LogitrailModule\Controller\Ajax;

class Fail extends \Magento\Framework\App\Action\Action
{
    protected $logitrail;

    public function __construct(
        \Codaone\LogitrailModule\Model\Carrier\LogitrailCarrier $logitrail,
        \Magento\Framework\App\Action\Context $context
    ) {
        parent::__construct($context);
        $this->logitrail = $logitrail;
    }

    /*
     *  Return logitrail form for checkout
     */
    public function execute()
    {
        //PLACEHOLDER ACTION; SHOULDNT EVER BE CALLED
    }
}
