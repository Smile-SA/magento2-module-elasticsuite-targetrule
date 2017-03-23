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

namespace Smile\ElasticsuiteTargetRule\Model\ResourceModel;

/**
 * Rewrite of TargetRule Product Index by Rule Product List Type Resource Model :
 * - builds and executes an Elasticsearch query instead of relying on the SQL DB when interpreting the rule 'actions'
 *   (the list of products to be provided/displayed by the rule)
 *
 * @category Smile\ElasticsuiteTargetRule
 * @package  Smile\ElasticsuiteTargetRule\Model\ResourceModel
 */
class Index extends \Magento\TargetRule\Model\ResourceModel\Index
{
    /**
     * Retrieve found product ids by Rule action conditions
     * If rule has cached select - get it
     *
     * @param \Magento\TargetRule\Model\Rule  $rule              Target rule
     * @param \Magento\TargetRule\Model\Index $object            Target rules index accessor (also contains the contextual Product)
     * @param int                             $limit             Max number of product IDs to return
     * @param array                           $excludeProductIds IDs of products to ignore/not to return
     * @return array
     */
    protected function _getProductIdsByRule($rule, $object, $limit, $excludeProductIds = [])
    {
        $rule->afterLoad();

        /* @var $collection \Magento\Catalog\Model\ResourceModel\Product\Collection */
        $collection = $this->_productCollectionFactory->create()->setStoreId(
            $object->getStoreId()
        )->addPriceData(
            $object->getCustomerGroupId()
        )->setVisibility(
            $this->_visibility->getVisibleInCatalogIds()
        );

        $actionSelect = $rule->getActionSelect();
        $actionBind = $rule->getActionSelectBind();

        if ($actionSelect === null) {
            $actionBind = [];
            $actionSelect = $rule->getActions()->getConditionForCollection($collection, $object, $actionBind);
            $rule->setActionSelect((string)$actionSelect)->setActionSelectBind($actionBind)->save();
        }

        if ($actionSelect) {
            $collection->getSelect()->where($actionSelect);
        }
        if ($excludeProductIds) {
            $collection->addFieldToFilter('entity_id', ['nin' => $excludeProductIds]);
        }

        $select = $collection->getSelect();
        $select->reset(\Magento\Framework\DB\Select::COLUMNS);
        $select->columns('entity_id', 'e');
        $select->limit($limit);

        $bind = $this->_prepareRuleActionSelectBind($object, $actionBind);
        $result = $this->getConnection()->fetchCol($select, $bind);

        return $result;
    }
}