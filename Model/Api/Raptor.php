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

        return "Return Response";
    }
/* This is Validator Function Only  End */
}
