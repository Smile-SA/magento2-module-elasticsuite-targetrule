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

namespace Smile\ElasticsuiteTargetRule\Plugin\Rule;

use \Magento\TargetRule\Model\ResourceModel\Rule\Collection as RuleCollection;
use \Smile\ElasticsuiteTargetRule\Model\Percolator as TargetRulePercolator;
use \Psr\Log\LoggerInterface;

/**
 * Plugin that replaces the DB lookup by an ElasticSearch percolation
 * when determining which target rules a given product matches.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTargetRule
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
class Collection
{
    /**
     * @var TargetRulePercolator
     */
    protected $percolator;

    /**
     * @var \Psr\Log\LoggerInterface;
     */
    protected $logger;

    /**
     * Collection plugin constructor.
     *
     * @param TargetRulePercolator $percolator ES target rule percolator
     * @param LoggerInterface      $logger     Logger
     */
    public function __construct(
        TargetRulePercolator $percolator,
        LoggerInterface $logger
    ) {
        $this->percolator   = $percolator;
        $this->logger       = $logger;
    }

    /**
     * Around - Add filter by product id to collection
     *
     * @param RuleCollection $collection Target rules collection
     * @param \Closure       $proceed    Original method
     * @param int            $productId  ID of product which must match the rules
     *
     * @return RuleCollection
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundAddProductFilter(
        RuleCollection $collection,
        \Closure $proceed,
        $productId
    ) {
        $matchedRuleIds = $this->percolator->getMatchingRuleIds($productId);

        if (empty($matchedRuleIds)) {
            /* no matched rule, so force an empty collection */
            $collection->addFieldToFilter('main_table.rule_id', ['eq' => 0]);
        } else {
            $collection->addFieldToFilter('main_table.rule_id', ['in' => $matchedRuleIds]);
        }

        return $collection;
    }
}