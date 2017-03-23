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

namespace Smile\ElasticsuiteTargetRule\Model\Actions\Condition\Product\Special;

use \Magento\TargetRule\Model\Actions\Condition\Product\Attributes as TargetRuleActionAttributes

class Price extends \Smile\ElasticsuiteVirtualCategory\Model\Rule\Condition\Product
{

    /**
     * Retrieve SELECT WHERE condition for product collection
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @param \Magento\TargetRule\Model\Index $object
     * @param array &$bind
     * @return \Zend_Db_Expr
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getConditionForCollection($collection, $object, &$bind)
    {
        /* @var $resource \Magento\TargetRule\Model\ResourceModel\Index */
        $resource = $object->getResource();
        $operator = $this->getOperator();

        $where = $resource->getOperatorBindCondition(
            'price_index.min_price',
            'final_price',
            $operator,
            $bind,
            [['bindPercentOf', $this->getValue()]]
        );

        $this->logger->debug(sprintf('%s', $where));

        return new \Zend_Db_Expr(sprintf('(%s)', $where));
    }
    

    /**
     * Build a search query for the current rule.
     *
     * @param array $excludedCategories Categories excluded of query building (avoid infinite recursion).
     *
     * @return QueryInterface
     */
    public function getSearchQuery($excludedCategories = [])
    {
        $searchQuery = parent::getSearchQuery();

        if ($this->getAttribute() === 'category_ids') {
            $searchQuery = $this->getCategorySearchQuery($excludedCategories);
        }

        return $searchQuery;
    }
}