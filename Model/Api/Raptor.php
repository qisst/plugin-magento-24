<?php
namespace Qisst\Magento24\Model\Api;
use Qisst\Magento24\Api\RaptorInterface;

class Raptor implements RaptorInterface
{
  public function __construct(
    \Magento\Config\Model\ResourceModel\Config $config,
    \Magento\Framework\App\Helper\Context $context,
            \Magento\Store\Model\StoreManagerInterface $storeManager,
            \Magento\Catalog\Model\ProductFactory $productFactory,
            \Magento\Quote\Model\QuoteManagement $quoteManagement,
            \Magento\Customer\Model\CustomerFactory $customerFactory,
            \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
            \Magento\Sales\Model\Service\OrderService $orderService,
            \Magento\Quote\Api\CartRepositoryInterface $cartRepositoryInterface,
            \Magento\Quote\Api\CartManagementInterface $cartManagementInterface,
            \Magento\Quote\Model\Quote\Address\Rate $shippingRate
    )
     {
       $this->config = $config;
       //parent::__construct($context);
       $this->_storeManager = $storeManager;
            $this->_productFactory = $productFactory;
            $this->quoteManagement = $quoteManagement;
            $this->customerFactory = $customerFactory;
            $this->customerRepository = $customerRepository;
            $this->orderService = $orderService;
            $this->cartRepositoryInterface = $cartRepositoryInterface;
            $this->cartManagementInterface = $cartManagementInterface;
            $this->shippingRate = $shippingRate;
     }



    /* This is Validator Function Only Start */
    public function returnOrderId($quoteid) {
      $entityDesired = $quoteid;
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $cart = $objectManager->get('\Magento\Checkout\Model\Cart');
        $shippingAddress = $cart->getQuote()->getShippingAddress();
        $cartId = $cart->getQuote()->getId();
        $this->_resources = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\App\ResourceConnection');
        $connection= $this->_resources->getConnection();
        $parentTable = $connection->getTableName('sales_order');
        $subSeqTable = $connection->getTableName('sales_order_grid');
        $customOrderGet = "SELECT `entity_id` FROM `".$parentTable."` where quote_id =".$entityDesired ;
        $customOrderNo = $connection->fetchAll($customOrderGet);
        if($customOrderNo){
          $customOrderReturn = $customOrderNo[0]['entity_id'];
          $sqlDetail = "SELECT `increment_id` FROM `".$subSeqTable."` where entity_id =".$customOrderReturn;
          $orderno = $connection->fetchAll($sqlDetail);
          $orderst = $orderno[0]['increment_id'];
        }else{return null;}
      if($orderst){return $orderst;}else{return null;}
    }

    /**
    * @param array $orderData
    * @return int $orderId
    *
    */
    public function createOrder($orderfname, $orderlname, $orderemail, $orderphone, $orderaddress1, $orderaddress2, $ordercity, $orderstate, $orderpostcode, $ordercountry, $orderquantiry, $orderprice, $ordershipping, $ordertax, $ordernote){
      //init the store id and website id @todo pass from array
      $orderData=[
     'currency_id'  => 'PKR',
     'email'        => $orderemail, //buyer email id
     'shipping_address' =>[
            'firstname'    => $orderfname, //address Details
            'lastname'     => $orderlname,
            'street' => $orderaddress1." ".$orderaddress2,
            'city' => $ordercity,
            'country_id' => $ordercountry,
            'region' => $orderstate,
            'postcode' => $orderpostcode,
            'telephone' => $orderphone,
            'fax' => '',
            'save_in_address_book' => 1
                 ],
   'items'=> [ //array of product which order you want to create
              ['product_id'=>'1','qty'=>$orderquantiry]
            ]
];
                  $store = $this->_storeManager->getStore();
                  $websiteId = $this->_storeManager->getStore()->getWebsiteId();

                  //init the customer
                  $customer=$this->customerFactory->create();
                  $customer->setWebsiteId($websiteId);
                  $customer->loadByEmail($orderData['email']);// load customet by email address

                  //check the customer
                  if(!$customer->getEntityId()){

                      //If not available then create this customer
                      $customer->setWebsiteId($websiteId)
                          ->setStore($store)
                          ->setFirstname($orderData['shipping_address']['firstname'])
                          ->setLastname($orderData['shipping_address']['lastname'])
                          ->setEmail($orderData['email'])
                          ->setPassword($orderData['email']);

                      $customer->save();
                  }

                  //init the quote
                  $cart_id = $this->cartManagementInterface->createEmptyCart();
                  $cart = $this->cartRepositoryInterface->get($cart_id);

                  $cart->setStore($store);

                  // if you have already had the buyer id, you can load customer directly
                  $customer= $this->customerRepository->getById($customer->getEntityId());
                  $cart->setCurrency();
                  $cart->assignCustomer($customer); //Assign quote to customer

                  //add items in quote
                  foreach($orderData['items'] as $item){
                      $product = $this->_productFactory->create()->load($item['product_id']);
                      $cart->addProduct(
                          $product,
                          intval($item['qty'])
                      );
                  }

                  //Set Address to quote @todo add section in order data for seperate billing and handle it
                  $cart->getBillingAddress()->addData($orderData['shipping_address']);
                  $cart->getShippingAddress()->addData($orderData['shipping_address']);

                  // Collect Rates, Set Shipping & Payment Method
                  $this->shippingRate
                      ->setCode('freeshipping_freeshipping')
                      ->getPrice(1);

                  $shippingAddress = $cart->getShippingAddress();

                  //@todo set in order data
                  $shippingAddress->setCollectShippingRates(true)
                      ->collectShippingRates()
                      ->setShippingMethod('flatrate_flatrate'); //shipping method
                  //$cart->getShippingAddress(0);

                  $cart->setPaymentMethod('checkmo'); //payment method

                  //@todo insert a variable to affect the invetory
                  $cart->setInventoryProcessed(false);

                  // Set sales order payment
                  $cart->getPayment()->importData(['method' => 'checkmo']);

                  // Collect total and save
                  $cart->collectTotals();



                  // Submit the quote and create the order
                  // $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                  // $order = $objectManager->create('\Magento\Sales\Model\Order')->load(53);
                  // $order->addStatusHistoryComment('This comment is programatically added to last order in this Magento setup');
                  // $order->save();


                  $cart->save();
                  //$cart = $this->cartRepositoryInterface->get($cart->getId());
                  //return $cart->getId();

                  $order_id = $this->cartManagementInterface->placeOrder($cart->getId());
                  return $cart_id;
    }
/* This is Validator Function Only  End */
}
