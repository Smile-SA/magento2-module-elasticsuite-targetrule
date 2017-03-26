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

namespace Smile\ElasticsuiteTargetRule\Model\Indexer\TargetRule;

/**
 * Abstract action reindex class
 * Rewritten to replace the indexing logic of registering products matching rule(s) conditions
 * to the rule/product association table (magento_targetrule_product)
 * by creating into ElasticSearch a percolator whose query corresponds to each rule conditions.
 *
 * @class   Smile
 * @package Smile\ElasticsuiteTargetRule
 * @author  Richard BAYET <richard.bayet@smile.fr>
 */
abstract class AbstractAction extends \Magento\TargetRule\Model\Indexer\TargetRule\AbstractAction
{
    /**
     * @var \Smile\ElasticsuiteTargetRule\Model\Indexer\TargetRule\Percolator
     */
    protected $rulePercolatorIndexer;

    /**
     * @param \Magento\TargetRule\Model\RuleFactory $ruleFactory
     * @param \Magento\TargetRule\Model\ResourceModel\Rule\CollectionFactory $ruleCollectionFactory
     * @param \Magento\TargetRule\Model\ResourceModel\Index $resource
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     */

    /**
     * AbstractAction constructor.
     *
     * @param \Magento\TargetRule\Model\RuleFactory                          $ruleFactory           Target rule factory
     * @param \Magento\TargetRule\Model\ResourceModel\Rule\CollectionFactory $ruleCollectionFactory Rule collection factory
     * @param \Magento\TargetRule\Model\ResourceModel\Index                  $resource              Indexer resource model
     * @param \Magento\Store\Model\StoreManagerInterface                     $storeManager          Store manager
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface           $localeDate            Locale date converter
     * @param Percolator                                                     $rulePercolatorIndexer Target rule percolator indexer
     */
    public function __construct(
        \Magento\TargetRule\Model\RuleFactory $ruleFactory,
        \Magento\TargetRule\Model\ResourceModel\Rule\CollectionFactory $ruleCollectionFactory,
        \Magento\TargetRule\Model\ResourceModel\Index $resource,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Smile\ElasticsuiteTargetRule\Model\Indexer\TargetRule\Percolator $rulePercolatorIndexer
    ) {
        parent::__construct($ruleFactory, $ruleCollectionFactory, $resource, $storeManager, $localeDate);

        $this->rulePercolatorIndexer = $rulePercolatorIndexer;
    }

    /**
     * Reindex all
     *
     * @return void
     */
    protected function _reindexAll()
    {
        $indexResource = $this->_resource;

        // Remove old cache index data.
        $this->_cleanIndex();
        $indexResource->removeProductIndex([]);

        $ruleCollection = $this->_ruleCollectionFactory->create();

        foreach ($ruleCollection as $rule) {
            // $indexResource->saveProductIndex($rule);
            $this->rulePercolatorIndexer->reindex($rule);
        }
    }

    /**
     * Reindex targetrules by product id
     * Native method removes occurrences of the product from the association table,
     * then :
     * - populates the association table with the product according to the rules it matches
     * - clear product cache accordingly
     * As \Magento\TargetRule\Model\Rule::getMatchingProductIds() native behavior is not desirable
     * and the method has not been rewritten yet, those second and third steps are not performed here.
     *
     * @param int|null $productId Product ID
     * @return $this
     */
    protected function _reindexByProductId($productId = null)
    {
        $indexResource = $this->_resource;

        // Remove old cache index data.
        $this->_cleanIndex();

        // Remove old matched product index.
        $indexResource->removeProductIndex($productId);

        return $this;
    }

    /**
     * Reindex rule by ID
     * Native method empties the rule records in the association table,
     * then :
     * - populates the association table again for the rule with the products that matches it
     * - clear product cache accordingly
     * As \Magento\TargetRule\Model\Rule::getMatchingProductIds() native behavior is not desirable
     * and the method has not been rewritten yet, those second and third steps are not performed here.
     *
     * @param int $ruleId Rule ID
     * @return void
     */
    protected function _reindexByRuleId($ruleId)
    {
        // Remove old cache index data.
        $this->_cleanIndex();

        /** @var \Magento\TargetRule\Model\Rule $rule */
        $rule = $this->_ruleFactory->create();
        $rule->load($ruleId);
        if ($rule->getId()) {
            $this->rulePercolatorIndexer->reindex($rule);
        }
    }
}
