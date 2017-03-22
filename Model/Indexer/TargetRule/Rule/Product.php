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


class Product extends \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product
{
    /**
     * @param \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Action\Row $ruleProductIndexerRow
     * @param \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Action\Rows $ruleProductIndexerRows
     * @param \Magento\TargetRule\Model\Indexer\TargetRule\Action\Full $ruleProductIndexerFull
     * @param \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor $productRuleProcessor
     * @param \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor $ruleProductProcessor
     */
    public function __construct(
        \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Action\Row $ruleProductIndexerRow,
        \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Action\Rows $ruleProductIndexerRows,
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