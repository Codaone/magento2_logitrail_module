<?php
/**
 * Copyright © 2016 Codaone Oy. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Codaone\LogitrailModule\Setup;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class UpgradeData implements UpgradeDataInterface
{
    /** @var \Magento\Eav\Setup\EavSetupFactory $eavSetupFactory */
    protected $eavSetupFactory;

    /** @var \Magento\Framework\App\Config\ConfigResource\ConfigInterface $resourceConfig */
    protected $resourceConfig;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface  */
    protected $scopeConfig;

    /** @var \Magento\Framework\Logger\Monolog  */
    protected $logger;

    public function __construct(
        \Magento\Eav\Setup\EavSetupFactory $eavSetupFactory,
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface $resourceConfig,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Logger\Monolog $logger


    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->resourceConfig = $resourceConfig;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '2.0.2') < 0) {
            $webhookskey = $this->scopeConfig->getValue('carriers/logitrail/webhookskey', "default");
            if(empty($webhookskey)) {
                $webhookskey = md5(base64_encode(random_bytes(32)));
                $this->resourceConfig->saveConfig('carriers/logitrail/webhookskey', $webhookskey, "default", 0);
            }
        }

        if (version_compare($context->getVersion(), '2.0.1') < 0) {
            /** @var \Magento\Eav\Setup\EavSetup $eavSetup */
            $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

            $entityTypeId = $eavSetup->getEntityTypeId(Product::ENTITY);

            $attributes = array(
                array(
                    "attributeName"  => 'Barcode', // Name of the attribute
                    "attributeCode"  => 'barcode', // Code of the attribute
                    "attributeGroup" => 'General',         // Group to add the attribute to
                    "attributeSetIds" => array(4),          // Array with attribute set ID's to add this attribute to. (ID:4 is the Default Attribute Set)

                    // Configuration:
                    "data" => array(
                        'type'      => 'varchar',       // Attribute type
                        'input'     => 'text',          // Input type
                        'global'    => ScopedAttributeInterface::SCOPE_STORE,    // Attribute scope
                        'required'  => false,           // Is this attribute required?
                        'user_defined' => true,
                        'searchable' => false,
                        'filterable' => false,
                        'comparable' => false,
                        'visible_on_front' => false,
                        'unique' => false,
                        'used_in_product_listing' => false,
                        'label' => 'Barcode'
                    )
                ),
                array(
                    "attributeName"  => 'Width', // Name of the attribute
                    "attributeCode"  => 'width', // Code of the attribute
                    "attributeGroup" => 'General',         // Group to add the attribute to
                    "attributeSetIds" => array(4),          // Array with attribute set ID's to add this attribute to. (ID:4 is the Default Attribute Set)

                    // Configuration:
                    "data" => array(
                        'type'      => 'int',       // Attribute type
                        'input'     => 'text',          // Input type
                        'global'    => ScopedAttributeInterface::SCOPE_STORE,    // Attribute scope
                        'required'  => false,           // Is this attribute required?
                        'user_defined' => true,
                        'searchable' => false,
                        'filterable' => false,
                        'comparable' => false,
                        'visible_on_front' => false,
                        'unique' => false,
                        'used_in_product_listing' => false,
                        'label' => 'Width (mm)'
                    )
                ),
                array(
                    "attributeName"  => 'Height', // Name of the attribute
                    "attributeCode"  => 'height', // Code of the attribute
                    "attributeGroup" => 'General',         // Group to add the attribute to
                    "attributeSetIds" => array(4),          // Array with attribute set ID's to add this attribute to. (ID:4 is the Default Attribute Set)

                    // Configuration:
                    "data" => array(
                        'type'      => 'int',       // Attribute type
                        'input'     => 'text',          // Input type
                        'global'    => ScopedAttributeInterface::SCOPE_STORE,    // Attribute scope
                        'required'  => false,           // Is this attribute required?
                        'user_defined' => true,
                        'searchable' => false,
                        'filterable' => false,
                        'comparable' => false,
                        'visible_on_front' => false,
                        'unique' => false,
                        'used_in_product_listing' => false,
                        'label' => 'Height (mm)'
                    )
                ),
                array(
                    "attributeName"  => 'Length', // Name of the attribute
                    "attributeCode"  => 'length', // Code of the attribute
                    "attributeGroup" => 'General',         // Group to add the attribute to
                    "attributeSetIds" => array(4),          // Array with attribute set ID's to add this attribute to. (ID:4 is the Default Attribute Set)

                    // Configuration:
                    "data" => array(
                        'type'      => 'int',       // Attribute type
                        'input'     => 'text',          // Input type
                        'global'    => ScopedAttributeInterface::SCOPE_STORE,    // Attribute scope
                        'required'  => false,           // Is this attribute required?
                        'user_defined' => true,
                        'searchable' => false,
                        'filterable' => false,
                        'comparable' => false,
                        'visible_on_front' => false,
                        'unique' => false,
                        'used_in_product_listing' => false,
                        'label' => 'Length (mm)'
                    )
                ));

            foreach ($attributes as $attribute) {
                $eavSetup->addAttribute('catalog_product', $attribute['attributeCode'], $attribute['data']);

                foreach ($attribute['attributeSetIds'] as $attributeSetId) {
                    $eavSetup->addAttributeToGroup('catalog_product', $attributeSetId, $attribute['attributeGroup'], $attribute['attributeCode']);
                }
            }
        }
        $setup->endSetup();
    }
}
