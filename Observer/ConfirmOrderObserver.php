<?php
/**
 * Copyright © 2016 Codaone Oy. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Codaone\LogitrailModule\Observer;

use Magento\Usps\Model\Source\Machinable;

class ConfirmOrderObserver implements \Magento\Framework\Event\ObserverInterface
{
    protected $quoteRepository;
    protected $logitrail;

    public function __construct(
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Codaone\LogitrailModule\Model\Logitrail $logitrail
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->logitrail = $logitrail;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getData('order');
        $quoteId = $order->getQuoteId();

        $quote = $this->quoteRepository->get($quoteId);

        $logitrailOrderId = $quote->getLogitrailOrderId();
        $order->setLogitrailOrderId($logitrailOrderId);
        $order->save();

        $this->logitrail->updateOrder($order);
    }
}
