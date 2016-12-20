<?php

namespace Codaone\LogitrailModule\Controller\Ajax;

class Form extends \Magento\Framework\App\Action\Action
{
    protected $logitrail;

    public function __construct( \Codaone\LogitrailModule\Model\Carrier\LogitrailCarrier $logitrail,
                                 \Magento\Framework\App\Action\Context $context)
    {
        parent::__construct($context);
        $this->logitrail = $logitrail;
    }

    /*
     *  Return logitrail form for checkout
     *
     *
     */
    public function execute()
    {
        echo $this->logitrail->getForm();
    }
}
