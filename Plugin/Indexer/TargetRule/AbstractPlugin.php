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

namespace Smile\ElasticsuiteTargetRule\Plugin\Indexer\TargetRule;

use \Magento\TargetRule\Model\ResourceModel\Index as RuleProductIndexResource;

/**
 * Abstract Rule-Product and Product-Rule indexers plugin
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTargetRule
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
abstract class AbstractPlugin
{
    /**
     * @var \Magento\TargetRule\Model\ResourceModel\Index
     */
    protected $indexResource;

    /**
     * @var boolean
     */
    protected $cleanRulesCache;

    /**
     * Plugin constructor
     *
     * @param RuleProductIndexResource $indexResource   Product-Rule/Rule-product index resource model
     * @param boolean                  $cleanRulesCache Whether to clean rules action index/cache after reindex
     */
    public function __construct(
        RuleProductIndexResource $indexResource,
        $cleanRulesCache
    ) {
        $this->indexResource = $indexResource;
        $this->cleanRulesCache = $cleanRulesCache;
    }

    /**
     * Clean per-type (related products, upsells, cross-sells) rules suggested products index/cache
     * (cache tag: main_<rule_type>_<store_id> and table magento_targetrule_index)
     *
     * @return void
     */
    protected function cleanRulesCache()
    {
        if ($this->cleanRulesCache) {
            // Remove old cache index data.
            $this->indexResource->cleanIndex();
        }
    }
}
