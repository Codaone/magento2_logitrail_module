<?php


namespace Codaone\LogitrailModule\Helper;

/**
 * Codaone Logitrail helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Path to store config if extension is enabled
     *
     * @var string
     */
    const XML_PATH_ENABLED = 'codaone/basic/enabled';

    /**
     * Check if extension enabled
     *
     * @return string|null
     */
    public function isEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
