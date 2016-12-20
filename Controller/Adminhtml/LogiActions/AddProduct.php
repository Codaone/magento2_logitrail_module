<?php

namespace Codaone\LogitrailModule\Controller\Adminhtml\LogiActions;

class AddProduct extends \Magento\Backend\App\Action
{
    protected $logitrail;
    protected $messageManager;

    public function __construct( \Codaone\LogitrailModule\Model\Logitrail $logitrail,
                                 \Magento\Backend\App\Action\Context $context)
    {
        parent::__construct($context);
        $this->logitrail = $logitrail;
        $this->messageManager = $context->getMessageManager();
    }

    /*
     *  Return logitrail form for checkout
     *
     *
     */
    public function execute()
    {
        $productId = $this->getRequest()->getParam('prid');
        $result = $this->logitrail->addProducts(array($productId));
        if ($result === true) {
            $this->messageManager->addSuccessMessage(__('Product successfully added to Logitrail'));

        } else {
            $this->messageManager->addErrorMessage(__('Error: Adding product failed: %1', $result));
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath($this->getUrl("catalog/product/edit", ['id'=>$productId]));
    }
}
