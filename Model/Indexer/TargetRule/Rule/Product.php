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

namespace Smile\ElasticsuiteTargetRule\Model\Indexer\TargetRule\Rule;

/**
 * Rule product indexer proxy
 * Rewrite needed to inject custom indexer action models
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTargetRule
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
class Product extends \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product
{
    /**
     * Constructor.
     *
     * @param \Smile\ElasticsuiteTargetRule\Model\Indexer\TargetRule\Rule\Product\Action\Row  $ruleProductIndexerRow  Rule product row indexer
     * @param \Smile\ElasticsuiteTargetRule\Model\Indexer\TargetRule\Rule\Product\Action\Rows $ruleProductIndexerRows Rule product rows indexer
     * @param \Smile\ElasticsuiteTargetRule\Model\Indexer\TargetRule\Action\Full              $ruleProductIndexerFull Full action handler
     * @param \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor             $productRuleProcessor   Product rule processor
     * @param \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor             $ruleProductProcessor   Rule product processor
     */
    public function __construct(
        \Smile\ElasticsuiteTargetRule\Model\Indexer\TargetRule\Rule\Product\Action\Row $ruleProductIndexerRow,
        \Smile\ElasticsuiteTargetRule\Model\Indexer\TargetRule\Rule\Product\Action\Rows $ruleProductIndexerRows,
        \Smile\ElasticsuiteTargetRule\Model\Indexer\TargetRule\Action\Full $ruleProductIndexerFull,
        \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor $productRuleProcessor,
        \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor $ruleProductProcessor
    ) {
        $this->_ruleProductIndexerRow = $ruleProductIndexerRow;
        $this->_ruleProductIndexerRows = $ruleProductIndexerRows;
        $this->_ruleProductIndexerFull = $ruleProductIndexerFull;
        $this->_productRuleProcessor = $productRuleProcessor;
        $this->_ruleProductProcessor = $ruleProductProcessor;
    }
}
