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

namespace Smile\ElasticsuiteTargetRule\Model\ResourceModel\Indexer\TargetRule\Percolator\Action;

use Smile\ElasticsuiteCore\Model\ResourceModel\Indexer\AbstractIndexer;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TargetRule\Model\ResourceModel\Rule\CollectionFactory as TargetRuleCollectionFactory;

/**
 * Target rule fulltext/percolator indexer full action handler resource model
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTargetRule
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
class Full extends AbstractIndexer
{
    /**
     * @var \Magento\TargetRule\Model\ResourceModel\Rule\CollectionFactory
     */
    protected $ruleCollectionFactory;

    /**
     * Constructor.
     *
     * @param ResourceConnection          $resource              Database adpater.
     * @param StoreManagerInterface       $storeManager          Store manager.
     * @param TargetRuleCollectionFactory $ruleCollectionFactory Target rule collection factory
     */
    public function __construct(
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        TargetRuleCollectionFactory $ruleCollectionFactory
    ) {
        parent::__construct($resource, $storeManager);
        $this->ruleCollectionFactory = $ruleCollectionFactory;
    }

    /**
     * Load a bulk of rule data.
     *
     * @param int        $storeId Store id.
     * @param array|null $ruleIds Target rule ids filter
     * @param integer    $fromId  Load from rule id greater than.
     * @param integer    $limit   Number of rules to load.
     *
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getSearchableTargetRules($storeId, $ruleIds = null, $fromId = 0, $limit = 100)
    {
        /**
         * @var \Magento\TargetRule\Model\ResourceModel\Rule\Collection $collection
         */
        $collection = $this->ruleCollectionFactory->create();

        if ($ruleIds !== null) {
            $collection->addFieldToFilter('rule_id', ['in' => $ruleIds]);
        }
        $collection->addFieldToFilter('rule_id', ['gt' => $fromId])
            ->addFieldToSelect(['is_active', 'apply_to'])
            ->addFieldToSelect(['rule_name' => 'name'])
            ->setOrder('rule_id', $collection::SORT_ORDER_ASC)
            ->setPageSize($limit);

        return $this->connection->fetchAll($collection->getSelect());
    }
}
