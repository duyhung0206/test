<?php

namespace Magestore\WebposVantiv\Block;
use Braintree\Exception;

class Redirect extends \Magento\Framework\View\Element\Template
{
    protected $_quote = null;
    protected $_customerSession;
    protected $_checkoutSession;
    protected $_mercuryhosted;
    protected $_quoteF;
    protected $_faild;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $content,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Schogini\Mercuryhosted\Model\Mercuryhosted $mercuryhosted,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\App\Action\Context $actionContent,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        array $data = array())
    {
        parent::__construct($content, $data);
        $this->_customerSession = $customerSession;
        $this->_checkoutSession = $checkoutSession;
        $this->_objectManager = $actionContent->getObjectManager();
        $this->_mercuryhosted = $mercuryhosted;
        $this->_isScopePrivate = true;
        $this->_encryptor = $encryptor;
        $this->_quoteF= $quoteFactory;
        $this->_request = $actionContent->getRequest();
        $this->_faild = 0;
    }

    public function getQuote()
    {
        if (null === $this->_quote) {
            $this->_quote = $this->_checkoutSession->getQuote();
        }
        return $this->_quote;
    }

    public function initializePaymentRequest()
    {
        $this->_faild++;
        $quote = $this->getQuote();
        if($quote->getId() == null){
            $quoteId = $this->_request->getParam('quoteId');
            $this->_checkoutSession->setQuoteId($quoteId);
            $quote = $this->_quoteF->create()->load($quoteId);
            $this->_checkoutSession->setQuote($quote);
        }
        $spaeb016 = $this->_getSArr($quote);
        if (empty($spaeb016)) {
            return array();
        }

        $spc679c6 = array('MerchantID' => $spaeb016['merchant_id'],
            'Password' => $spaeb016['merchant_password'],
            'Invoice' => $spaeb016['invoice'],
            'TotalAmount' => $spaeb016['grandtotal_amount'],
            'TaxAmount' => '0.00',
            'Invoice' => $spaeb016['tax_amount'],
            'AVSAddress' => $spaeb016['address1'][0],
            'AVSZip' => $spaeb016['zip'],
            'TranType' => $spaeb016['transaction_type'],
            'CardHolderName' => trim($spaeb016['first_name'] . ' ' . $spaeb016['last_name']),
            'Frequency' => 'OneTime',
            'CustomerCode' => 'TEST' . time(),
            'Memo' => $this->_mercuryhosted->getMercuryMemo(),
            'ProcessCompleteUrl' => $this->getUrl('webposvantiv/index/success', array('_secure' => true)),
            'ReturnUrl' => $this->getUrl('webposvantiv/index/cancel', array('_secure' => true)),
            'DisplayStyle' => 'Custom', 'LogoUrl' => $spaeb016['logo_url'],
            'PageTitle' => $spaeb016['page_title'],
            'AVSFields' => $spaeb016['avscheck'], 'CVV' => 'on');
        if (empty($spc679c6['LogoUrl'])) {
            unset($spc679c6['LogoUrl']);
        }
        if (empty($spc679c6['PageTitle'])) {
            unset($spc679c6['PageTitle']);
        }
        if (empty($spc679c6['LogoUrl'])) {
            unset($spc679c6['LogoUrl']);
        }
        if (empty($spc679c6['PageTitle'])) {
            unset($spc679c6['PageTitle']);
        }
        $sp55df0a = '';
        if ($spaeb016['testmode'] == 'TRUE') {
            $sp55102f = 'https://hc.mercurycert.net/hcws/hcservice.asmx?wsdl';
            $spc679c6['OperatorID'] = 'Test';
            $spac789b = stream_context_create(array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true)));
            $sp99ec52 = array('trace' => true, 'stream_context' => $spac789b);
        } else {
            $sp55102f = 'https://hc.mercurypay.com/hcws/hcservice.asmx?wsdl';
            $spac789b = null;
            $sp99ec52 = array('trace' => true);
        }
        try {
            $spdd0fab = new \SoapClient($sp55102f, $sp99ec52);
            $sp3defb0 = $spdd0fab->__soapCall('InitializePayment', array(array('request' => $spc679c6)));
            $spaeb016['ResponseCode'] = $sp3defb0->InitializePaymentResult->ResponseCode;
            $spaeb016['PaymentID'] = $sp3defb0->InitializePaymentResult->PaymentID;
            $spaeb016['Message'] = $sp3defb0->InitializePaymentResult->Message;
            if ((int)$spaeb016['ResponseCode'] = 0) {
                echo 'Payment Gateway Error: ' . $spaeb016['Message'] . '(' . $spaeb016['ResponseCode'] . ')';
                echo 'InitializePayment REQUEST' . '
' . print_r($spdd0fab->__getlastRequest(), 1);
                echo 'InitializePayment RESPONSE' . '
' . print_r($spdd0fab->__getlastResponse(), 1);
                die;
            }
        } catch (\SoapFault $sp5e9e58) {
            if($this->_faild != 2){
                $this->initializePaymentRequest();
            }
            $spbd95c8 = '';
            if ($sp5e9e58->faultcode == 'HTTP') {
                $spbd95c8 = 'Invalid Credentials';
            } else {
                $spbd95c8 = '(' . $sp5e9e58->faultcode . ')' . $sp5e9e58->getMessage();
            }
            $sp55df0a = 'SOAP Fault: ' . $spbd95c8;
            echo 'SOAP Fault: ' . $spbd95c8;
            echo 'InitializePayment REQUEST' . '
' . print_r($spdd0fab->__getlastRequest(), 1);
            echo 'InitializePayment RESPONSE' . '
' . print_r($spdd0fab->__getlastResponse(), 1);
            die;
        } catch (\Exception $sp5e9e58) {
            if($this->_faild != 2){
                $this->initializePaymentRequest();
            }
            $spbd95c8 = $sp5e9e58->getMessage();
            if (empty($spbd95c8)) {
                $spbd95c8 = 'Unknown error';
            }
            $sp55df0a = 'SOAP Exception: ' . $spbd95c8;
            echo 'SOAP Exception: ' . $spbd95c8;
            echo 'InitializePayment REQUEST' . '
' . print_r($spdd0fab->__getlastRequest(), 1);
            echo 'InitializePayment RESPONSE' . '
' . print_r($spdd0fab->__getlastResponse(), 1);
            die;
        }
        return $spaeb016;
    }

    protected function _getSArr($quote)
    {
        $sp158636 = $quote->getBaseCurrencyCode();
        $isVirtual = $quote->getIsVirtual();
        $billingAddress = $quote->getBillingAddress();
        $sp0ec5eb = $isVirtual ? $quote->getBillingAddress() : $quote->getShippingAddress();
        if (!isset($sp0ec5eb) || empty($sp0ec5eb)) {
            $sp0ec5eb = $billingAddress;
        }
        $spa6481b = $this->_request->getParams();
        if ($quote->getCustomerIsGuest() || isset($spa6481b['guestemail']) && $spa6481b['guestemail'] != 'null') {
            $spc2e6ed = $spa6481b['guestemail'];
            $this->_checkoutSession->setGuestData(array('email' => $spc2e6ed, 'firstname' => $billingAddress->getFirstname(), 'lastname' => $billingAddress->getLastname()));
        } else {
            $spc2e6ed = $quote->getCustomer()->getEmail();
            $this->_checkoutSession->setGuestData(null);
        }
        $sp2a74e3 = $this->_mercuryhosted->getConfigData('payment_action');
        switch ($sp2a74e3) {
            case 'authorize_capture':
                $sp2a74e3 = 'Sale';
                break;
            default:
                $sp2a74e3 = 'PreAuth';
        }
        $spf235d7 = $this->_objectManager->create('\\Magento\\Sales\\Model\\Order')->getCollection()->setOrder('created_at', 'DESC')->setPageSize(1)->setCurPage(1);
        $sp156987 = $spf235d7->getFirstItem()->getIncrementId();
        $spaeb016 = array('merchant_id' => $this->_mercuryhosted->getConfigData('merchant_id'), 'merchant_password' => $this->_encryptor->decrypt($this->_mercuryhosted->getConfigData('merchant_password')), 'transaction_type' => $sp2a74e3, 'testmode' => $this->_mercuryhosted->getConfigData('test') ? 'TRUE' : 'FALSE', 'logo_url' => $this->_mercuryhosted->getConfigData('logo_url'), 'page_title' => $this->_mercuryhosted->getConfigData('page_title'), 'avscheck' => $this->_mercuryhosted->getConfigData('avscheck'), 'invoice' => $sp156987, 'currency_code' => $sp158636, 'first_name' => $billingAddress->getFirstname(), 'last_name' => $billingAddress->getLastname(), 'address1' => $billingAddress->getStreet(1), 'address2' => $billingAddress->getStreet(2), 'city' => $billingAddress->getCity(), 'state' => $billingAddress->getRegionCode(), 'country' => $billingAddress->getCountry(), 'zip' => $billingAddress->getPostcode(), 'telephone' => $billingAddress->getTelephone(), 's_first_name' => $sp0ec5eb->getFirstname(), 's_last_name' => $sp0ec5eb->getLastname(), 's_address1' => $sp0ec5eb->getStreet(1), 's_address2' => $sp0ec5eb->getStreet(2), 's_city' => $sp0ec5eb->getCity(), 's_state' => $sp0ec5eb->getRegionCode(), 's_country' => $sp0ec5eb->getCountry(), 's_zip' => $sp0ec5eb->getPostcode(), 's_telephone' => $sp0ec5eb->getTelephone(), 'email' => $spc2e6ed);
        $spdbad63 = 0;
        $sp3a580c = 0;
        $spf315dc = 0;
        $spcd8597 = 0;
        $sp84ed5d = 0;
        $sp2db865 = $quote->getAllItems();
        if ($sp2db865) {
            $sp84ed5d = 1;
            foreach ($sp2db865 as $sp0336f5) {
                if ($sp0336f5->getParentItem()) {
                    continue;
                }
                if ($sp0336f5->getQty() == '') {
                    $sp15c6b1 = 0;
                    if ($sp0336f5->getBaseTaxAmount() > 0) {
                        $sp15c6b1 = $sp0336f5->getBaseTaxAmount() / $sp0336f5->getQtyOrdered();
                    }
                    $sp52ab86 = $sp0336f5->getBaseDiscountAmount() / $sp0336f5->getQtyOrdered();
                    $spaeb016 = array_merge($spaeb016, array('item_name_' . $sp84ed5d => $sp0336f5->getName(), 'item_number_' . $sp84ed5d => $sp0336f5->getSku(), 'quantity_' . $sp84ed5d => sprintf('%d', $sp0336f5->getQtyOrdered()), 'individual_discount_' . $sp84ed5d => sprintf('%.2f', $sp52ab86), 'amount_' . $sp84ed5d => sprintf('%.2f', $sp0336f5->getBasePrice() - $sp52ab86 + $sp15c6b1), 'base_price_' . $sp84ed5d => sprintf('%.2f', $sp0336f5->getBasePrice() - $sp52ab86), 'individual_total_' . $sp84ed5d => sprintf('%.2f', ($sp0336f5->getBasePrice() - $sp52ab86 + $sp15c6b1) * $sp0336f5->getQtyOrdered()), 'tax_' . $sp84ed5d => sprintf('%.2f', $sp15c6b1)));
                    $spdbad63 += ($sp0336f5->getBasePrice() - $sp52ab86 + $sp15c6b1) * $sp0336f5->getQtyOrdered();
                    $spf315dc += ($sp0336f5->getBasePrice() - $sp52ab86) * $sp0336f5->getQtyOrdered();
                    $sp3a580c += $sp15c6b1 * $sp0336f5->getQtyOrdered();
                    $spcd8597 += $sp52ab86 * $sp0336f5->getQtyOrdered();
                } else {
                    $sp15c6b1 = 0;
                    $spa1ebff = $sp0336f5->getBaseDiscountAmount();
                    if ($sp0336f5->getBaseTaxAmount() > 0) {
                        $sp15c6b1 = $sp0336f5->getBaseTaxAmount() / $sp0336f5->getQty();
                    }
                    $sp52ab86 = $sp0336f5->getBaseDiscountAmount() / $sp0336f5->getQty();
                    $spaeb016 = array_merge($spaeb016, array('item_name_' . $sp84ed5d => $sp0336f5->getName(), 'item_number_' . $sp84ed5d => $sp0336f5->getSku(), 'quantity_' . $sp84ed5d => sprintf('%d', $sp0336f5->getQty()), 'individual_discount_' . $sp84ed5d => sprintf('%.2f', $sp52ab86), 'amount_' . $sp84ed5d => sprintf('%.2f', $sp0336f5->getBaseCalculationPrice() - $sp0336f5->getBaseDiscountAmount() + $sp15c6b1), 'base_price_' . $sp84ed5d => sprintf('%.2f', $sp0336f5->getBaseCalculationPrice() - $sp52ab86), 'individual_total_' . $sp84ed5d => sprintf('%.2f', ($sp0336f5->getBaseCalculationPrice() - $sp52ab86 + $sp15c6b1) * $sp0336f5->getQty()), 'tax_' . $sp84ed5d => sprintf('%.2f', $sp15c6b1)));
                    $spdbad63 += ($sp0336f5->getBaseCalculationPrice() - $sp52ab86 + $sp15c6b1) * $sp0336f5->getQty();
                    $spf315dc += ($sp0336f5->getBaseCalculationPrice() - $sp52ab86) * $sp0336f5->getQty();
                    $sp3a580c += $sp15c6b1 * $sp0336f5->getQtyOrdered();
                    $spcd8597 += $sp52ab86 * $sp0336f5->getQtyOrdered();
                }
                $sp84ed5d++;
            }
        } else {
            return array();
        }
        $sp8a7a98 = $sp0ec5eb->getShippingDescription();
        if (empty($sp8a7a98)) {
            $sp8a7a98 = $quote->getShippingDescription();
        }
        $spaeb016 = array_merge($spaeb016, array('item_name_' . $sp84ed5d => $sp8a7a98, 'quantity_' . $sp84ed5d => 1, 'amount_' . $sp84ed5d => sprintf('%.2f', $sp0ec5eb->getShippingAmount() + $sp0ec5eb->getShippingTaxAmount()), 'base_price_' . $sp84ed5d => sprintf('%.2f', $sp0ec5eb->getBaseShippingAmount()), 'tax_' . $sp84ed5d => sprintf('%.2f', $sp0ec5eb->getBaseShippingTaxAmount())));
        $sp84ed5d++;
        $spaeb016['shipping_amount'] = $sp0ec5eb->getShippingAmount() + $sp0ec5eb->getShippingTaxAmount();
        $spaeb016['shipping_amount_without_tax'] = $sp0ec5eb->getShippingAmount();
        if ($spaeb016['shipping_amount'] <= 0) {
            $spaeb016['shipping_amount'] = $sp0ec5eb->getShippingAmount() + $sp0ec5eb->getShippingTaxAmount();
            $spaeb016['shipping_amount_without_tax'] = $sp0ec5eb->getShippingAmount();
        }
        $spaeb016['shipping_amount_without_tax'] = sprintf('%.2f', $spaeb016['shipping_amount_without_tax']);
        $spaeb016['subtotal_amount'] = sprintf('%.2f', $spdbad63);
        $spaeb016['tax_amount'] = sprintf('%.2f', $sp3a580c);
        $spaeb016['discount_amount'] = sprintf('%.2f', $spcd8597);
        $spaeb016['grandtotal_base_amount'] = sprintf('%.2f', $spf315dc);
        $spaeb016['grandtotal_base_amount_with_shipping_no_tax'] = sprintf('%.2f', $spf315dc + $spaeb016['shipping_amount_without_tax']);
        $spaeb016['grandtotal_amount'] = sprintf('%.2f', $spaeb016['shipping_amount'] + $spaeb016['subtotal_amount']);
        return $spaeb016;
    }

    public function getMercuryhostedUrl()
    {
        $sp8d88aa = $this->_mercuryhosted->getConfigData('test');
        if ($sp8d88aa) {
            $sp71828c = 'https://hc.mercurycert.net/Checkout.aspx';
        } else {
            $sp71828c = 'https://hc.mercurypay.com/Checkout.aspx';
        }
        return $sp71828c;
    }
}

function print_r_level($data, $sp56c08c = 5)
{
    static $innerLevel = 1;
    static $tabLevel = 1;
    static $cache = array();
    $sp09b919 = __FUNCTION__;
    $spc12126 = gettype($data);
    $spf66e0a = str_repeat('    ', $spb17fe2);
    $spda310a = str_repeat('    ', $spb17fe2 - 1);
    $sp3414a1 = array('object', 'array');
    $sp29f0cd = '';
    if (in_array($spc12126, $sp3414a1)) {
        if ($spc12126 == 'object') {
            if (in_array($data, $sp7aa57b)) {
                return "\n{$spda310a}*RECURSION*\n";
            }
            $sp7aa57b[] = $data;
            $sp29f0cd = get_class($data) . ' ' . ucfirst($spc12126);
            $sp70140a = new \ReflectionObject($data);
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
                $spa027c2[$sp36329f] = $sp36dca8->getValue($data);
            }
        } elseif ($spc12126 == 'array') {
            $sp29f0cd = ucfirst($spc12126);
            $spa027c2 = $data;
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