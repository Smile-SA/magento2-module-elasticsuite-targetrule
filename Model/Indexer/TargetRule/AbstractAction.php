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
            if (!$rule->getIsActive()) {
                // continue;
            }
            // $indexResource->saveProductIndex($rule);
            $this->rulePercolatorIndexer->reindex($rule);
        }
    }
}
