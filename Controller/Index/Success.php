<?php

namespace Magestore\WebposVantiv\Controller\Index;

use Magento\Sales\Model\Order;
use Magento\Payment\Model\Method\AbstractMethod;

class Success extends \Magento\Framework\App\Action\Action
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
        $spebc224 = $this->getRequest()->getPost();
        if (!isset($spebc224) || !isset($spebc224['ReturnCode'])) {
            $this->messageManager->addError(__('Invalid transaction. Please try again.'));
            return $this->_redirect('webposvantiv/index/faild');
            die;
        }
        $sp9abde6 = false;
        if ($spebc224['ReturnCode'] == 0) {
            $spc97a02 = $this->checkoutSession->getQuote();
            $sp2ff223 = $this->checkoutSession->getGuestData();
            $spc97a02->getPayment()->setMethod('mercuryhosted');
            $this->checkoutSession->setGuestData(null);
            if (!$this->customerSession->isLoggedIn() && isset($sp2ff223) && is_array($sp2ff223) && !empty($sp2ff223)) {
                $spc97a02->getCustomer()->setId(null);
                $spc97a02->getCustomer()->setEmail($sp2ff223['email']);
                $spc97a02->getCustomer()->setFirstname($sp2ff223['firstname']);
                $spc97a02->getCustomer()->setLastname($sp2ff223['lastname']);
            }
            $sp568321 = $this->cartManagement->submit($spc97a02);
            if ($sp568321) {
                try {
                    $spaeb016 = $this->mercuryhosted->verifyPayment($spebc224['PaymentID']);
                    $sp2a74e3 = $this->mercuryhosted->getConfigData('payment_action');
                    if ($sp2a74e3 == 'authorize_capture') {
                        $sp9dbd79 = $sp568321->getPayment();
                        $sp9dbd79->setAdditionalInformation('payment_type', \Magento\Payment\Model\Method\Cc::ACTION_AUTHORIZE_CAPTURE)->setAdditionalInformation('payment_response', serialize($spaeb016))->setStatus(AbstractMethod::STATUS_APPROVED)->setCcApproval($spaeb016['AuthCode'])->setLastTransId($spaeb016['AcqRefData'])->setCcTransId($spaeb016['AcqRefData'])->setCcAvsStatus($spaeb016['AVSResult'])->setCcCidStatus($spaeb016['CvvResult'])->setCcType($spaeb016['CardType'])->setAmount($spaeb016['Amount'])->setIsTransactionClosed(0)->setTransactionAdditionalInfo('real_transaction_id', $spaeb016['AcqRefData']);
                        $sp9dbd79->save();
                        $sp9dbd79->registerCaptureNotification($spaeb016['Amount']);
                        $sp9abde6 = true;
                    } else {
                        $sp9dbd79 = $sp568321->getPayment();
                        $sp9dbd79->setAdditionalInformation('payment_type', \Magento\Payment\Model\Method\Cc::ACTION_AUTHORIZE)->setAdditionalInformation('payment_response', serialize($spaeb016))->setStatus(AbstractMethod::STATUS_APPROVED)->setCcApproval($spaeb016['AuthCode'])->setLastTransId($spaeb016['AcqRefData'])->setCcTransId($spaeb016['AcqRefData'])->setCcAvsStatus($spaeb016['AVSResult'])->setCcCidStatus($spaeb016['CvvResult'])->setCcType($spaeb016['CardType'])->setAmount($spaeb016['Amount'])->setIsTransactionClosed(0)->setTransactionAdditionalInfo('real_transaction_id', $spaeb016['AcqRefData']);
                        $sp9dbd79->save();
                        $sp9dbd79->registerAuthorizationNotification($spaeb016['Amount']);
                        $sp9abde6 = false;
                    }
                    $spd3a48b = __('%1 (%2) at Mercury, Trans ID: %3 %4', $spaeb016['DisplayMessage'], $spaeb016['StatusMessage'], $spaeb016['AcqRefData'], $spaeb016['StatusMessage']);
                    $sp568321->addStatusHistoryComment($spd3a48b);
                    $sp568321->setCanSendNewEmailFlag($sp9abde6);
                    $sp568321->save();
                    $this->checkoutSession->setLastQuoteId($spc97a02->getId());
                    $this->checkoutSession->setLastSuccessQuoteId($spc97a02->getId());
                    $this->checkoutSession->setLastOrderId($sp568321->getId());
                    $this->checkoutSession->setLastRealOrderId($sp568321->getIncrementId());
                    $this->checkoutSession->setLastOrderStatus($sp568321->getStatus());
                    $this->messageManager->addSuccess(__('Order has been created successfully #'.$sp568321->getIncrementId()));
                    $resultPage = $this->resultPageFactory->create();
                    $resultPage->getLayout()->getBlock('webpos_vantiv_integration_success')->initDataOrder($sp568321->getId());
                    return $resultPage;
                } catch (\Exception $sp5e9e58) {
                    $spee74e8 = 'Payment could not be completed! Reason(1): ' . '
' . $sp5e9e58->getMessage();
                    $this->messageManager->addError(nl2br($spee74e8));
                    return $this->_redirect('webposvantiv/index/faild');
                }
            } else {
                $this->messageManager->addError(__('We could not create the order. Kindly try again'));
                return $this->_redirect('webposvantiv/index/faild');
            }
        } else {
            $spbd95c8 = isset($spebc224['ReturnMessage']) ? $spebc224['ReturnMessage'] : '';
            if (empty($spbd95c8)) {
                $spbd95c8 = __('Client cancelled transaction or there has been an error in the payment. Kindly try again');
            }
            $this->messageManager->addError($spbd95c8 . ' (' . $spebc224['ReturnCode'] . ')');
            return $this->_redirect('webposvantiv/index/faild');
        }
    }
}

