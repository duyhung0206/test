<?php

/**
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *
 */

namespace Magestore\WebposVantiv\Model\Cart;

/**
 * Class Create
 * @package Magestore\Webpos\Model\Cart
 */
class Create extends \Magestore\Webpos\Model\Cart\Create
{
    const VANTIV_PAYMENT_ACTIVE = 'payment/mercuryhosted/active';
    const SPECIFICPAYMENT = 'webpos/payment/specificpayment';

    /**
     * @return mixed
     */
    public function getPayment()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $api = $this->_objectManager->create('Magento\Quote\Model\PaymentMethodManagement');
        $list = $api->getList($this->getQuote()->getId());

        /*add new code payment : mercuryhosted*/
        $this->allowPayments = array('cashforpos', 'codforpos', 'ccforpos', 'cp1forpos', 'cp2forpos',
            'paypal_direct', 'authorizenet_directpost', 'payflowpro', 'mercuryhosted');
        $this->ccPayments = array('authorizenet_directpost', 'payflowpro');
        $paymentList = array();
        if (count($list) > 0) {
            $paymentHelper = $this->_objectManager->create('Magestore\Webpos\Helper\Payment');
            foreach ($list as $data) {
                if (!in_array($data->getCode(), $this->allowPayments))
                    continue;
                $code = $data->getCode();
                $title = $data->getTitle();
                $ccTypes = 0;
                $useCvv = 0;
                if (in_array($data->getCode(), $this->ccPayments)) {
                    $ccTypes = 1;
                    $useCvv = $paymentHelper->useCvv($code);
                }

                $isDefault = ($code == $paymentHelper->getDefaultPaymentMethod()) ?
                    \Magestore\Webpos\Api\Data\Payment\PaymentInterface::YES :
                    \Magestore\Webpos\Api\Data\Payment\PaymentInterface::NO;
                $isReferenceNumber = $paymentHelper->isReferenceNumber($code) ? '1' : '0';
                $isPayLater = $paymentHelper->isPayLater($code) ? '1' : '0';

                $paymentModel = $this->_objectManager->create('Magestore\Webpos\Model\Payment\Payment');

                /*add icon and check config active/inactive payment vantiv*/
                if($code == 'mercuryhosted'){
                    $vantivActive = $this->_scopeConfig->getValue(self::VANTIV_PAYMENT_ACTIVE, $storeScope);
                    $allowInWebpos = explode(',',$this->_scopeConfig->getValue(self::SPECIFICPAYMENT, $storeScope));
                    if(!in_array($code, $allowInWebpos)){
                        continue;
                    }
                    if($vantivActive != 1){
                        continue;
                    }
                    $paymentModel->setCode($code);
                    $iconClass = 'icon-iconPOS-payment-vantiv';

                }else{
                    $iconClass = 'icon-iconPOS-payment-cp1forpos';
                }

                $paymentModel->setCode($code);
                $paymentModel->setIconClass($iconClass);
                $paymentModel->setTitle($title);
                $paymentModel->setInformation('');
                $paymentModel->setType(($ccTypes) ? $ccTypes : \Magestore\Webpos\Api\Data\Payment\PaymentInterface::NO);
                $paymentModel->setTypeId(($ccTypes) ? $ccTypes : \Magestore\Webpos\Api\Data\Payment\PaymentInterface::NO);
                $paymentModel->setIsDefault($isDefault);
                $paymentModel->setIsReferenceNumber($isReferenceNumber);
                $paymentModel->setIsPayLater($isPayLater);
                $paymentModel->setMultiable(0);
                $paymentModel->setUsecvv($useCvv);
                $paymentList[] = $paymentModel->getData();
            }
        }
        $data = array(
            'payments' => new \Magento\Framework\DataObject(array(
                'list' => $paymentList,
            ))
        );
        $this->_eventManager->dispatch(
            \Magestore\Webpos\Api\Data\Cart\CheckoutInterface::EVENT_WEBPOS_GET_PAYMENT_AFTER, $data);
        $paymentList = $data['payments']->getList();
        return $paymentList;
    }

}