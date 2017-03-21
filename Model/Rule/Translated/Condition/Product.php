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
 * @author    $FullName
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2017 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTargetRule\Model\Rule\Translated\Condition;

// class Product extends \Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product
class Product extends \Smile\ElasticsuiteVirtualCategory\Model\Rule\Condition\Product
{
    /**
     * Load array
     *
     * @param array $arr
     * @return $this
     */
    public function loadAndTranslateArray($arr, $key = 'conditions')
    {
        parent::loadArray($arr);

        return $this;
    }
}