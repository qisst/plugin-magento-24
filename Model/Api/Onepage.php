<?php
namespace Qisst\Magento24\Model\Api;
class Onepage extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $_customerRepository;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $_cart;

    /**
     * @var \Magento\Store\Model\StoreFactory
     */
    protected $storeFactory;

    protected $orderRepository;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,

        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Quote\Model\Quote\Address\Rate $rate,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    )
    {
        $this->_registry = $registry;
        $this->_storeManager = $storeManager;
        $this->_customerFactory = $customerFactory;
        $this->_objectManager = $objectManager;
        $this->_productFactory = $productFactory;
        $this->_customerRepository = $customerRepository;
        $this->_cartRepositoryInterface = $cartRepository;
        $this->_shippingRate = $rate;
        $this->orderRepository = $orderRepository;
        parent::__construct($context,$registry);
    }

    public function placeOrder($orderfname, $orderlname, $orderemail, $orderphone, $orderaddress1, $orderaddress2, $ordercity, $orderstate, $orderpostcode, $ordercountry, $orderquantiry, $orderprice, $ordershipping, $ordertax, $ordernote) {

        $customer = $this->_registry->registry('auth_customer');
        // if (!$customer) {
        //     throw new Exception($this->__('User not authorized'));
        // }

        //$data = json_decode(json_encode($data), True);

        // $items = $this->getCartItems();
        //$items = $data['products'];

        $customerAddressModel = $this->_objectManager->create('Magento\Customer\Model\Address');
        //$shippingID =  $customer->getDefaultShipping();
        //$address = $customerAddressModel->load($shippingID);

        $orderData = [
            'currency_id' => 'PKR',
            'email' => 'zaheer@gmail.com', //buyer email id
            'shipping_address' => [
                'firstname' => $orderfname,
                'lastname' => $orderlname,
                'street' => $orderaddress1,
                'city' => $ordercity,
                'country_id' => $ordercountry,
                'region' => $orderstate,
                'postcode' => $orderpostcode,
                'telephone' => $orderphone,
                'fax' => '',
                'save_in_address_book' => 1
            ],
            'items' => [ //array of product which order you want to create
                       ['product_id'=>'1','qty'=>2]
                     ]
        ];

        return $this->createOrder($orderData, $orderData);
    }
    public function createOrder($orderData, $data)
    {
        $response=array();
        $response['success']=FALSE;

        if(!count($orderData['items'])) {
            $response['error_msg'] = 'Cart is Empty';
        } else {
            $this->cartManagementInterface = $this->_objectManager->get('\Magento\Quote\Api\CartManagementInterface');

            //init the store id and website id
            $store = $this->_storeManager->getStore(1);
            $websiteId = $this->_storeManager->getStore()->getWebsiteId();

            //init the customer
            $customer = $this->_customerFactory->create();
            $customer->setWebsiteId($websiteId);
            $customer->loadByEmail($orderData['email']);// load customer by email address

            //check the customer
            if (!$customer->getEntityId()) {

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
            $cart = $this->_cartRepositoryInterface->get($cart_id);

            $cart->setStore($store);

            // if you have already buyer id then you can load customer directly
            $customer = $this->_customerRepository->getById($customer->getEntityId());
            $cart->setCurrency();
            $cart->assignCustomer($customer); //Assign quote to customer

            $_productModel = $this->_productFactory->create();
            //add items in quote
            foreach ($orderData['items'] as $item) {
                $product = $_productModel->load($item['product_id']);

                try {
                    // print_r($item); die();
                    $params = array('product' => $item['product_id'], 'qty' => $item['qty']);
                    if (array_key_exists('options', $item) && $item['options']) {
                        $params['options'] = json_decode(json_encode($item['options']), True);
                    }
                    if ($product->getTypeId() == 'configurable') {
                        $params['super_attribute'] = $item['super_attribute'];
                    } elseif ($product->getTypeId() == 'bundle') {
                        $params['bundle_option'] = $item['bundle_option'];
                        $params['bundle_option_qty'] = $item['bundle_option_qty'];
                    } elseif ($product->getTypeId() == 'grouped') {
                        $params['super_group'] = $item['super_group'];
                    }

                    $objParam = new \Magento\Framework\DataObject();
                    $objParam->setData($params);
                    // print_r($objParam); die();
                    $cart->addProduct($product, $objParam);

                } catch (Exception $e) {
                    $response[$item['product_id']]= $e->getMessage();
                }
            }

            //Set Address to quote
            $cart->getBillingAddress()->addData($orderData['shipping_address']);
            $cart->getShippingAddress()->addData($orderData['shipping_address']);

            // Collect Rates and Set Shipping & Payment Method
            $this->_shippingRate
                ->setCode('checkmo')
                ->getPrice(1);

            $shippingAddress = $cart->getShippingAddress();

            $shippingAddress->setCollectShippingRates(true)
                ->collectShippingRates()
                ->setShippingMethod('checkmo'); //shipping method
            $cart->getShippingAddress()->addShippingRate($this->_shippingRate);

            $cart->setPaymentMethod('checkmo'); //payment method

            $cart->setInventoryProcessed(false);

            // Set sales order payment
            $cart->getPayment()->importData(['method' => 'checkmo']);

            // Collect total and saeve
            $cart->collectTotals();

            // Submit the quote and create the order
            $cart->save();
            $cart = $this->_cartRepositoryInterface->get($cart->getId());
            try{
                $order_id = $this->_objectManager->get('\Magento\Quote\Api\CartManagementInterface')->placeOrder($cart->getId());
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $order = $objectManager->create('\Magento\Sales\Model\Order')->load($order_id);
                $order->addStatusHistoryComment('This comment is programatically added to last order in this Magento setup');
                $order->save();
                if(isset($order_id) && !empty($order_id)) {
                    $order = $this->orderRepository->get($order_id);
                    $this->deleteQuoteItems(); //Delete cart items
                    $response['success'] = TRUE;
                    $response['success_data']['increment_id'] = $order->getIncrementId();
                }
            } catch (Exception $e) {
                $response['error_msg']=$e->getMessage();
            }
        }
        return $response;
    }

    public function deleteQuoteItems(){
        $checkoutSession = $this->getCheckoutSession();
        $allItems = $checkoutSession->getQuote()->getAllVisibleItems();//returns all teh items in session
        foreach ($allItems as $item) {
            $itemId = $item->getItemId();//item id of particular item
            $quoteItem=$this->getItemModel()->load($itemId);//load particular item which you want to delete by his item id
            $quoteItem->delete();//deletes the item
        }
    }
    public function getCheckoutSession(){
        $checkoutSession = $this->_objectManager->get('Magento\Checkout\Model\Session');//checkout session
        return $checkoutSession;
    }

    public function getItemModel(){
        $itemModel = $this->_objectManager->create('Magento\Quote\Model\Quote\Item');//Quote item model to load quote item
        return $itemModel;
    }

    public function getCartItems()
    {
        $cart = $this->_objectManager->get('\Magento\Checkout\Model\Cart');

        // retrieve quote items collection
        $itemsCollection = $cart->getQuote()->getItemsCollection();

        // get array of all items what can be display directly
        $itemsVisible = $cart->getQuote()->getAllVisibleItems();

        // retrieve quote items array
        $items = $cart->getQuote()->getAllItems();
        $itemsInCart = array();
        foreach($items as $item) {
            $itemsInCart[] = array(
                                'product_id' => $item->getProductId(),
                                'qty' => $item->getQty(),
                            );

        }
        return $itemsInCart;
    }
}
