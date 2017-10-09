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

namespace Smile\ElasticsuiteTargetRule\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Smile\ElasticsuiteCatalogRule\Model\RuleFactory;

/**
 * RuleConverter helper
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTargetRule
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
class RuleConverter extends AbstractHelper
{
    /**
     * @var \Smile\ElasticsuiteCatalogRule\Model\RuleFactory
     */
    protected $ruleFactory;

    /**
     * @var array
     */
    protected $conditionsMapping;

    /**
     * @var array
     */
    protected $actionsMapping;

    /**
     * Constructor.
     *
     * @param Context     $context           Helper context.
     * @param RuleFactory $ruleFactory       ES catalog rule factory
     * @param array       $conditionsMapping Target rule condition to catalog rule condition classes mapping
     * @param array       $actionsMapping    Target rule action to catalog rule condition classes mapping
     */
    public function __construct(Context $context, RuleFactory $ruleFactory, $conditionsMapping = [], $actionsMapping = [])
    {
        parent::__construct($context);
        $this->ruleFactory          = $ruleFactory;
        $this->conditionsMapping    = $conditionsMapping;
        $this->actionsMapping       = $actionsMapping;
    }

    /**
     * Returns en Elasticsuite catalog rule corresponding to the conditions of a given target rule.
     *
     * @param \Magento\TargetRule\Model\Rule $rule Target rule
     *
     * @return \Smile\ElasticsuiteCatalogRule\Model\Rule
     */
    public function getCatalogRuleFromConditions($rule)
    {
        /** @var \Smile\ElasticsuiteCatalogRule\Model\Rule $catalogRule */
        $catalogRule = $this->ruleFactory->create();
        $catalogRule->setStoreId($rule->getStoreId());

        if ($rule->hasConditionsSerialized()) {
            $targetRuleConditions = $rule->getConditionsSerialized();
            $targetRuleConditions = str_replace(
                array_map('addslashes', array_keys($this->conditionsMapping)),
                array_map('addslashes', array_values($this->conditionsMapping)),
                $targetRuleConditions
            );
            $targetRuleConditions = json_decode($targetRuleConditions, true);

            $catalogRule->getConditions()->loadArray($targetRuleConditions);
        }

        return $catalogRule;
    }

    /**
     * Returns en Elasticsuite catalog rule corresponding to the actions of a given target rule.
     * Warning: the "getSearchQuery" method on the resulting rule MUST NOT be called without providing
     * first an execution context (instance of \Magento\TargetRule\Model\Index)
     *
     * @param \Magento\TargetRule\Model\Rule $rule Target rule
     *
     * @return \Smile\ElasticsuiteCatalogRule\Model\Rule
     */
    public function getCatalogRuleFromActions($rule)
    {
        /** @var \Smile\ElasticsuiteCatalogRule\Model\Rule $catalogRule */
        $catalogRule = $this->ruleFactory->create();
        $catalogRule->setStoreId($rule->getStoreId());

        if ($rule->hasActionsSerialized()) {
            $targetRuleActions = $rule->getActionsSerialized();
            $targetRuleActions = str_replace(
                array_map('addslashes', array_keys($this->actionsMapping)),
                array_map('addslashes', array_values($this->actionsMapping)),
                $targetRuleActions
            );
            $targetRuleActions = json_decode($targetRuleActions, true);

            $catalogRule->getConditions()->loadArray($targetRuleActions);
        }

        return $catalogRule;
    }

    /**
     * Return the helper logger
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        $this->_logger;
    }
}
