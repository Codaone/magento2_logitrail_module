<?php
/**
 * Copyright © 2016 Codaone Oy. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Codaone\LogitrailModule\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;

class LogitrailCarrier extends \Magento\Shipping\Model\Carrier\AbstractCarrier implements
    \Magento\Shipping\Model\Carrier\CarrierInterface
{
    /**
     * @var string
     */
    protected $_code = 'logitrail';

    protected $productRepository;
    protected $logger;
    protected $quote;
    protected $logitrail;
    protected $session;
    protected $rateResultFactory;
    protected $rateMethodFactory;
    protected $addressRepository;

    /**
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Codaone\LogitrailModule\Model\Logitrail $logitrail,
        \Magento\Backend\Model\Session $session,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\ResourceModel\AddressRepository $addressRepository,
        array $data = []
    ) {
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->productRepository = $productRepository;
        $this->addressRepository = $addressRepository;
        $this->logger = $logger;
        $this->logitrail = $logitrail;
        $this->session = $session;
        $this->quote = $checkoutSession->getQuote();
        $this->scopeConfig = $scopeConfig;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * Get form block name
     *
     * @return string
     */
    public function getFormBlock()
    {
        return 'logitrail/logitrail';
    }

    /**
     * Get form for the checkout block
     *
     * @return string
     */
    public function getForm($lang = 'fi')
    {
        $this->session->setLogitrailShippingCost(0);

        $items = $this->quote->getAllItems();
        $api = $this->logitrail->getApi();
        //$api->setOrderId($this->quote->getId());
        $api->setOrderId($this->quote->getId());

        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($items as $item) {
            if (count($item->getChildren()) > 0) {
                continue;
            }
            $product = $this->productRepository->getById($item->getProduct()->getId());
            $api->addProduct(
                $product->getId(),
                $item->getName(),
                $item->getQty(),
                $product->getWeight() * 1000, // in grams
                $item->getBasePriceInclTax() - $item->getDiscountAmount(),
                $item->getTaxPercent(),
                $product->getBarcode(),
                $product->getWidth(), // width
                $product->getHeight(), // height
                $product->getLength() // length
            );
        }
        $address = $this->quote->getShippingAddress();
        $email = $address->getEmail();

        if ($address->getFirstname() == null && $address->getLastname() == null) {
            $customer = $this->quote->getCustomer();
            if ($this->quote->getCustomer()->getId() == null) {
                return false;
            }
            $addressId = $this->quote->getCustomer()->getDefaultShipping();
            $address = $this->addressRepository->getById($addressId);
            $email = $this->quote->getCustomerEmail();
        }
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
        $form = $api->getForm($lang);
        if ($this->isTestMode()) {
            $this->logger->info("Order form for Logitrail: $form");
        }
        return $form;
    }

    /**
     * Update shipping details with logitrail order id and shipping fee
     *
     * @return string
     */
    public function shippingDetails($logitrailId, $price)
    {
        $this->session->setLogitrailShippingCost($price);
        if ($this->isTestMode()) {
            $this->logger->info("Shipping details: Logitrail Order Id: $logitrailId, Shipping fee: $price");
        }
        $address = $this->quote->getShippingAddress();
        $address->setShippingAmount($price);
        $address->setBaseShippingAmount($price);
        $address->save();
        // Find if our shipping has been included.
        $rates = $address->collectShippingRates()
            ->getGroupedAllShippingRates();

        foreach ($rates as $carrier) {
            foreach ($carrier as $rate) {
                /**  @var \Magento\Quote\Model\Quote\Address\Rate $rate **/
                $rate->setPrice($price);
                $rate->save();
            }
        }
        $address->setCollectShippingRates(true);
        $this->quote->setData('logitrail_order_id', $logitrailId)->save();
    }

    /**
     * @return array
     */
    public function getAllowedMethods()
    {
        return array($this->_code=>$this->getConfigData('name'));
    }

    /**
     * Are we on test mode or not
     *
     * @return boolean
     */
    public function isTestMode()
    {
        return $this->getConfigData('testmode') == 1;
    }

    /**
     * @param RateRequest $request
     * @return bool|Result
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        // Save the address to the quote as we need it when retrieving the form
        $quoteItems = $request->getAllItems();
        if (count($quoteItems)>0) {
            /** @var \Magento\Quote\Model\Quote $quote */
            $quote = $quoteItems[0]->getQuote();

            $address = $quote->getShippingAddress();

            $address->setStreet($request->getDestStreet());
            $address->setPostcode($request->getDestPostcode());
            $address->setCity($request->getDestCity());
            $address->setCountryId($request->getDestCountryId());
            $address->save();
        }

        /** @var \Magento\Shipping\Model\Rate\Result $result */
        $result = $this->rateResultFactory->create();

        /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
        $method = $this->rateMethodFactory->create();

        $method->setData('carrier', $this->_code);
        $method->setData('carrier_title', "Logitrail");
        $method->setData('method_title', "Logitrail");
        $method->setData('method', 'logitrail'); //FIXME: method variable?

        $amount = $this->session->getLogitrailShippingCost();
        $method->setPrice($amount);
        $method->setData('cost', $amount);

        $result->append($method);

        return $result;
    }
}
