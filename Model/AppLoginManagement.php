<?php
declare(strict_types=1);

namespace Studio3Marketing\AppAccess\Model;

use Magento\Framework\Math\Random;
use Studio3Marketing\AppAccess\Api\AppLoginManagementInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\AccountManagement;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\Webapi\Exception as ApiException;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Webapi\Rest\Request;

class AppLoginManagement extends AbstractModel implements AppLoginManagementInterface
{
    /**
     * @var Request
     */
    protected Request $request;


    /**
     * @var ResultFactory
     */
    protected ResultFactory $resultFactory;

    /**
     * @var CustomerInterfaceFactory
     */
    protected CustomerInterfaceFactory $customerInterfaceFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    protected CustomerRepositoryInterface $customerRepository;

    /**
     * @var CustomerFactory
     */
    protected CustomerFactory $customerFactory;

    /**
     * @var AccountManagement
     */
    protected AccountManagement $accountManagement;

    /**
     * @var EncryptorInterface
     */
    protected EncryptorInterface $encryptor;

    /**
     * @var UrlInterface
     */
    protected UrlInterface $urlInterface;

    /**
     * @var AccountManagementInterface
     */
    private AccountManagementInterface $accountManagementInterface;

    /**
     * @var Random
     */
    private Random $mathRandom;

    /**
     * @var JsonFactory
     */
    protected JsonFactory $jsonResultFactory;

    public function __construct(
        Request $request,
        ResultFactory $resultFactory,
        JsonFactory $jsonResultFactory,
        CustomerRepositoryInterface $customerRepository,
        CustomerFactory $customerFactory,
        AccountManagement $accountManagement,
        UrlInterface $urlInterface,
        EncryptorInterface $encryptor,
        AccountManagementInterface $accountManagementInterface,
        CustomerInterfaceFactory $customerInterfaceFactory,
        Random $mathRandom

    ) {
        $this->request = $request;
        $this->resultFactory = $resultFactory;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->customerRepository = $customerRepository;
        $this->customerFactory = $customerFactory;
        $this->accountManagement = $accountManagement;
        $this->urlInterface = $urlInterface;
        $this->encryptor = $encryptor;
        $this->accountManagementInterface = $accountManagementInterface;
        $this->customerInterfaceFactory = $customerInterfaceFactory;
        $this->mathRandom = $mathRandom;
    }

    /**
     * Handles the app login process. Creates or loads a customer, sets UUID, and generates a login URL.
     *
     * @return string JSON encoded response with URL, message, and status
     * @throws ApiException
     */
    public function getAppLogin()
    {
        $data = $this->request->getBodyParams();

        if(!empty($data['email']) && isset($data['email'])) {
            // Check if customer already exists
            $websiteId = 1;
            $email = $data['email'];
            $firstLastName = explode("@", $email);
            $firstname = $firstLastName[0];
            $password = $this->random_alphanumeric_string();
            $lastname = $firstLastName[1];

            if(!empty($data['firstName']) && isset($data['firstName'])){
                $firstname = $data['firstName'];
            }

            if(!empty($data['lastName']) && isset($data['lastName'])){
                $lastname = $data['lastName'];
            }

            $redirectUrl = null;
            if(isset($data['redirectUrl'])) {
                if(!empty($data['redirectUrl'])) {
                    $redirectUrl = $data['redirectUrl'];
                }
            }

            if(isset($data['uuid'])) {
                $uuid = $data['uuid'];
            } else {
                $uuid = "";
            }

            try {
                if($this->accountManagementInterface->isEmailAvailable($email, $websiteId)) {
//                    $this->jdbg(__LINE__,"If email not exist.");
                    $customer = $this->customerInterfaceFactory->create();
                    $customer->setWebsiteId($websiteId);
                    $customer->setEmail($email);
                    $customer->setFirstname($firstname);
                    $customer->setLastname($lastname);
                    $hashedPassword = $this->encryptor->getHash($password, true);
                    // Todo : Review
                    $this->customerRepository->save($customer, $hashedPassword);
                    $customer = $this->customerFactory->create();
                    $customer = $customer->setWebsiteId($websiteId)->loadByEmail($email);
                    $customer->setUuid($uuid);
                    $customer->save();
                    $customer->getWebsiteId();
                    $this->accountManagement->initiatePasswordReset($email, AccountManagement::EMAIL_RESET);
                    $data = [
                        "url" => $this->generatePrivateUrl($customer, $redirectUrl),
                        "message" => "Success",
                        "status" => 200
                    ];
                    return json_encode($data, JSON_UNESCAPED_SLASHES);
                } else {

                    $customer = $this->customerFactory->create();
                    $customer = $customer->setWebsiteId($websiteId)->loadByEmail($email);
//                    $customer->setUuid($uuid);
//                    $customer->save();
                    if(empty($customer['uuid']) && (!empty($uuid))) {
                        $urlParams = [
                            'id' => $customer->getId(),
                            'token' => null,
                            'uuid' => $uuid,
                            "redurl" => base64_encode($redirectUrl),
                            '_secure' => false,
                        ];

                        $data = [
                            "url" => $this->urlInterface->getUrl('appaccess/account/login', $urlParams),
                            "message" => "Requires Login",
                            "status" => 300
                        ];
//                        $this->jdbg(__LINE__,"If email  exist and uuid not empty. ". $data['url']);
                        return json_encode($data, JSON_UNESCAPED_SLASHES);

                    } else {
//                        $this->jdbg(__LINE__,"If email not exist and uuid empty.");
                        $customer->setUuid($uuid);
                        $customer->save();
                        $data = [
                            "url" => $this->generatePrivateUrl($customer, $redirectUrl),
                            "message" => "Success",
                            "status" => 200
                        ];
                        return json_encode($data, JSON_UNESCAPED_SLASHES);
                    }
                }
            } catch (\Exception $e) {
                throw new ApiException(__($e->getMessage()));
            }
        }
    }

    /**
     * Generate a random alphanumeric string for use as a password or token.
     *
     * @return string
     */
    function random_alphanumeric_string()
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return substr(str_shuffle(str_repeat($chars, (int)ceil(16 / strlen($chars)))), 0, 16);
    }

    /**
     * Generate a private URL for customer login with a reset password token and optional redirect.
     *
     * @param \Magento\Customer\Model\Customer $customer
     * @param string|null $redirectUrl
     * @return string
     */
    public function generatePrivateUrl($customer, $redirectUrl)
    {
        $customerId = $customer->getId();

        // Reloading with repository to get compatible object type
        $customer = $this->customerRepository->getById($customerId);

        //Creating RP Token with date stamp
        $token = $this->mathRandom->getUniqueHash();
        $this->accountManagement->changeResetPasswordLinkToken($customer, $token);

        // Generate the private URL with the token and return URL (if provided)
        $urlParams = [
            'id' => $customerId,
            'token' => $token,
            'redurl' => base64_encode($redirectUrl),
            '_secure' => true,
        ];
        return $this->urlInterface->getUrl('appaccess/account/login', $urlParams);
    }
}

