<?php

namespace Studio3Marketing\AppAccess\Controller\Account;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Math\Random;
use Magento\Customer\Model\AccountManagement;
use Magento\Framework\UrlInterface;

class Login extends Action
{
    /**
     * @var UrlInterface
     */
    protected $urlInterface;

    /**
     * @var Random
     */
    private Random $mathRandom;

    /**
     * @var AccountManagement
     */
    protected $accountManagement;
    protected $resultFactory;
    protected $session;
    protected $customerRepository;
    protected $authentication;
    protected $accountManagementInterface;
    protected $resultJsonFactory;
    protected $resultRedirectFactory;

    public function __construct(
        Context $context,
        ResultFactory $resultFactory,
        Session $session,
        CustomerRepositoryInterface $customerRepository,
        AccountManagementInterface $accountManagementInterface,
        AccountManagement $accountManagement,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
        UrlInterface $urlInterface,
        Random $mathRandom,
        JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->resultFactory = $resultFactory;
        $this->session = $session;
        $this->customerRepository = $customerRepository;
        $this->accountManagementInterface = $accountManagementInterface;
        $this->accountManagement = $accountManagement;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->urlInterface = $urlInterface;
        $this->mathRandom = $mathRandom;
        $this->resultJsonFactory = $resultJsonFactory;

    }

    /**
     * Main controller action for handling app login and redirection logic.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $customerId = (int) $this->getRequest()->getParam('id');
        $resetToken = $this->getRequest()->getParam('token');
        $uuid = $this->getRequest()->getParam('uuid');
        $redirectUrl = $this->getRequest()->getParam('redurl');
        $forgotUrl = $this->_url->getUrl('customer/account/forgotpassword');
        $this->session->setUuid(null);
        if($uuid) {

            $urlParams = [
                'token' => null,
                'uuid' => $uuid,
                'customerId' => $customerId,
                'redirectUrl' => base64_decode($redirectUrl)
            ];

            $url = $this->urlInterface->getUrl(base64_decode($redirectUrl));
            $resultRedirect = $this->resultRedirectFactory->create();

            $this->session->setUuid($uuid);

            if($this->session->isLoggedIn() && $uuid) {
                if($this->session->getCustomer()->getId() == $customerId) {
//                    $this->jdbg(__LINE__, "If UUID exist and Customer already login ");
                    $resultRedirect->setUrl($url);
                    return $resultRedirect;
                }
            }

            $login_url = $this->urlInterface
                ->getUrl('customer/account/login',
                    array('referer' => base64_encode($url))
                );
            $resultRedirect->setUrl($login_url);
//            $this->jdbg(__LINE__, "If UUID exist and ". $url);
            return $resultRedirect;
        }

        try {
            $resultJson = $this->resultJsonFactory->create();
            $customer = $this->customerRepository->getById($customerId);
            if($resetToken && $customer) {
                if($this->accountManagementInterface->validateResetPasswordLinkToken($customerId, $resetToken)) {
                    //Set Customer login
                    $this->session->setCustomerDataAsLoggedIn($customer);
                    $this->session->regenerateId();

                    if($redirectUrl && $this->session->isLoggedIn()) {
//                        $this->jdbg(__LINE__, "If UUID not exist and redirectUrl exist and Customer already login ");
                        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath(base64_decode($redirectUrl));
                    } else {
//                        $this->jdbg(__LINE__, "If UUID not exist and redirectUrl not exist and Customer not login ");
                        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('/');
                    }
                }
            }
        } catch (NoSuchEntityException $e) {
            $data = ["url" => $forgotUrl, "message" => $e->getMessage(), "status" => 400];
            return $resultJson->setData($data);
        } catch (LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
            $data = ["url" => $forgotUrl, "message" => $e->getMessage(), "status" => 400];
            return $resultJson->setData($data);
            #return $this->resultRedirectFactory->create()->setUrl($forgotUrl);
        }
    }

    /**
     * Reset the reset password token for a customer.
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return void
     */
    public function reSetRpToken($customer)
    {
        //Creating RP Token with date stamp
        $token = $this->mathRandom->getUniqueHash();
        $this->accountManagement->changeResetPasswordLinkToken($customer, null);
    }
}
