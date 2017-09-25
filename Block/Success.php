<?php
/**
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *
 */

namespace Magestore\WebposVantiv\Block;

/**
 * Class Container
 * @package Magestore\WebposVantiv\Block
 */
class Success extends \Magento\Framework\View\Element\Template
{

    protected $emailSender;
    protected $orderVantiv;

    /**
     * Container constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Sales\Model\AdminOrder\EmailSender $emailSender,
        array $data = []
    ) {
        $this->emailSender = $emailSender;
        parent::__construct($context, $data);
    }

    public function initDataOrder($orderId){
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $orderRepository = $objectManager->create("Magestore\Webpos\Api\Sales\OrderRepositoryInterface");
        $order = $orderRepository->get($orderId);
        $this->emailSender->send($order);
        $this->orderVantiv= $order;
    }

    public function getDataOrderVantiv(){
        return $this->orderVantiv;
    }

    public function getUrl($route = '', $params = [])
    {
        return $this->_urlBuilder->getUrl($route, $params);
    }
}
