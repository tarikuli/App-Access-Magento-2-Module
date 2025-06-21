<?php
declare(strict_types=1);

namespace Studio3Marketing\AppAccess\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Model\Session;

class AppCustomerLogin implements ObserverInterface
{
    protected $customerSession;

    public function __construct(
        Session $customerSession
    ) {
        $this->customerSession = $customerSession;
    }

    /**
     * Observer execute method to set UUID on customer after login if present in session.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();

        if($this->customerSession->getUuid() && $this->customerSession->isLoggedIn() ) {
            $customer->setUuid($this->customerSession->getUuid());
            $customer->save();
        }

    }
}
