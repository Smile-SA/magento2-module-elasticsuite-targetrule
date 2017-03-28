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

namespace Smile\ElasticsuiteTargetRule\Plugin\Core\Index;

use \Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface;
use \Smile\ElasticsuiteCore\Api\Index\IndexInterface;
use \Smile\ElasticsuiteTargetRule\Model\Indexer\TargetRule\Action\Full as TargetRuleIndexFullAction;

/**
 * IndexOperation plugin to force a full reindex of target rules
 * when a new catalog product index is installed in ElasticSearch
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTargetRule
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
class IndexOperation
{
    /**
     * @var \Smile\ElasticsuiteTargetRule\Model\Indexer\TargetRule\Action\Full
     */
    protected $ruleIndexerFullAction;

    /**
     * IndexOperation constructor.
     *
     * @param \Smile\ElasticsuiteTargetRule\Model\Indexer\TargetRule\Action\Full $full Indexer full action
     */
    public function __construct(
        TargetRuleIndexFullAction $full
    ) {
        $this->ruleIndexerFullAction = $full;
    }

    /**
     * Around - Switch the alias to the installed index and delete the old index.
     *
     * @param \Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface $subject The index operation
     * @param \Closure                                                  $proceed Original method
     * @param \Smile\ElasticsuiteCore\Api\Index\IndexInterface          $index   Installed index.
     * @param integer|string|\Magento\Store\Api\Data\StoreInterface     $store   Store (id, identifier or object).
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\IndexInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundInstallIndex(
        IndexOperationInterface $subject,
        \Closure $proceed,
        IndexInterface $index,
        $store
    ) {
        $proceed($index, $store);

        if ($index->needInstall() && ($index->getIdentifier() == 'catalog_product')) {
            /*
             * Push all the target rules into the newly replaced catalog product index.
             * ---
             * Note: the rules indexer will actually loop on all stores to reindex
             * the target rules into each store index... which is kind of suboptimal
             * because the accurate store context is available here.
             */
            $this->ruleIndexerFullAction->execute();
        }

        return $index;
    }
}
