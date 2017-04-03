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

namespace Smile\ElasticsuiteTargetRule\Model\Indexer\TargetRule\Percolator\Action;

use \Smile\ElasticsuiteTargetRule\Model\ResourceModel\Indexer\TargetRule\Percolator\Action\Full as ResourceModel;

/**
 * Target rule fulltext/percolator indexer full action handler
 * (actually, also used for diff indexing)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTargetRule
 */
class Full
{
    /**
     * @var \Smile\ElasticsuiteTargetRule\Model\ResourceModel\Indexer\TargetRule\Percolator\Action\Full
     */
    protected $resourceModel;

    /**
     * Constructor.
     *
     * @param ResourceModel $resourceModel Indexer resource model.
     */
    public function __construct(ResourceModel $resourceModel)
    {
        $this->resourceModel  = $resourceModel;
    }

    /**
     * Get data for a list of target rules in a store id.
     * If the list of target rule ids is null, all rules data will be loaded.
     *
     * @param integer    $storeId Store id.
     * @param array|null $ruleIds List of target rule ids.
     *
     * @return \Traversable
     */
    public function rebuildStoreIndex($storeId, $ruleIds = null)
    {
        $lastRuleId = 0;

        do {
            $rules = $this->getSearchableTargetRules($storeId, $ruleIds, $lastRuleId);

            foreach ($rules as $ruleData) {
                $lastRuleId = (int) $ruleData['rule_id'];
                yield $lastRuleId => $ruleData;
            }
        } while (!empty($rules));
    }

    /**
     * Load a bulk of rules data.
     *
     * @param int        $storeId Store id.
     * @param array|null $ruleIds Target rule ids filter
     * @param integer    $fromId  Load from rule id greater than.
     * @param integer    $limit   Number of rules to load.
     *
     * @return array
     */
    private function getSearchableTargetRules($storeId, $ruleIds = null, $fromId = 0, $limit = 100)
    {
        return $this->resourceModel->getSearchableTargetRules($storeId, $ruleIds, $fromId, $limit);
    }
}
