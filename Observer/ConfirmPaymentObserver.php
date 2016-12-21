<?php
/**
 * Copyright © 2016 Codaone Oy. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Codaone\LogitrailModule\Observer;

class ConfirmPaymentObserver implements \Magento\Framework\Event\ObserverInterface
{
    protected $logitrail;

    public function __construct(\Codaone\LogitrailModule\Model\Logitrail $logitrail)
    {
        $this->logitrail = $logitrail;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        //sales_order_payment_pay
        $payment = $observer->getData('payment');
        $this->logitrail->confirmOrder($payment->getOrder());
    }
}
