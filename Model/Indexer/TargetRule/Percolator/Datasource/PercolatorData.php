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

namespace Smile\ElasticsuiteTargetRule\Model\Indexer\TargetRule\Percolator\Datasource;

use Smile\ElasticsuiteCore\Api\Index\DatasourceInterface;
use \Smile\ElasticsuiteTargetRule\Helper\RuleConverter;
use \Magento\TargetRule\Model\ResourceModel\Rule\CollectionFactory as TargetRuleCollectionFactory;
use \Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder as QueryBuilder;

/**
 * Class PercolatorData
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTargetRule
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
class PercolatorData implements DatasourceInterface
{
    /**
     * The percolator type for this entity
     */
    const PERCOLATOR_TYPE = 'target_rule';

    /**
     * @var \Magento\TargetRule\Model\ResourceModel\Rule\CollectionFactory
     */
    protected $ruleCollectionFactory;

    /**
     * @var \Smile\ElasticsuiteTargetRule\Helper\RuleConverter
     */
    protected $ruleConverter;

    /**
     * @var \Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder;
     */
    protected $queryBuilder;

    /**
     * PercolatorData constructor.
     * @param TargetRuleCollectionFactory $ruleCollectionFactory Target rule collection factory
     * @param RuleConverter               $ruleConverter         Target rule to ES Catalog Rule converter
     * @param QueryBuilder                $queryBuilder          ES query builder
     */
    public function __construct(
        TargetRuleCollectionFactory $ruleCollectionFactory,
        RuleConverter $ruleConverter,
        QueryBuilder $queryBuilder
    ) {
        $this->ruleCollectionFactory = $ruleCollectionFactory;
        $this->ruleConverter = $ruleConverter;
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * Add rule percolator data to the index
     * {@inheritdoc}
     */
    public function addData($storeId, array $indexData)
    {
        $ruleCollection = $this->ruleCollectionFactory->create()
            ->setFlag('do_not_run_after_load', true)
            ->addFieldToFilter('rule_id', array_keys($indexData));

        foreach ($ruleCollection as $rule) {
            /** @var \Magento\TargetRule\Model\Rule $rule */
            $rule->afterLoad();

            $ruleId = $rule->getId();
            $query = $this->queryBuilder->buildQuery(
                $this->ruleConverter->getCatalogRuleFromConditions($rule)->getSearchQuery()
            );
            $percolatorData = [
                'type' => 'product',
                'percolator_type' => self::PERCOLATOR_TYPE,
                'query' => $query,
            ];
            $indexData[$ruleId] += $percolatorData;
        }

        return $indexData;
    }
}
