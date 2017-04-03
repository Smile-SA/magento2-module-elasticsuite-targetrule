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

namespace Smile\ElasticsuiteTargetRule\Plugin\CatalogSearch\Indexer;

use \Magento\CatalogSearch\Model\Indexer\Fulltext as ProductFulltextIndexer;
use \Smile\ElasticsuiteTargetRule\Model\Indexer\TargetRule\Percolator as TargetRulePercolatorIndexer;

/**
 * CatalogSearch fulltext indexer plugin.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTargetRule
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class Fulltext
{
    /**
     * @var TargetRulePercolatorIndexer
     */
    protected $targetRulePercolatorIndexer;

    /**
     * Fulltext plugin constructor.
     *
     * @param TargetRulePercolatorIndexer $percolatorIndexer Target rules fulltext/percolator indexer
     */
    public function __construct(TargetRulePercolatorIndexer $percolatorIndexer)
    {
        $this->targetRulePercolatorIndexer = $percolatorIndexer;
    }

    /**
     * After - Execute full indexation
     * Launch a full reindex of the target rules immediately after a full reindex of the products,
     * because a new ElasticSearch index has been created (with only products in it).
     *
     * @param ProductFulltextIndexer $indexer Catalog product fulltext indexer
     * @param void                   $result  Void result
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecuteFull(ProductFulltextIndexer $indexer, $result)
    {
        $this->targetRulePercolatorIndexer->executeFull();
        /*
         * Note: neither the rules suggested products index/cache
         * nor the rules related **product** cache tags are cleaned up.
         */
    }
}
