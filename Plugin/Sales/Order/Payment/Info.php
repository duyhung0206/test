<?php

namespace Magestore\WebposVantiv\Plugin\Sales\Order\Payment;

class Info
{
    /**
     * Getter for entire additional_information value or one of its element by key
     *
     * @param string $key
     * @return array|null|mixed
     */
    public function afterGetAdditionalInformation(\Magento\Sales\Model\Order\Payment\Info $subject, $result)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $coreRegistry = $objectManager->get('Magento\Framework\Registry');
        if($coreRegistry->registry('currrent_webpos_staff')->getId() && $subject->getMethodInstance()->getCode() == 'mercuryhosted'){
           if(array_key_exists('payment_response', $result)){
               $cardType = unserialize($result['payment_response'])['CardType'];
           }else{
               $cardType = 'N/A';
           }
            $cardType = trim($cardType)!=''?$cardType:'N/A';
            return array(
                'item1' => $result['method_title'],
                'item2' => "Credit Card Type: ".$cardType
            );
        }
        return $result;
    }

}