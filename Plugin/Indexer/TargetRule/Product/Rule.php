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

namespace Smile\ElasticsuiteTargetRule\Plugin\Indexer\TargetRule\Product;

use \Smile\ElasticsuiteTargetRule\Plugin\Indexer\TargetRule\AbstractPlugin;
use \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor as RuleProductIndexerProcessor;
use \Smile\ElasticsuiteTargetRule\Model\Indexer\TargetRule\Percolator as RulePercolatorIndexer;
use \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule as ProductRuleIndexer;
use \Magento\TargetRule\Model\ResourceModel\Index as RuleProductIndexResource;

/**
 * Product-rule indexer plugin
 * On a full reindex, re-routes the rules reindexing to the custom Elasticsuite indexer.
 * On a diff reindex (of product id(s)), prevents any native rule-matching operation
 * (because \Magento\TargetRule\Model\Rule::getMatchingProductIds is not rewritten yet).
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTargetRule
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
class Rule extends AbstractPlugin
{
    /**
     * Rule-Product indexer processor
     *
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor
     */
    protected $ruleProductProcessor;

    /**
     * @var \Smile\ElasticsuiteTargetRule\Model\Indexer\TargetRule\Percolator
     */
    protected $rulePercolatorIndexer;

    /**
     * Plugin constructor
     *
     * @param RuleProductIndexerProcessor $ruleProductProcessor  Rule-product indexer processor
     * @param RulePercolatorIndexer       $rulePercolatorIndexer Indexer creating percolators for target rules in ES
     * @param RuleProductIndexResource    $indexResource         Product-Rule/Rule-product index resource model
     * @param bool                        $cleanRulesCache       Whether to clean rules action index/cache after reindex
     */
    public function __construct(
        RuleProductIndexerProcessor $ruleProductProcessor,
        RulePercolatorIndexer $rulePercolatorIndexer,
        RuleProductIndexResource $indexResource,
        $cleanRulesCache
    ) {
        $this->ruleProductProcessor = $ruleProductProcessor;
        $this->rulePercolatorIndexer = $rulePercolatorIndexer;
        parent::__construct($indexResource, $cleanRulesCache);
    }

    /**
     * Around - Execute materialization on ids entities
     * Bypasses entirely the native indexer.
     *
     * @param ProductRuleIndexer $indexer    Native product-rule indexer
     * @param \Closure           $proceed    Original method
     * @param int[]              $productIds Product ID(s) to reindex
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(ProductRuleIndexer $indexer, \Closure $proceed, $productIds)
    {
        $this->cleanRulesCache();
        // Note : rules related **product** cache tags are not cleaned up.
    }

    /**
     * Around - Execute full indexation
     * Bypasses entirely the native indexer
     *
     * @param ProductRuleIndexer $indexer Native product-rule indexer
     * @param \Closure           $proceed Original method
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecuteFull(ProductRuleIndexer $indexer, \Closure $proceed)
    {
        /*
         * Native rule-product and product-rule indexers full actions are the same
         * (ie full reindex of the rules), this native check prevents two consecutive
         * (and so, useless) full reindex in the same PHP process/context.
         */
        if (!$this->ruleProductProcessor->isFullReindexPassed()) {
            $this->rulePercolatorIndexer->executeFull();
            $this->ruleProductProcessor->setFullReindexPassed();
            $this->cleanRulesCache();
            // Note : rules related **product** cache tags are not cleaned up.
        }
    }

    /**
     * Around - Execute partial indexation by ID list
     * Bypasses entirely the native indexer
     *
     * @param ProductRuleIndexer $indexer    Native product-rule indexer
     * @param \Closure           $proceed    Original method
     * @param int[]              $productIds Product ID(s) to reindex
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecuteList(ProductRuleIndexer $indexer, \Closure $proceed, array $productIds)
    {
        $this->cleanRulesCache();
        // Note : rules related **product** cache tags are not cleaned up.
    }

    /**
     * Around - Execute partial indexation by ID
     * Bypasses entirely the native indexer
     *
     * @param ProductRuleIndexer $indexer   Native product-rule indexer
     * @param \Closure           $proceed   Original method
     * @param int                $productId Product ID
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecuteRow(ProductRuleIndexer $indexer, \Closure $proceed, $productId)
    {
        $this->cleanRulesCache();
        // Note : rules related **product** cache tags are not cleaned up.
    }
}
