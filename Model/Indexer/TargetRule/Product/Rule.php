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
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2017 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTargetRule\Model\Indexer\TargetRule\Product;

class Rule extends \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule
{
    /**
     * Construct
     *
     * @param \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Action\Row $productRuleIndexerRow
     * @param \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Action\Rows $productRuleIndexerRows
     * @param \Smile\ElasticsuiteTargetRule\Model\Indexer\TargetRule\Action\Full $productRuleIndexerFull
     * @param \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor $ruleProductProcessor
     * @param \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor $productRuleProcessor
     * @param \Magento\TargetRule\Model\Indexer\TargetRule\Action\Clean $productRuleIndexerClean
     * @param \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Action\CleanDeleteProduct $productRuleIndexerCleanDeleteProduct
     */
    public function __construct(
        \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Action\Row $productRuleIndexerRow,
        \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Action\Rows $productRuleIndexerRows,
        \Smile\ElasticsuiteTargetRule\Model\Indexer\TargetRule\Action\Full $productRuleIndexerFull,
        \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor $ruleProductProcessor,
        \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor $productRuleProcessor,
        \Magento\TargetRule\Model\Indexer\TargetRule\Action\Clean $productRuleIndexerClean,
        \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Action\CleanDeleteProduct $productRuleIndexerCleanDeleteProduct
    ) {
        $this->_productRuleIndexerRow = $productRuleIndexerRow;
        $this->_productRuleIndexerRows = $productRuleIndexerRows;
        $this->_productRuleIndexerFull = $productRuleIndexerFull;
        $this->_ruleProductProcessor = $ruleProductProcessor;
        $this->_productRuleProcessor = $productRuleProcessor;
        $this->_productRuleIndexerClean = $productRuleIndexerClean;
        $this->_productRuleIndexerCleanDeleteProduct = $productRuleIndexerCleanDeleteProduct;
    }
}