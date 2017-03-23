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

class Price extends \Smile\ElasticsuiteVirtualCategory\Model\Rule\Condition\Product
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
     * @return \Smile\ElasticsuiteCore\Search\Request\QueryInterface
     */
    public function getSearchQuery($excludedCategories = [])
    {
        $this->setAttribute('price');

        /** @var \Magento\TargetRule\Model\Index $context */
        $context = $this->getRule()->getContext();
        /* @var $resource \Magento\TargetRule\Model\ResourceModel\Index */
        $resource = $context->getResource();

        /** @var \Magento\Catalog\Model\Product $product */
        $product = $context->getProduct();

        // value contains the percent in "<operator> <percent>% of <matched product price>"
        $referencePrice = $resource->bindPercentOf($product->getFinalPrice(), $this->getValue());
        $this->setValue($referencePrice);

        return parent::getSearchQuery($excludedCategories);
    }
}