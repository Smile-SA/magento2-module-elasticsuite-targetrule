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

use \Magento\TargetRule\Model\Actions\Condition\Product\Attributes as TargetRuleActionAttributes;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * TargetRule Action Product Attributes Condition search query translator model.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTargetRule
 */
class Attributes extends \Smile\ElasticsuiteVirtualCategory\Model\Rule\Condition\Product
{
    /**
     * Load attribute property from array
     *
     * @param array $array Condition metadata
     *
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
     * Delegates to parent method after extracting the rule "value" from the context (ie the matched product)
     *
     * @param array    $excludedCategories  Categories excluded of query building (avoid infinite recursion).
     * @param int|null $virtualCategoryRoot Category root for Virtual Category.
     *
     * @return QueryInterface|null
     */
    public function getSearchQuery($excludedCategories = [], $virtualCategoryRoot = null): ?QueryInterface
    {
        if ($this->getValueType() == TargetRuleActionAttributes::VALUE_TYPE_CHILD_OF) {
            // Implicit: $this->getAttribute() == 'category_ids'.
            $subCategoryIds = implode(',', $this->getChildrenCategoryIds());
            $this->setValue($subCategoryIds);
        } elseif ($this->getValueType() == TargetRuleActionAttributes::VALUE_TYPE_SAME_AS) {
            /** @var \Magento\TargetRule\Model\Index $context */
            $context = $this->getRule()->getContext();
            /** @var \Magento\Catalog\Model\Product $product */
            $product = $context->getProduct();

            /** @var string $attribute */
            $attribute = $this->getAttribute();
            // Note: works as intended if $attribute is 'category_ids'.
            $value = $product->getDataUsingMethod($attribute);
            if ($attribute === 'category_ids') {
                $value = implode(',', $value);
            }
            $this->setValue($value);
        }
        /*
         * Implicit else : $this->getValueType() == TargetRuleActionAttributes::VALUE_TYPE_CONSTANT
         * => Nothing to do.
         */

        return parent::getSearchQuery($excludedCategories);
    }

    /**
     * Get the IDs of all subcategories of the product in context
     *
     * @return array
     */
    protected function getChildrenCategoryIds()
    {
        $childrenCategoryIds = [];

        /** @var \Magento\TargetRule\Model\Index $context */
        $context = $this->getRule()->getContext();

        $categoryIds = $context->getProduct()->getCategoryIds();
        if (!empty($categoryIds)) {
            /** @var \Magento\TargetRule\Model\ResourceModel\Index $resource */
            $resource = $context->getResource();

            $concatenated = $resource->getConnection()->getConcatSql(['tp.path', "'/%'"]);
            $select = $resource->getConnection()->select()
                ->from(
                    ['tp' => $resource->getTable('catalog_category_entity')],
                    []
                )->join(
                    ['tc' => $resource->getTable('catalog_category_entity')],
                    "tc.path LIKE {$concatenated}",
                    'tc.entity_id'
                )->where(
                    'tp.entity_id IN (?)',
                    $categoryIds
                )->distinct();
            $childrenCategoryIds = $resource->getConnection()->fetchCol($select);
        }

        return $childrenCategoryIds;
    }
}
