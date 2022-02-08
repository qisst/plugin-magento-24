<?php /**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Qisst\Magento24\Controller\Page;

use Magento\Framework\App\Bootstrap;
use Magento\Checkout\Model\Cart;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;

class createiframe extends \Magento\Framework\App\Action\Action implements HttpGetActionInterface
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    protected $_scopeConfig;

    protected $_encryptor;
    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    public function __construct(
       \Magento\Framework\App\Action\Context $context,
       \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
       \Magento\Framework\Encryption\EncryptorInterface $encryptor,
       \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory)
    {
       $this->resultJsonFactory = $resultJsonFactory;
       $this->_scopeConfig = $scopeConfig;
       $this->_encryptor = $encryptor;
       parent::__construct($context);
    }
    /**
     * Phone page action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $key = $this->_scopeConfig->getValue(
            'qp/config/merchant_api_key',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );


        $key = $this->_encryptor->decrypt($key);

        $is_live = $this->_scopeConfig->getValue(
          'qp/config/qp_is_live',
          \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $url = $is_live == 1? 'https://qisstpay.com/api/send-data':'https://sandbox.qisstpay.com/api/send-data';
        $curl = curl_init();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $cart = $objectManager->get('\Magento\Checkout\Model\Cart');
        $shippingAddress = $cart->getQuote()->getShippingAddress();
        $cartId = $cart->getQuote()->getId();
        $cart_data = $shippingAddress->getData();
        $objctManager = \Magento\Framework\App\ObjectManager::getInstance();
        $remote = $objctManager->get('Magento\Framework\HTTP\PhpEnvironment\RemoteAddress');

        $params = $_SERVER;

        $bootstrap = Bootstrap::create(BP, $params);

        $obj = $bootstrap->getObjectManager();

        $state = $obj->get('Magento\Framework\App\State');
        $state->setAreaCode('frontend');

        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $om->get('Psr\Log\LoggerInterface');
        $storeManager->info('Magecomp Log');

        $storeManager=$om->get('Magento\Store\Model\StoreManagerInterface');
        $product=$om->get('Magento\Catalog\Model\Product');
        $quote=$om->get('Magento\Quote\Model\QuoteFactory');
        $quoteManagement=$om->get('Magento\Quote\Model\QuoteManagement');
        $customerFactory=$om->get('Magento\Customer\Model\CustomerFactory');
        $customerRepository=$om->get('Magento\Customer\Api\CustomerRepositoryInterface');
        $orderService=$om->get('Magento\Sales\Model\Service\OrderService');
        $cart=$om->get('Magento\Checkout\Model\Cart');
        $productFactory=$om->get('Magento\Catalog\Model\ProductFactory');
        $cartRepositoryInterface = $om->get('Magento\Quote\Api\CartRepositoryInterface');
        $cartManagementInterface = $om->get('Magento\Quote\Api\CartManagementInterface');
        $fnameorder = $cart_data['firstname'];
        $lnameorder = $cart_data['lastname'];
        $str1order = $cart_data['street'];
        $regionorder = $cart_data['region'];
        $cityorder = $cart_data['city'];
        $postcodeorder = $cart_data['postcode'];
        $phoneorder = $cart_data['telephone'];

        $orderData =[
            'currency_id'  => 'PKR',
            'email'        => $cart_data['email'],
            'paymentMethod'=> [
              'method'=> 'cashondelivery'
            ],
            'shipping_address' =>[
                'firstname'    => $fnameorder,
                'lastname'     => $lnameorder,
                'street' => $str1order,
                'city' => $cityorder,
                'country_id' => '',
                'region' => $regionorder,
                'region_id'=> $regionorder,
                'postcode' => $postcodeorder,
                'telephone' => $phoneorder,
                'fax' => $phoneorder,
                'save_in_address_book' => 1
            ],
            'items'=> [
                //array of product which order you want to create
                ['product_id'=>'1','qty'=>1],
                ['product_id'=>'2','qty'=>1]
            ]
        ];

        $store=$storeManager->getStore();
        $websiteId =$storeManager->getStore()->getWebsiteId();
        $customer=$customerFactory->create();
        $customer->setWebsiteId($websiteId);
        $customer->loadByEmail($orderData['email']);// load customet by email address
        if(!$customer->getEntityId()){
            //If not avilable then create this customer
            $customer->setWebsiteId($websiteId)
                ->setStore($store)
                ->setFirstname($orderData['shipping_address']['firstname'])
                ->setLastname($orderData['shipping_address']['lastname'])
                ->setEmail($orderData['email'])
                ->setPassword($orderData['email']);
            $customer->save();
        }
        $cart_id = $cartManagementInterface->createEmptyCart();
        $cart = $cartRepositoryInterface->get($cart_id);

        $cart->setStore($store);

        // if you have already had the buyer id, you can load customer directly
        $customer= $customerRepository->getById($customer->getEntityId());
        $cart->setCurrency();
        $cart->assignCustomer($customer); //Assign quote to customer

        //add items in quote
        foreach($orderData['items'] as $item){
            $product = $productFactory->create()->load($item['product_id']);
            $cart->addProduct(
                $product,
                intval($item['qty'])
            );
        }

        //Set Address to quote
        //$quote->getBillingAddress()->addData($orderData['shipping_address']);
        $cart->getBillingAddress()->addData($orderData['shipping_address']);
        //$quote->getShippingAddress()->addData($orderData['shipping_address']);
        $cart->getShippingAddress()->addData($orderData['shipping_address']);

        /*$this->shippingRate
            ->setCode('freeshipping_freeshipping')
            ->getPrice(1);
        */
        $shippingAddress = $cart->getShippingAddress();

        $shippingAddress->setCollectShippingRates(true)
            ->collectShippingRates()
            ->setShippingMethod('freeshipping_freeshipping'); //shipping method

        $cart->setPaymentMethod('cashondelivery'); //payment method

        $cart->setInventoryProcessed(false);

        // Set sales order payment
        $cart->getPayment()->importData(['method' => 'cashondelivery']);

        // Collect total and save
        $cart->collectTotals();

        // Submit the quote and create the order
        $cart->save();
        $cart = $cartRepositoryInterface->get($cart->getId());
        $order_id = $cartManagementInterface->placeOrder($cart->getId());


        curl_setopt_array($curl, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS =>'{
            "partner_id":"magento",
            "fname":"'.$cart_data['firstname'].'",
            "mname":"",
            "lname":"'.$cart_data['lastname'].'",
            "email":"",
            "ip_addr":"'.$remote->getRemoteAddress().'",
            "shipping_info":{
              "addr1":"'.$cart_data['street'].'",
              "addr2":"",
              "state":"'.$cart_data['region'].'",
              "city":"'.$cart_data['city'].'",
              "zip":"'.$cart_data['postcode'].'"
            },
            "billing_info":{
              "addr1":"'.$cart_data['street'].'",
              "addr2":"",
              "state":"'.$cart_data['region'].'",
              "city":"'.$cart_data['city'].'",
              "zip":"'.$cart_data['postcode'].'"
            },
            "shipping_details":{},
            "card_details":{},
            "itemFlag":false,
            "line_items":[],
            "merchant_order_id": "'.$order_id.'",
            "total_amount":'.$cart_data['grand_total'].'
          }',
          CURLOPT_HTTPHEADER => array(
            'sec-ch-ua: "Chromium";v="94", "Google Chrome";v="94", ";Not A Brand";v="99"',
            'sec-ch-ua-mobile: ?0',
            'Authorization: Basic '.$key,
            'Content-Type: application/json',
            'Accept: */*',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.81 Safari/537.36',
            'sec-ch-ua-platform: "Windows"',
            'Cookie: XSRF-TOKEN=eyJpdiI6Im1XQkpKd3lYbTlObDFBRjk1NFo0S1E9PSIsInZhbHVlIjoiTWJKNEJoMlZTSUFsOStCSEp6WWpJdWJnZzh6VkViL0FJbjJvRW5JVm96MlN5a0lRQkJ6U2hScmZES3RoSHdOeEJxTjVZTWxPWWpCNHQydVplemwzOFBTcjIyVVp5Y2VNY2JPMEp3ZFF1M0YzWXNSeDYyekxCN3ppNGNHdTlMTlEiLCJtYWMiOiI2OGZhMzM0NDYzOWZkZjFhYTdiYjU3Yzg0ZTAzYjcxYjMxNDdhYWYxNDYxY2FiN2U1YmM5ZGI0ZTgyMzhhOWM4IiwidGFnIjoiIn0%3D; qisstpay_sandbox_session=eyJpdiI6IklpVnRIVkhxYW1YWDM3cUZGNHB1V0E9PSIsInZhbHVlIjoialdYNUxjUllzR3JhMDJuUVZ5SHRzdUt1N21VYVpYcE9neGZRN2pQYU4yS0lEWGtNaFFoSjFIWlI4aERNa2hEYVJiOFlSUVZicW84blBVQlBWaXlHWElEQitzTlNFcGExWjZld3FuOGRSSFQyMmo2R2Vmb2VFUHhNOGhSTmRaREYiLCJtYWMiOiJiZDdjNTYyOWVlN2I5YjAyMTZjNTllY2NlOGJiYTA1YjgyOTRmYTA4NTllMTE0OWYxZDQ2NmY3NmE5NmMwMWE0IiwidGFnIjoiIn0%3D'
          ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $result = $this->resultJsonFactory->create();
        //return $result->setData(json_decode($response, 1));
        //$session = $this->getOnepage()->getCheckout();
        if (!$objectManager->get(\Magento\Checkout\Model\Session\SuccessValidator::class)->isValid()) {
            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        }

        // $session->clearQuote();            ***** COMMENT THIS LINE *****

        //@todo: Refactor it to match CQRS
        $resultPage = $this->resultPageFactory->create();
        $this->_eventManager->dispatch(
            'checkout_onepage_controller_success_action',
            [
                'order_ids' => [$session->getLastOrderId()],
                'order' => $session->getLastRealOrder()
            ]
        );
        return $resultPage;
    }


}
