<?php

namespace Magestore\WebposVantiv\Controller\Index;

use Magento\Sales\Model\Order;
use Magento\Payment\Model\Method\AbstractMethod;

class Cancel extends \Magento\Framework\App\Action\Action
{
    protected $resultPageFactory;
    protected $customerSession;
    protected $checkoutSession;
    protected $cartManagement;
    protected $quoteRepository;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Api\CartManagementInterface $cartManagement)
    {
        $this->resultPageFactory = $pageFactory;
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->cartManagement = $cartManagement;
        $this->orderSender = $context->getObjectManager()->create('\\Magento\\Sales\\Model\\Order\\Email\\Sender\\OrderSender');
        $this->mercuryhosted = $context->getObjectManager()->create('\\Schogini\\Mercuryhosted\\Model\\Mercuryhosted');
    }

    public function execute()
    {
        $params = $this->getRequest()->getPost();
        if (!isset($params) || !isset($params['ReturnCode'])) {
            $this->messageManager->addError(__('Invalid transaction. Please try again.'));
            return $this->_redirect('webposvantiv/index/faild');
        }
        $errors = $params['ReturnMessage'] . '(' . $params['ReturnCode'] . ')';
        $message = 'Payment could not be completed! Reason: ' . '
' . $errors;
        $this->messageManager->addError($message);
        return $this->_redirect('webposvantiv/index/faild');
    }
}