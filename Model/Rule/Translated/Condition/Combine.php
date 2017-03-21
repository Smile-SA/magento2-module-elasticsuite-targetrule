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

// class Combine extends \Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Combine
class Combine extends \Smile\ElasticsuiteVirtualCategory\Model\Rule\Condition\Combine
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

    /**
     * {@inheritDoc}
     */
    public function loadAndTranslateArray($arr, $key = 'conditions')
    {
        $aggregator = isset($arr['aggregator']) ? $arr['aggregator'] : (isset($arr['attribute']) ? $arr['attribute'] : null);
        $value      = isset($arr['value']) ? $arr['value'] : (isset($arr['operator']) ? $arr['operator'] : null);

        $this->setAggregator($aggregator)
            ->setValue($value);

        // $this->setData($this->getPrefix(), []);

        if (!empty($arr[$key]) && is_array($arr[$key])) {
            foreach ($arr[$key] as $conditionArr) {
                try {
                    if ($conditionArr['type'] == 'Magento\TargetRule\Model\Rule\Condition\Combine') {
                        // $conditionArr['type'] = 'Smile\ElasticsuiteVirtualCategory\Model\Rule\Condition\Combine';
                        $conditionArr['type'] = 'Smile\ElasticsuiteTargetRule\Model\Rule\Translated\Condition\Combine';
                    } else {
                        // $conditionArr['type'] = 'Smile\ElasticsuiteVirtualCategory\Model\Rule\Condition\Product';
                        $conditionArr['type'] = 'Smile\ElasticsuiteTargetRule\Model\Rule\Translated\Condition\Product';
                    }
                    $condition = $this->_conditionFactory->create($conditionArr['type']);
                    $this->_logger->debug('---- ' . __METHOD__ . ' --- ');
                    $this->_logger->debug(get_class($condition));

                    $condition->setElementName($this->elementName);
                    $condition->setRule($this->getRule());
                    // $condition->setData($this->getPrefix(), []);
                    // Note: current prefix will be propaged in method addCondition
                    $this->addCondition($condition);
                    $condition->loadAndTranslateArray($conditionArr, $key);
                } catch (\Exception $e) {
                    $this->_logger->critical($e);
                }
            }
        }

        // $this->_logger->debug(print_r($this->getConditions(), true));

        return $this;
    }
}