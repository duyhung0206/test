<?php
/**
 *
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\WebposVantiv\Controller\Index;

class Faild extends \Magento\Framework\App\Action\Action
{
    protected $resultPageFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $content,
        \Magento\Framework\View\Result\PageFactory $pageFactory)
    {
        $this->resultPageFactory = $pageFactory;
        parent::__construct($content);
    }

    public function execute()
    {
        return $this->resultPageFactory->create();
    }
}