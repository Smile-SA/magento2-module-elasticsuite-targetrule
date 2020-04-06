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

use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * TargetRule Action Product Price (percentage) Condition search query translator model.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTargetRule
 */
class Price extends \Smile\ElasticsuiteVirtualCategory\Model\Rule\Condition\Product
{
    /**
     * Build a search query for the current rule.
     *
     * @param array    $excludedCategories  Categories excluded of query building (avoid infinite recursion).
     * @param int|null $virtualCategoryRoot Category root for Virtual Category.
     *
     * @return QueryInterface|null
     */
    public function getSearchQuery($excludedCategories = [], $virtualCategoryRoot = null): ?QueryInterface
    {
        // Test below prevents to re-apply the price percentage if multiple calls on this condition.
        if (!$this->getData('reference_price')) {
            $this->setAttribute('price');

            /** @var \Magento\TargetRule\Model\Index $context */
            $context = $this->getRule()->getContext();
            /* @var $resource \Magento\TargetRule\Model\ResourceModel\Index */
            $resource = $context->getResource();

            /** @var \Magento\Catalog\Model\Product $product */
            $product = $context->getProduct();

            // Value contains the percent in "<operator> <percent>% of <matched product price>".
            $referencePrice = $resource->bindPercentOf($product->getFinalPrice(), $this->getValue());
            $this->setValue($referencePrice);
            $this->setData('reference_price', $referencePrice);
        }

        return parent::getSearchQuery($excludedCategories);
    }
}
