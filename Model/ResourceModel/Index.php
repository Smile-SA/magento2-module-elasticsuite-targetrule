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

use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder as QueryBuilder;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\CollectionFactory as FulltextProductCollectionFactory;
use Smile\ElasticsuiteTargetRule\Helper\RuleConverter;

/**
 * Rewrite of TargetRule Product Index by Rule Product List Type Resource Model :
 * - builds and executes an Elasticsearch query instead of relying on the SQL DB when interpreting the rule 'actions'
 *   (the list of products to be provided/displayed by the rule)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTargetRule
 * @author   Richard BAYET <richard.bayet@smile.fr>
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) Inherited excessive coupling (single object added in constructor)
 */
class Index extends \Magento\TargetRule\Model\ResourceModel\Index
{
    /**
     * @var \Smile\ElasticsuiteTargetRule\Helper\RuleConverter
     */
    private $ruleConverter;

    /**
     * Constructor
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context              $context                  Context
     * @param \Magento\TargetRule\Model\ResourceModel\IndexPool              $indexPool                Target rule index pool
     * @param \Magento\TargetRule\Model\ResourceModel\Rule                   $rule                     Target rule resource model
     * @param \Magento\CustomerSegment\Model\ResourceModel\Segment           $segmentCollectionFactory Customer segment factory
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory Catalog product collection factory
     * @param \Magento\Store\Model\StoreManagerInterface                     $storeManager             Store manager
     * @param \Magento\Catalog\Model\Product\Visibility                      $visibility               Visibility model
     * @param \Magento\CustomerSegment\Model\Customer                        $customer                 Customer model
     * @param \Magento\Customer\Model\Session                                $session                  Customer session
     * @param \Magento\CustomerSegment\Helper\Data                           $customerSegmentData      Customer segment helper
     * @param \Magento\TargetRule\Helper\Data                                $targetRuleData           Target rule helper
     * @param \Magento\Framework\Registry                                    $coreRegistry             Core registry
     * @param RuleConverter                                                  $ruleConverter            Target rule to Catalog rule converter helper
     * @param string                                                         $connectionName           Connection name
     * @SuppressWarnings(PHPMD.ExcessiveParameterList) inherited method
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
        RuleConverter $ruleConverter,
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
        $this->ruleConverter = $ruleConverter;
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
     * @SuppressWarnings(PHPMD.CamelCaseMethodName) inherited method
     */
    protected function _getProductIdsByRule($rule, $object, $limit, $excludeProductIds = [])
    {
        $rule->afterLoad();

        // To propagate the store context to the catalogRule.
        $rule->setStoreId($object->getStoreId());
        /** @var \Smile\ElasticsuiteCatalogRule\Model\Rule $catalogRule */
        $catalogRule = $this->ruleConverter->getCatalogRuleFromActions($rule);
        // Provide target rule application context (inc. the current product) to the ES catalog rule.
        $catalogRule->setContext($object);

        /** @var \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection $collection */
        $collection = $this->_productCollectionFactory->create()->setStoreId(
            $object->getStoreId()
        )->addPriceData(
            $object->getCustomerGroupId()
        )->setVisibility(
            $this->_visibility->getVisibleInCatalogIds()
        );

        $collection->addQueryFilter($catalogRule->getSearchQuery());

        if ($excludeProductIds) {
            $collection = $this->excludeProductIds($collection, $excludeProductIds);
        }

        $collection->setPageSize($limit);

        return $collection->load()->getLoadedIds();
    }

    /**
     * Exclude product Ids from collection
     * @SuppressWarnings(PHPMD.StaticAccess) To remove when the call to ObjectManager will be refactored.
     *
     * @param \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection $collection        Product Collection
     * @param array                                                                      $excludeProductIds Product Ids to exclude
     *
     * @return mixed
     */
    private function excludeProductIds($collection, $excludeProductIds)
    {
        // Uncomment line below when PR https://github.com/Smile-SA/elasticsuite/pull/371 will be merged into Elasticsuite.
        // $collection->addFieldToFilter('entity_id', ['nin' => $excludeProductIds]);
        // Remove following code when uncommenting line above.
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory $queryFactory */
        $queryFactory  = $objectManager->get('\Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory');

        $query = $queryFactory->create(
            \Smile\ElasticsuiteCore\Search\Request\QueryInterface::TYPE_TERMS,
            ['field' => 'entity_id', 'values' => $excludeProductIds]
        );

        $query = $queryFactory->create(
            \Smile\ElasticsuiteCore\Search\Request\QueryInterface::TYPE_NOT,
            ['query' => $query]
        );

        $collection->addQueryFilter($query);

        return $collection;
    }
}
