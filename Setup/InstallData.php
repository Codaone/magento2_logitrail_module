<?php
/**
 * Copyright © 2016 Codaone Oy. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Codaone\LogitrailModule\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\InstallDataInterface;

class InstallData implements InstallDataInterface
{
    /** @var \Magento\Framework\App\Config\ConfigResource\ConfigInterface  $resourceConfig */
    private $resourceConfig;

    public function __construct(\Magento\Framework\App\Config\ConfigResource\ConfigInterface $resourceConfig)
    {
        $this->resourceConfig = $resourceConfig;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $webhookskey = md5(base64_encode(random_bytes(32)));
        $this->resourceConfig->saveConfig('carriers/logitrail/webhookskey', $webhookskey, "default", 0);
    }
}
