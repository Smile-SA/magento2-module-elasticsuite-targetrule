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

namespace Smile\ElasticsuiteTargetRule\Plugin\Catalog\Helper;

use \Smile\ElasticsuiteCatalog\Helper\Attribute as AttributeHelper;
use \Magento\Eav\Model\Entity\Attribute\AttributeInterface;

/**
 * ElasticsuiteCatalog attribute helper plugin
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTargetRule
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
class Attribute
{
    /**
     * Around - Parse attribute to get mapping field creation parameters.
     * Forces attributes with "is_used_for_promo_rules = 1" to be indexed in ES with the same structure
     * as "is_filterable/is_filterable_in_search = 1" attributes.
     *
     * @param AttributeHelper    $helper    ElasticsuiteCatalog attribute helper
     * @param \Closure           $proceed   Original method
     * @param AttributeInterface $attribute Product attribute.
     *
     * @return array Mapping options as an array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetMappingFieldOptions(
        AttributeHelper $helper,
        \Closure $proceed,
        AttributeInterface $attribute
    ) {
        $options = $proceed($attribute);

        if ($attribute->getIsUsedForPromoRules()) {
            $options['is_filterable'] = true;
        }

        return $options;
    }
}
