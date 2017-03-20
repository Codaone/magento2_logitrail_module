<?php
/**
 * Copyright © 2016 Codaone Oy. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Codaone\LogitrailModule\Controller\Webhook;

class Update extends \Magento\Framework\App\Action\Action
{
    /** @var \Codaone\LogitrailModule\Model\Logitrail  */
    protected $logitrail;

    /** @var \Magento\Framework\Logger\Monolog  */
    protected $logger;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface  */
    protected $scopeConfig;

    /** @var \Magento\CatalogInventory\Api\StockRegistryInterface  */
    protected $stockRegistry;

    /** @var \Magento\Sales\Model\OrderRepository  */
    protected $orderRepository;

    /** @var \Magento\Sales\Model\Convert\OrderFactory  */
    protected $convertOrderFactory;

    /** @var \Magento\Sales\Model\Order\Shipment\TrackFactory  */
    protected $trackFactory;

    /** @var \Magento\Shipping\Model\ShipmentNotifier  */
    protected $shipmentNotifier;

    /** @var \Magento\Framework\DB\TransactionFactory  */
    protected $transactionFactory;

    public function __construct(
        \Codaone\LogitrailModule\Model\Logitrail $logitrail,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Logger\Monolog $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Sales\Model\Convert\OrderFactory $convertOrderFactory,
        \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory,
        \Magento\Shipping\Model\ShipmentNotifier $shipmentNotifier,
        \Magento\Framework\DB\TransactionFactory $transactionFactory
    ) {
        parent::__construct($context);
        $this->logitrail = $logitrail;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->stockRegistry = $stockRegistry;
        $this->orderRepository = $orderRepository;
        $this->convertOrderFactory = $convertOrderFactory;
        $this->trackFactory = $trackFactory;
        $this->shipmentNotifier = $shipmentNotifier;
        $this->transactionFactory = $transactionFactory;
    }

    /*
     *  Handle webservice request
     */
    public function execute()
    {
        /** @var \Magento\Framework\App\Request\Http $request */
        $request = $this->getRequest();

        /** @var \Zend\Http\Headers $header */
        $header = $request->getHeaders();

        /** @var bool $result */
        $result = false;
        $msg = "";

        if ($this->authenticate($header)) {
            $content = $request->getContent();

            $data = $this->logitrail->getApi()->processWebhookData($content);

            $this->logger->debug(json_encode($data, JSON_UNESCAPED_UNICODE));

            switch ($data["event_type"]) {
                case "product.inventory.change":
                    $result = $this->handleInventoryChange($data);
                    $msg = "success";
                    break;
                case "order.shipped":
                    $result = $this->handleOrderShipped($data);
                    $msg = "success";
                    break;
                default:
                    $result = true;
                    $msg = "Handling for event type {$data["event_type"]} not implemented";
                    break;
            }
        }

        if ($result) {
            header('HTTP/1.1 200 OK');
            echo($msg);
        } else {
            header('HTTP/1.0 400 Bad Request');
            echo('fail');
        }
        exit;
    }

    private function handleInventoryChange($data)
    {
        foreach ($data["payload"] as $product) {
            $stock = $this->stockRegistry->getStockItem($product["merchant_id"]);
            $stock->setQty($product["inventory"]["available"]);
        }
        return true;
    }

    private function handleOrderShipped($data)
    {
        /** @var bool $result */
        $result = false;

        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->orderRepository->get($data["merchant_id"]);

        //A shipment might have been created if autoship was set to true in module settings
        if (!$order->hasShipments() && $order->canShip()) {
            $qty = array();
            foreach ($order->getAllItems() as $item) {
                $qty[$item->getId()] = $item->getQtyOrdered();
            }

            /** @var \Magento\Sales\Model\Convert\Order $convertOrder */
            $convertOrder = $this->convertOrderFactory->create();

            /** @var \Magento\Sales\Model\Order\Shipment $shipment */
            $shipment = $convertOrder->toShipment($order);

            $shipment->register();
            $shipment->addComment(__("Tracking URL: " . str_replace('\\', '', $data['tracking_url'])));
            $track = $this->trackFactory->create();
            $track->addData(array(
                'carrier_code' => 'custom',
                'title'        => 'Logitrail',
                'number'       => $data['tracking_code']
            ));
            $shipment->addTrack($track);
            $shipment->getOrder()->setState(\Magento\Sales\Model\Order::STATE_COMPLETE);

            $transactionSave = $this->transactionFactory->create();
            $transactionSave->addObject($shipment)
                ->addObject($order)
                ->save();
            $this->shipmentNotifier->notify($shipment);

            $result = true;
        }
        return $result;
    }

    private function authenticate(\Zend\Http\Headers $headers)
    {
        $headerArray = $headers->toArray();

        if (!isset($headerArray["Authorization"])) {
            //No auth field in headers so can't authenticate the request. stop processing
            return false;
        }

        // The auth field from the header
        $authString = $headerArray["Authorization"];

        // Split the string into the auth type and auth string
        $authArray = explode(" ", $authString);

        // Check the type is "Basic"
        if ($authArray[0] != "Basic") {
            // Not something we are expecting so stop processing.
            return false;
        }

        // Decode the auth string and
        $auth = explode(':', base64_decode($authArray[1]));

        // Get the local values for authentication
        $webhooksname = $this->scopeConfig->getValue('carriers/logitrail/webhooksname', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $webhookskey = $this->scopeConfig->getValue('carriers/logitrail/webhookskey', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        // If the first value doesn't match return false
        if ($auth[0] != $webhooksname) {
            return false;
        }

        // If the second value doesn't match return false
        if ($auth[1] != $webhookskey) {
            return false;
        }

        return true;
    }
}
