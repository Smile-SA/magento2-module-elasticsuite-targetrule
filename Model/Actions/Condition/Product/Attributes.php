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

namespace Smile\ElasticsuiteTargetRule\Model\Actions\Condition\Product;

use \Magento\TargetRule\Model\Actions\Condition\Product\Attributes as TargetRuleActionAttributes

class Attributes extends \Smile\ElasticsuiteVirtualCategory\Model\Rule\Condition\Product
{
    /**
     * Load attribute property from array
     *
     * @param array $array
     * @return $this
     */
    public function loadArray($array)
    {
        parent::loadArray($array);

        if (isset($array['value_type'])) {
            $this->setValueType($array['value_type']);
        }
        return $this;
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
        if ($this->getValueType() == TargetRuleActionAttributes::VALUE_TYPE_CONSTANT) {
            return parent::getSearchQuery($excludedCategories);
        }

        if ($this->getValueType() == TargetRuleActionAttributes::VALUE_TYPE_CHILD_OF) {
            // implicit: $this->getAttribute() == 'category_ids'
            // 1) grab categories of context product
            // 2) grab collection of subcategories (there might be some cross-over)
            // 3) inject those subcategories ids in condition getValue()
            // 4) return parent::getSearchQuery(...)
        }

        // implicit: $this->getValueType() == TargetRuleActionAttributes::VALUE_TYPE_SAME_AS
        if ($this->getAttribute() === 'category_ids') {
            // 1) grab categories of context product
            // 2) inject those category ids in condition getValue()
            // 3) return parent::getSearchQuery(...)
        } else {
            // 1) grab attribute of (getAttribute) of context product using callback/bind method
            // object->getDataUsingMethod(...)
            // 2) inject the values into condition getValue();
            // 3) return parent::getSearchQuery(...)
        }

        $searchQuery = parent::getSearchQuery();

        if ($this->getAttribute() === 'category_ids') {
            $searchQuery = $this->getCategorySearchQuery($excludedCategories);
        }

        return $searchQuery;
    }
}