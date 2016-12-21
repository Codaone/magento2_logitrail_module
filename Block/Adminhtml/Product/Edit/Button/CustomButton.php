<?php
/**
 * Copyright © 2016 Codaone Oy. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Codaone\LogitrailModule\Block\Adminhtml\Product\Edit\Button;

use Magento\Catalog\Block\Adminhtml\Product\Edit\Button\Generic;

class CustomButton extends Generic
{
    public function __construct(\Magento\Framework\View\Element\UiComponent\Context $context, \Magento\Framework\Registry $registry)
    {
        parent::__construct($context, $registry);
    }

    public function getButtonData()
    {
        $product = $this->registry->registry('product');
        if (!$product->getId()) {
            return false;
        }
        return [
            'label' => _('Create/update product to Logitrail'),
            'on_click' => 'setLocation(\'' . $this->getUrl('logitrail/logiactions/addproduct', array('prid' => (int)$product->getId())) . "')",
            'sort_order' => 100
        ];
    }
}
