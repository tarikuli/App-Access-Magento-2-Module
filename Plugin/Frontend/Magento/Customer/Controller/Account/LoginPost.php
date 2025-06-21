<?php

declare(strict_types=1);

namespace Studio3Marketing\AppAccess\Plugin\Frontend\Magento\Customer\Controller\Account;

use Magento\Customer\Model\Session;
use Magento\Customer\Model\CustomerFactory;

class LoginPost
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    public function __construct(
        CustomerFactory $customerFactory,
        Session $customerSession
    ) {
        $this->customerFactory = $customerFactory;
        $this->session = $customerSession;
    }
    /**
     * Plugin afterExecute method to handle custom login logic after customer login post action.
     *
     * @param \Magento\Customer\Controller\Account\LoginPost $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterExecute(
        \Magento\Customer\Controller\Account\LoginPost $subject,
        $result
    ) {
        $referUrl = null;
        if($subject->getRequest()->getParam('referer')){
            $referUrl = base64_decode($subject->getRequest()->getParam('referer'));
        }

        if($referUrl) {
            $uuid = null;
            $customerId =null;
            $redirectUrl = "/";
            $referUrl = parse_url($referUrl);
            $referUrl = explode('/', $referUrl['path']);

            if(isset($referUrl[4])) {
                if($referUrl[4] == 'uuid') {
                    $uuid = $referUrl[5];
                }
            }

            if(isset($referUrl[6])) {
                if($referUrl[6] == 'customerId') {
                    $customerId = $referUrl[7];
                }
            }

            if(isset($referUrl[8])) {
                if($referUrl[8] == 'redirectUrl') {
                    $redirectUrl = $referUrl[9];
                }
            }

            if(!empty($this->session->getCustomerId()) && !empty($uuid) && !empty($customerId)){
                if($this->session->getCustomerId() == $customerId){
                    $customer = $this->customerFactory->create();
                    $customer = $customer->setWebsiteId(1)->load($this->session->getCustomerId());
                    if($customer){
                        $customer->setUuid($uuid);
                        $customer->save();
                        $result->setPath($redirectUrl);
                    }
                }
            }
        }
        return $result;
    }
}