function print_r_level($sp99d9eb, $sp56c08c = 5)
{
    static $innerLevel = 1;
    static $tabLevel = 1;
    static $cache = array();
    $sp09b919 = __FUNCTION__;
    $spc12126 = gettype($sp99d9eb);
    $spf66e0a = str_repeat('    ', $spb17fe2);
    $spda310a = str_repeat('    ', $spb17fe2 - 1);
    $sp3414a1 = array('object', 'array');
    $sp29f0cd = '';
    if (in_array($spc12126, $sp3414a1)) {
        if ($spc12126 == 'object') {
            if (in_array($sp99d9eb, $sp7aa57b)) {
                return "\n{$spda310a}*RECURSION*\n";
            }
            $sp7aa57b[] = $sp99d9eb;
            $sp29f0cd = get_class($sp99d9eb) . ' ' . ucfirst($spc12126);
            $sp70140a = new \ReflectionObject($sp99d9eb);
            $sp2c624a = $sp70140a->getProperties();
            $spa027c2 = array();
            foreach ($sp2c624a as $sp36dca8) {
                $sp36dca8->setAccessible(true);
                $sp36329f = $sp36dca8->getName();
                if ($sp36dca8->isProtected()) {
                    $sp36329f .= ':protected';
                } elseif ($sp36dca8->isPrivate()) {
                    $sp36329f .= ':' . $sp36dca8->class . ':private';
                }
                if ($sp36dca8->isStatic()) {
                    $sp36329f .= ':static';
                }
                $spa027c2[$sp36329f] = $sp36dca8->getValue($sp99d9eb);
            }
        } elseif ($spc12126 == 'array') {
            $sp29f0cd = ucfirst($spc12126);
            $spa027c2 = $sp99d9eb;
        }
        if ($sp56c08c == 0 || $sp21c92a < $sp56c08c) {
            $sp29f0cd .= "\n{$spda310a}(";
            foreach ($spa027c2 as $spa2655d => $spb728a1) {
                $sp29f0cd .= "\n{$spf66e0a}[{$spa2655d}] => ";
                $spb17fe2 = $spb17fe2 + 2;
                $sp21c92a++;
                $sp29f0cd .= in_array(gettype($spb728a1), $sp3414a1) ? $sp09b919($spb728a1, $sp56c08c) : $spb728a1;
                $spb17fe2 = $spb17fe2 - 2;
                $sp21c92a--;
            }
            $sp29f0cd .= "\n{$spda310a})\n";
        } else {
            $sp29f0cd .= "\n{$spda310a}*MAX LEVEL*\n";
        }
    }
    if ($sp21c92a == 1) {
        $sp7aa57b = array();
    }
    return $sp29f0cd;
}