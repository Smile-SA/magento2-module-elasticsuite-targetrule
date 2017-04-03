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

namespace Smile\ElasticsuiteTargetRule\Plugin\Indexer\TargetRule\Rule;

use \Smile\ElasticsuiteTargetRule\Plugin\Indexer\TargetRule\AbstractPlugin;
use \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product as RuleProductIndexer;
use \Smile\ElasticsuiteTargetRule\Model\Indexer\TargetRule\Percolator as RulePercolatorIndexer;
use \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor as ProductRuleIndexerProcessor;
use \Magento\TargetRule\Model\ResourceModel\Index as RuleProductIndexResource;

/**
 * Rule-product indexer plugin
 * Re-routes the rules reindexing to the custom Elasticsuite indexer.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTargetRule
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
class Product extends AbstractPlugin
{
    /**
     * Product-Rule indexer processor
     *
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor
     */
    protected $productRuleProcessor;

    /**
     * @var \Smile\ElasticsuiteTargetRule\Model\Indexer\TargetRule\Percolator
     */
    protected $rulePercolatorIndexer;

    /**
     * Plugin constructor
     *
     * @param ProductRuleIndexerProcessor $productRuleProcessor  Product-Rule indexer processor
     * @param RulePercolatorIndexer       $rulePercolatorIndexer Indexer creating percolators for target rules in ES
     * @param RuleProductIndexResource    $indexResource         Product-Rule/Rule-product index resource model
     * @param boolean                     $cleanRulesCache       Whether to clean rules action index/cache after reindex
     */
    public function __construct(
        ProductRuleIndexerProcessor $productRuleProcessor,
        RulePercolatorIndexer $rulePercolatorIndexer,
        RuleProductIndexResource $indexResource,
        $cleanRulesCache
    ) {
        $this->productRuleProcessor = $productRuleProcessor;
        $this->rulePercolatorIndexer = $rulePercolatorIndexer;
        parent::__construct($indexResource, $cleanRulesCache);
    }

    /**
     * Around - Execute materialization on ids entities
     * Bypasses entirely the native indexer.
     *
     * @param RuleProductIndexer $indexer Native rule-product indexer
     * @param \Closure           $proceed Original method
     * @param int[]              $ruleIds Rule ID(s) to reindex
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(RuleProductIndexer $indexer, \Closure $proceed, $ruleIds)
    {
        $this->rulePercolatorIndexer->execute($ruleIds);
        $this->cleanRulesCache();
        // Note : rules related **product** cache tags are not cleaned up.
    }

    /**
     * Around - Execute full indexation
     * Bypasses entirely the native indexer
     *
     * @param RuleProductIndexer $indexer Native rule-product indexer
     * @param \Closure           $proceed Original method
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecuteFull(RuleProductIndexer $indexer, \Closure $proceed)
    {
        /*
         * Native rule-product and product-rule indexers full actions are the same
         * (ie full reindex of the rules), this native check prevents two consecutive
         * (and so, useless) full reindex in the same PHP process/context.
         */
        if (!$this->productRuleProcessor->isFullReindexPassed()) {
            $this->rulePercolatorIndexer->executeFull();
            $this->productRuleProcessor->setFullReindexPassed();
            $this->cleanRulesCache();
            // Note : rules related **product** cache tags are not cleaned up.
        }
    }

    /**
     * Around - Execute partial indexation by ID list
     * Bypasses entirely the native indexer
     *
     * @param RuleProductIndexer $indexer Native rule-product indexer
     * @param \Closure           $proceed Original method
     * @param int[]              $ruleIds Rule ID(s) to reindex
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecuteList(RuleProductIndexer $indexer, \Closure $proceed, array $ruleIds)
    {
        $this->rulePercolatorIndexer->executeList($ruleIds);
        $this->cleanRulesCache();
        // Note : rules related **product** cache tags are not cleaned up.
    }

    /**
     * Around - Execute partial indexation by ID
     * Bypasses entirely the native indexer
     *
     * @param RuleProductIndexer $indexer Native rule-product indexer
     * @param \Closure           $proceed Original method
     * @param int                $ruleId  Rule ID
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecuteRow(RuleProductIndexer $indexer, \Closure $proceed, $ruleId)
    {
        $this->rulePercolatorIndexer->executeRow($ruleId);
        $this->cleanRulesCache();
        // Note : rules related **product** cache tags are not cleaned up.
    }
}
