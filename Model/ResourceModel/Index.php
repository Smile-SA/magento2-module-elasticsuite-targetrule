<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTargetRule
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2017 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTargetRule\Model\ResourceModel;

use Smile\ElasticsuiteCatalogRule\Model\RuleFactory as CatalogRuleFactory;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder as QueryBuilder;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\CollectionFactory as FulltextProductCollectionFactory;
use Psr\Log\LoggerInterface;

/**
 * Rewrite of TargetRule Product Index by Rule Product List Type Resource Model :
 * - builds and executes an Elasticsearch query instead of relying on the SQL DB when interpreting the rule 'actions'
 *   (the list of products to be provided/displayed by the rule)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTargetRule
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
class Index extends \Magento\TargetRule\Model\ResourceModel\Index
{
    /**
     * @var \Smile\ElasticsuiteCatalogRule\Model\RuleFactory
     */
    protected $catalogRuleFactory;

    /**
     * @var \Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder;
     */
    protected $queryBuilder;

    /**
     * @var \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\CollectionFactory
     */
    protected $fulltextProductCollectionFactory;

    /**
     * @var \Psr\Log\LoggerInterface;
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context              $context                          Context
     * @param \Magento\TargetRule\Model\ResourceModel\IndexPool              $indexPool                        Target rule index pool
     * @param \Magento\TargetRule\Model\ResourceModel\Rule                   $rule                             Target rule resource model
     * @param \Magento\CustomerSegment\Model\ResourceModel\Segment           $segmentCollectionFactory         Customer segment factory
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory         Catalog product collection factory
     * @param \Magento\Store\Model\StoreManagerInterface                     $storeManager                     Store manager
     * @param \Magento\Catalog\Model\Product\Visibility                      $visibility                       Visibility model
     * @param \Magento\CustomerSegment\Model\Customer                        $customer                         Customer model
     * @param \Magento\Customer\Model\Session                                $session                          Customer session
     * @param \Magento\CustomerSegment\Helper\Data                           $customerSegmentData              Customer segment helper
     * @param \Magento\TargetRule\Helper\Data                                $targetRuleData                   Target rule helper
     * @param \Magento\Framework\Registry                                    $coreRegistry                     Core registry
     * @param \Smile\ElasticsuiteCatalogRule\Model\RuleFactory               $catalogRuleFactory               ES catalog rule factory
     * @param QueryBuilder                                                   $queryBuilder                     ES query builder
     * @param FulltextProductCollectionFactory                               $fulltextProductCollectionFactory ES product collection factory
     * @param LoggerInterface                                                $logger                           Logger
     * @param string                                                         $connectionName                   Connection name
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\TargetRule\Model\ResourceModel\IndexPool $indexPool,
        \Magento\TargetRule\Model\ResourceModel\Rule $rule,
        \Magento\CustomerSegment\Model\ResourceModel\Segment $segmentCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Product\Visibility $visibility,
        \Magento\CustomerSegment\Model\Customer $customer,
        \Magento\Customer\Model\Session $session,
        \Magento\CustomerSegment\Helper\Data $customerSegmentData,
        \Magento\TargetRule\Helper\Data $targetRuleData,
        \Magento\Framework\Registry $coreRegistry,
        CatalogRuleFactory $catalogRuleFactory,
        QueryBuilder $queryBuilder,
        FulltextProductCollectionFactory $fulltextProductCollectionFactory,
        LoggerInterface $logger,
        $connectionName = null
    ) {
        parent::__construct(
            $context,
            $indexPool,
            $rule,
            $segmentCollectionFactory,
            $productCollectionFactory,
            $storeManager,
            $visibility,
            $customer,
            $session,
            $customerSegmentData,
            $targetRuleData,
            $coreRegistry,
            $connectionName
        );
        $this->catalogRuleFactory   = $catalogRuleFactory;
        $this->queryBuilder         = $queryBuilder;
        $this->fulltextProductCollectionFactory = $fulltextProductCollectionFactory;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\TargetRule\Model\Rule $rule Target rule
     *
     * @return \Smile\ElasticsuiteCatalogRule\Model\Rule
     */
    protected function _getCatalogRuleFromTargetRule($rule)
    {
        /*
         * Replace the Combine, Attributes and Special\Price and Product condition models
         * "Magento\TargetRule\Model\Actions\Condition\Combine"
         *      -> "Smile\ElasticsuiteVirtualCategory\Model\Rule\Condition\Combine"
         * "Magento\TargetRule\Model\Actions\Condition\Product\Attributes" ->
         *      -> "Smile\ElasticsuiteTargetRule\Model\Actions\Condition\Product\Attributes"
         * "Magento\TargetRule\Model\Actions\Condition\Product\Special\Price"
         *      -> "Smile\ElasticsuiteTargetRule\Model\Actions\Condition\Product\Special\Price"
         *
         * TODO : move the model names into class parameters that can be redefined in the XML config ?
         *        or better, have a mapping in XML config ?
         */
        $targetRuleActions = unserialize($rule->getActionsSerialized());
        $targetRuleActions = json_encode($targetRuleActions);
        $targetRuleActions = str_replace(
            [
                addslashes('Magento\TargetRule\Model\Actions\Condition\Combine'),
                addslashes('Magento\TargetRule\Model\Actions\Condition\Product\Attributes'),
                addslashes('Magento\TargetRule\Model\Actions\Condition\Product\Special\Price'),
            ],
            [
                addslashes('Smile\ElasticsuiteVirtualCategory\Model\Rule\Condition\Combine'),
                addslashes('Smile\ElasticsuiteTargetRule\Model\Actions\Condition\Product\Attributes'),
                addslashes('Smile\ElasticsuiteTargetRule\Model\Actions\Condition\Product\Special\Price'),
            ],
            $targetRuleActions
        );
        $targetRuleActions = json_decode($targetRuleActions, true);

        /** @var \Smile\ElasticsuiteCatalogRule\Model\Rule $catalogRule */
        $catalogRule = $this->catalogRuleFactory->create();
        $catalogRule->setStoreId($rule->getStoreId());

        $catalogRule->getConditions()->loadArray($targetRuleActions);

        return $catalogRule;
    }

    /**
     * Retrieve found product ids by Rule action conditions
     * Converts the action conditions into an ElasticSuite catalog rule
     * to fetch the product IDs from ElasticSearch.
     *
     * @param \Magento\TargetRule\Model\Rule  $rule              Target rule
     * @param \Magento\TargetRule\Model\Index $object            Target rules index accessor (also contains the contextual Product)
     * @param int                             $limit             Max number of product IDs to return
     * @param array                           $excludeProductIds IDs of products to ignore/not to return
     * @return array
     */
    protected function _getProductIdsByRule($rule, $object, $limit, $excludeProductIds = [])
    {
        $rule->afterLoad();

        // To propagate the store context to the catalogRule.
        $rule->setStoreId($object->getStoreId());
        /** @var \Smile\ElasticsuiteCatalogRule\Model\Rule $catalogRule */
        $catalogRule = $this->_getCatalogRuleFromTargetRule($rule);
        // Provide target rule application context (inc. the current product) to the ES catalog rule.
        $catalogRule->setContext($object);

        /*
        $this->logger->debug('----- QB -----');
        $this->logger->debug(json_encode($this->queryBuilder->buildQuery($catalogRule->getSearchQuery())));
        $this->logger->debug('----- QB -----');
        */

        /* @var $collection \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection */
        $collection = $this->fulltextProductCollectionFactory->create()->setStoreId(
            $object->getStoreId()
        )->addPriceData(
            $object->getCustomerGroupId()
        )->setVisibility(
            $this->_visibility->getVisibleInCatalogIds()
        );

        $collection->addQueryFilter($catalogRule->getSearchQuery())
            ->setPageSize($limit);

        return $collection->load()->getLoadedIds();
    }
}
