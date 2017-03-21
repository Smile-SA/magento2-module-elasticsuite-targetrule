<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTargetRule
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2017 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTargetRule\Model\Rule\Condition;

class Combine extends \Magento\TargetRule\Model\Rule\Condition\Combine
{
    /**
     * @param array $arr
     * @param string $key
     * @return $this
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @deprecated
     */
    public function _loadArray($arr, $key = 'conditions')
    {
        $this->setAggregator(
            isset($arr['aggregator']) ? $arr['aggregator'] : (isset($arr['attribute']) ? $arr['attribute'] : null)
        )->setValue(
            isset($arr['value']) ? $arr['value'] : (isset($arr['operator']) ? $arr['operator'] : null)
        );

        if (!empty($arr[$key]) && is_array($arr[$key])) {
            foreach ($arr[$key] as $conditionArr) {
                try {
                    if ($conditionArr['type'] == 'Magento\TargetRule\Model\Rule\Condition\Combine') {
                        $conditionArr['type'] = 'Smile\ElasticsuiteVirtualCategory\Model\Rule\Condition\Combine';
                    } else {
                        $conditionArr['type'] = 'Smile\ElasticsuiteVirtualCategory\Model\Rule\Condition\Product';
                    }
                    $condition = $this->_conditionFactory->create($conditionArr['type']);
                    $this->addCondition($condition);
                    $condition->loadArray($conditionArr, $key);
                } catch (\Exception $e) {
                    $this->_logger->critical($e);
                }
            }
        }
        return $this;
    }
}