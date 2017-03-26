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

/**
 * Product rule indexer proxy.
 * Rewrite needed to inject custom indexer action models
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTargetRule
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
class Rule extends \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule
{
    /**
     * Constructor
     *
     * @param \Smile\ElasticsuiteTargetRule\Model\Indexer\TargetRule\Product\Rule\Action\Row      $productRuleIndexerRow                Product rule row indexer
     * @param \Smile\ElasticsuiteTargetRule\Model\Indexer\TargetRule\Product\Rule\Action\Rows     $productRuleIndexerRows               Product rule rows indexer
     * @param \Smile\ElasticsuiteTargetRule\Model\Indexer\TargetRule\Action\Full                  $productRuleIndexerFull               Product rule full indexer
     * @param \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor                 $ruleProductProcessor                 Rule product processor
     * @param \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor                 $productRuleProcessor                 Product rule processor
     * @param \Magento\TargetRule\Model\Indexer\TargetRule\Action\Clean                           $productRuleIndexerClean              Product rule clean indexer
     * @param \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Action\CleanDeleteProduct $productRuleIndexerCleanDeleteProduct Product rule clean product indexer
     */
    public function __construct(
        \Smile\ElasticsuiteTargetRule\Model\Indexer\TargetRule\Product\Rule\Action\Row $productRuleIndexerRow,
        \Smile\ElasticsuiteTargetRule\Model\Indexer\TargetRule\Product\Rule\Action\Rows $productRuleIndexerRows,
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
