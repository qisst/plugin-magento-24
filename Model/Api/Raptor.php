<?php
namespace Qisst\Magento24\Model\Api;
use Qisst\Magento24\Api\RaptorInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Store\Model\GroupFactory;
use Magento\Store\Model\ResourceModel\Group;
use Magento\Store\Model\ResourceModel\Store;
use Magento\Store\Model\ResourceModel\Website;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Model\WebsiteFactory;
class Raptor implements RaptorInterface
{
    protected $productRepository;
    protected $resultJsonFactory;

    protected $config;


        /**
         * @var WebsiteFactory
         */
        private $websiteFactory;
        /**
         * @var Website
         */
        private $websiteResourceModel;
        /**
         * @var StoreFactory
         */
        private $storeFactory;
        /**
         * @var GroupFactory
         */
        private $groupFactory;
          /**
         * @var Group
         */
        private $groupResourceModel;
        /**
         * @var Store
         */
        private $storeResourceModel;
        /**
         * @var ManagerInterface
         */
        private $eventManager;

        /**
         * InstallData constructor.
         * @param WebsiteFactory $websiteFactory
         * @param Website $websiteResourceModel
         * @param Store $storeResourceModel
         * @param Group $groupResourceModel
         * @param StoreFactory $storeFactory
         * @param GroupFactory $groupFactory
         * @param ManagerInterface $eventManager
         */

    public function __construct(
      \Magento\Config\Model\ResourceModel\Config $config,
             WebsiteFactory $websiteFactory,
             Website $websiteResourceModel,
             Store $storeResourceModel,
             Group $groupResourceModel,
             StoreFactory $storeFactory,
             GroupFactory $groupFactory,
             ManagerInterface $eventManager,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
    \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory)
     {
        $this->config = $config;
        $this->websiteFactory = $websiteFactory;
        $this->websiteResourceModel = $websiteResourceModel;
        $this->storeFactory = $storeFactory;
        $this->groupFactory = $groupFactory;
        $this->groupResourceModel = $groupResourceModel;
        $this->storeResourceModel = $storeResourceModel;
        $this->eventManager = $eventManager;
        $this->productRepository = $productRepository;
        $this->resultJsonFactory = $resultJsonFactory;
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
      $tableName   = $connection->getTableName('sales_order_item');
      $tableNamefilter   = $connection->getTableName('sales_order_grid');
      $sql = "SELECT `order_id` FROM `".$tableName."` where item_id = ". $entityDesired;
      $itemno = $connection->fetchAll($sql);
      $orderst = $itemno[0]['order_id'];
      $ordern = (int)$orderst;
      $sqlfilter = "SELECT `increment_id` FROM `".$tableNamefilter."` where entity_id = ". $ordern;
      $orderno = $connection->fetchAll($sqlfilter);
      $orderst = $orderno[0]['increment_id'];
      $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/qisstpayfilter.log');
      $logger = new \Zend\Log\Logger();
      $logger->addWriter($writer);
      $logger->info('Below are OrderNo Refined');
      $logger->info($ordern);
        return $orderst;
    }
    public function createOrder($orderfname, $orderlname, $orderemail, $orderphone, $orderaddress1, $orderaddress2, $ordercity, $orderstate, $orderpostcode, $ordercountry, $orderquantiry, $orderprice, $ordershipping, $ordertax, $ordernote){
      $orderInfo =[
            'email'        => 'test@gmail.com', //customer email id
            'currency_id'  => 'USD',
            'address' =>[
                'firstname'    => 'Rohan',
                'lastname'     => 'Hapani',
                'prefix' => '',
                'suffix' => '',
                'street' => 'Test Street',
                'city' => 'Miami',
                'country_id' => 'US',
                'region' => 'Florida',
                'region_id' => '18', // State region id
                'postcode' => '98651',
                'telephone' => '1234567890',
                'fax' => '1234567890',
                'save_in_address_book' => 1
            ],
            'items'=>
                [
                    //simple product
                    [
                        'product_id' => '10',
                        'qty' => 10
                    ],
                    //configurable product
                    [
                        'product_id' => '70',
                        'qty' => 2,
                        'super_attribute' => [
                            93 => 52,
                            142 => 167
                        ]
                    ]
                ]
        ];
        $store = $this->storeManager->getStore();
        $storeId = $store->getStoreId();
        $websiteId = $this->storeManager->getStore()->getWebsiteId();
        $customer = $this->customerFactory->create()
        ->setWebsiteId($websiteId)
        ->loadByEmail($orderInfo['email']); // Customer email address
        if(!$customer->getId()){
            /**
             * If Guest customer, Create new customer
             */
            $customer->setStore($store)
                    ->setFirstname($orderInfo['address']['firstname'])
                    ->setLastname($orderInfo['address']['lastname'])
                    ->setEmail($orderInfo['email'])
                    ->setPassword('admin@123');
            $customer->save();
        }
        $quote = $this->quote->create(); //Quote Object
        $quote->setStore($store); //set store for our quote

        /**
         * Registered Customer
         */
        $customer = $this->customerRepository->getById($customer->getId());
        $quote->setCurrency();
        $quote->assignCustomer($customer); //Assign Quote to Customer

        //Add Items in Quote Object
        foreach($orderInfo['items'] as $item){
            $product=$this->productRepository->getById($item['product_id']);
            if(!empty($item['super_attribute']) ) {
                /**
                 * Configurable Product
                 */
                $buyRequest = new \Magento\Framework\DataObject($item);
                $quote->addProduct($product,$buyRequest);
            } else {
                /**
                 * Simple Product
                 */
                $quote->addProduct($product,intval($item['qty']));
            }
        }

        //Billing & Shipping Address to Quote
        $quote->getBillingAddress()->addData($orderInfo['address']);
        $quote->getShippingAddress()->addData($orderInfo['address']);

        // Set Shipping Method
        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setCollectShippingRates(true)
                        ->collectShippingRates()
                        ->setShippingMethod('freeshipping_freeshipping'); //shipping method code, Make sure free shipping method is enabled
        $quote->setPaymentMethod('checkmo'); //Payment Method Code, Make sure checkmo payment method is enabled
        $quote->setInventoryProcessed(false);
        $quote->save();
        $quote->getPayment()->importData(['method' => 'checkmo']);

        // Collect Quote Totals & Save
        $quote->collectTotals()->save();
        // Create Order From Quote Object
        $order = $this->quoteManagement->submit($quote);
        // Send Order Email to Customer Email ID
        $this->orderSender->send($order);
        // Get Order Increment ID
        $orderId = $order->getIncrementId();
        if($orderId){
            $result['success'] = $orderId;
        } else {
            $result = [ 'error' => true,'msg' => 'Error occurs for Order placed'];
        }
      return $result;
    }
/* This is Validator Function Only  End */
}
