<?php
/**
 * Copyright © 2016 Codaone Oy. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Codaone\LogitrailModule\Model;

class Logitrail extends \Magento\Framework\Model\AbstractModel
{
    protected $api = false;
    protected $carrier;
    protected $logger;
    protected $transactionFactory;
    protected $om;
    protected $storeManager;
    protected $taxCalculation;
    protected $productRepository;
    protected $convertOrderFactory;
    protected $trackFactory;
    protected $shipmentNotifier;
    protected $scopeConfig;
    protected $stockItem;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Tax\Model\Calculation $taxCalculation,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Sales\Model\Convert\OrderFactory $convertOrderFactory,
        \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory,
        \Magento\Shipping\Model\ShipmentNotifier $shipmentNotifier,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\CatalogInventory\Api\StockStateInterface $stockItem
    ) {
        $this->logger = $context->getLogger();
        $this->transactionFactory = $transactionFactory;
        $this->storeManager = $storeManager;
        $this->taxCalculation = $taxCalculation;
        $this->productRepository = $productRepository;
        $this->convertOrderFactory = $convertOrderFactory;
        $this->trackFactory = $trackFactory;
        $this->shipmentNotifier = $shipmentNotifier;
        $this->scopeConfig = $scopeConfig;
        $this->stockItem = $stockItem;

        $api = new \Codaone\LogitrailModule\Lib\ApiClient();
        $api->setMerchantId($this->_getConfig('merchantid'));
        $api->setSecretKey($this->_getConfig('secretkey'));
        $api->useTest($this->isTestMode() == 1 ? true : false);
        $this->api = $api;

        parent::__construct($context, $registry);
    }

    public function getApi()
    {
        return $this->api;
    }

    public function updateOrder($order)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $api = $this->getApi();
        $api->setResponseAsRaw(true);
        $logitrailId = $order->getLogitrailOrderId();
        $api->setOrderId($order->getId());
        $address = $order->getShippingAddress();
        $email = $address->getEmail() ? : $order->getCustomerEmail();
        $api->setCustomerInfo(
            $address->getFirstname(),
            $address->getLastname(),
            $address->getTelephone(),
            $email,
            join(' ', $address->getStreet()),
            $address->getPostcode(),
            $address->getCity(),
            $address->getCompany()
        );
        $api->updateOrder($logitrailId);
    }

    /*
    * Confirm order delivery to Logitrail
    *
    */
    public function confirmOrder($order)
    {
        /** @var $order \Magento\Sales\Model\Order */
        if ($order->getShippingMethod() == 'logitrail_logitrail') {
            $api = $this->getApi();
            $api->setResponseAsRaw(true);
            $logitrailId = $order->getLogitrailOrderId();
            $address = $order->getShippingAddress();
            $email = $address->getEmail() ? : $order->getCustomerEmail();
            // Update customerinfo to make sure they are correct
            // firstname, lastname, phone, email, address, postalCode, city
            $api->setCustomerInfo(
                $address->getFirstname(),
                $address->getLastname(),
                $address->getTelephone(),
                $email,
                join(' ', $address->getStreet()),
                $address->getPostcode(),
                $address->getCity(),
                $address->getCompany()
            );

            $api->setOrderId($order->getId());
            $api->updateOrder($logitrailId);
            $rawResponse = $api->confirmOrder($logitrailId);
            $response    = json_decode($rawResponse, true);
            if ($response) {
                if ($this->_getConfig('autoship') and $order->canShip()) {
                    $qty = array();
                    foreach ($order->getAllItems() as $item) {
                        $qty[$item->getId()] = $item->getQtyOrdered();
                    }

                    /** @var \Magento\Sales\Model\Convert\Order $convertOrder */
                    $convertOrder = $this->convertOrderFactory->create();

                    /** @var \Magento\Sales\Model\Order\Shipment $shipment */
                    $shipment = $convertOrder->toShipment($order);

                    $shipment->register();
                    $shipment->addComment(__("Tracking URL: " . str_replace('\\', '', $response['tracking_url'])));
                    $track = $this->trackFactory->create();
                    $track->addData(array(
                            'carrier_code' => 'custom',
                            'title'        => 'Logitrail',
                            'number'       => $response['tracking_code']
                        ));
                    $shipment->addTrack($track);
                    $shipment->getOrder()->setIsInProcess(true);

                    $transactionSave = $this->transactionFactory->create();
                    $transactionSave->addObject($shipment)
                                    ->addObject($order)
                                    ->save();
                    $this->shipmentNotifier->notify($shipment);
                } // if autoship
                $order->addStatusHistoryComment(sprintf(__("Logitrail Order Id: %s, Tracking number: %s, Tracking URL: %s"), $logitrailId, $response['tracking_code'], str_replace('\\', '', $response['tracking_url'])));
                if ($this->isTestMode()
                ) {
                    $this->logger->info("Confirmed order $logitrailId, response $rawResponse");
                }
            } else {  // confirmation failed
                $order->addStatusHistoryComment(__('Error: could not confirm order to Logitrail. Logitrail Order Id: ' . $logitrailId));
                $this->logger->error("Could not confirm order to Logitrail. Logitrail Order Id:  $logitrailId Response: $rawResponse");
                if ($this->isTestMode()) {
                    $this->logger->error("Error: could not confirm order to Logitrail. Logitrail Order Id:  $logitrailId Response: $rawResponse");
                }
            }
        }
    }
    /*
    *
    * Add product to Logitrail
    * $param array of product id's
    * @return mixed: true on success, string error message on failure.
    *
    */
    public function addProducts($productIds)
    {
        $api = $this->getApi();
        $api->setResponseAsRaw(true);
        $store = $this->storeManager->getStore();

        $request = $this->taxCalculation->getRateRequest(null, null, null, $store);

        $api->clearAll();


        foreach ($productIds as $productId) {
            $product = $this->productRepository->getById($productId);
            $taxClassId = $product->getTaxClassId();
            $taxPercent = $this->taxCalculation->getRate($request->setProductClassId($taxClassId));

            $api->addProduct(
                $product->getId(),
                $product->getName(),
                $this->stockItem->getStockQty($product->getId(), $product->getStore()->getWebsiteId()),
                $product->getWeight() * 1000, // in grams
                $product->getPrice(),
                $taxPercent,
                $product->getBarcode(),
                $product->getWidth(), // width
                $product->getHeight(), // height
                $product->getLength() // length
            );
        }
        $results = $api->createProducts();
        $success = true;
        $failed = array();
        $errorMessage = '';
        foreach ($results as $result) {
            $status = json_decode($result, true);
            if ($status === false) {
                // not correct json
                $success = true;
                $errorMessage = __('Error creating/updating products');
                $this->logger->error("Error: could not create/update product Logitrail. Response: " . print_r($results, true));
                if ($this->isTestMode()) {
                    $this->logger->error("Error: could not create/update product Logitrail. Response: " . print_r($results, true));
                }
                return $errorMessage;  // can not recover
            }
            if (isset($status['code']) && $status['code'] == 'access_denied') {
                // Access denied means its pointless to check the rest of the products, log the error and return with a sensible error message
                $errorMessage = __('Invalid credentials for Logitrail API. Reason: ') . $status['code'] . ", " . $status['message'];
                $this->logger->error("Error: could not create/update product(s) to Logitrail. Response: " . join(',', $results));
                return $errorMessage;
            }
            if (isset($status['success']) && $status['success'] != 1) {
                $success = false;
                $failed[] = $status['id'];
            }
        }
        if (!$success) {
            $errorMessage = __('Failed creating/updating product IDs: ') . join(', ', $failed);
            $this->logger->error("Error: could not create/update product Logitrail. Response: " . join(",", $results));
            if ($this->isTestMode()) {
                $this->logger->error("Error: could not create product to Logitrail. Response:  " . print_r($results, true));
            }
            return $errorMessage;
        }
        if ($this->isTestMode()) {
            $this->logger->info("Created/updated products to Logitrail. Product IDs: " . join(',', $productIds) . " Logitrail response " .   print_r($results, true));
        }
        return true;
    }

    protected function _getConfig($name)
    {
        return $this->scopeConfig->getValue('carriers/logitrail/' . $name, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    protected function isTestMode()
    {
        return $this->scopeConfig->getValue('carriers/logitrail/testmode', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }



}
