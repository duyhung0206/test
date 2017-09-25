<?php

/**
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *
 */

namespace Magestore\WebposVantiv\Model\Source\Adminhtml;

/**
 * class \Magestore\Webpos\Model\Source\Adminhtml\Payment
 *
 * Web POS Payment source model
 * Methods:
 *  getAllowPaymentMethods
 *  toOptionArray
 *
 * @category    Magestore
 * @package     Magestore_Webpos
 * @module      Webpos
 * @author      Magestore Developer
 */
class Payment extends \Magestore\Webpos\Model\Source\Adminhtml\Payment
{
    /**
     * @param \Magestore\Webpos\Helper\Payment $paymentHelper
     * @param \Magestore\Webpos\Model\Payment\Payment $paymentModel
     * @param \Magento\Payment\Model\Config $paymentConfigModel
     */
    public function __construct(
        \Magestore\Webpos\Helper\Payment $paymentHelper,
        \Magestore\Webpos\Model\Payment\PaymentFactory $paymentModel,
        \Magento\Payment\Model\Config $paymentConfigModel,
        \Magento\Payment\Helper\Data $corePaymentHelper
    ) {
        parent::__construct($paymentHelper, $paymentModel, $paymentConfigModel, $corePaymentHelper);
        $this->_allowPayments[] = 'mercuryhosted';
    }


    /*deny use payment vantiv offline*/
    public function addPosPayments(&$list, $collection, $ignores){
        $addedMethods = array();
        if(count($collection) > 0) {
            foreach ($collection as $item) {
                if(
                    in_array($item->getCode(), $ignores)
                    || !in_array($item->getCode(), $this->_allowPayments)
                    || !$this->_paymentHelper->isAllowOnWebPOS($item->getCode())
                    || $item->getCode() == 'mercuryhosted'
                ){
                    continue;
                }
                $isDefault = '0';
                if($item->getCode() == $this->_paymentHelper->getDefaultPaymentMethod()) {
                    $isDefault = '1';
                }
                $isReferenceNumber = '0';
                if ($item->getConfigData('use_reference_number')){
                    $isReferenceNumber = '1';
                }
                $isPayLater = 0;
                if ($item->getConfigData('pay_later')){
                    $isPayLater = '1';
                }
                $paymentModel = $this->_paymentModel->create();
                $paymentModel->setCode($item->getCode());
                $paymentModel->setTitle($item->getTitle());
                $paymentModel->setInformation('');
                $paymentModel->setType('0');
                $paymentModel->setIsDefault($isDefault);
                $paymentModel->setIsReferenceNumber($isReferenceNumber);
                $paymentModel->setIsPayLater($isPayLater);
                if(in_array($item->getCode(), $this->_ccPayments))
                    $paymentModel->setType('1');
                $list[] = $paymentModel->getData();
                $addedMethods[] = $item->getCode();
            }
        }
        return $addedMethods;
    }
}
