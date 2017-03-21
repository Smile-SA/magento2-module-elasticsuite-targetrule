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

namespace Smile\ElasticsuiteTargetRule\Model;

use \Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Class Rule
 * @package Smile\ElasticsuiteTargetRule\Model
 *
 * @method string getTranslatedConditionsSerialized()
 * @method \Magento\TargetRule\Model\Rule setTranslatedConditionsSerialized(string $value)
 */
class Rule extends \Magento\TargetRule\Model\Rule
{
    /**
     * Translated rule factory
     *
     * var \Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\CombineFactory
     * @var \Smile\ElasticsuiteTargetRule\Model\Rule\Translated\Condition\CombineFactory
     */
    protected $translatedRuleFactory;

    /**
     * Store rule translated combine conditions model
     *
     * var \Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Combine
     * @var \Smile\ElasticsuiteTargetRule\Model\Rule\Translated\Condition\Combine
     */
    protected $translatedConditions;

    /**
     * Rule constructor.
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\TargetRule\Model\Rule\Condition\CombineFactory $ruleFactory
     * @param \Magento\TargetRule\Model\Actions\Condition\CombineFactory $actionFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor $ruleProductIndexerProcessor
     * @param \Magento\Rule\Model\Condition\Sql\Builder $sqlBuilder
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     * @param \Smile\ElasticsuiteTargetRule\Model\Rule\Translated\Condition\CombineFactory $combineConditionsFactory
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\TargetRule\Model\Rule\Condition\CombineFactory $ruleFactory,
        \Magento\TargetRule\Model\Actions\Condition\CombineFactory $actionFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor $ruleProductIndexerProcessor,
        \Magento\Rule\Model\Condition\Sql\Builder $sqlBuilder,
        // \Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\CombineFactory $combineConditionsFactory
        \Smile\ElasticsuiteTargetRule\Model\Rule\Translated\Condition\CombineFactory $combineConditionsFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $localeDate,
            $ruleFactory,
            $actionFactory,
            $productFactory,
            $ruleProductIndexerProcessor,
            $sqlBuilder,
            $resource,
            $resourceCollection,
            $data
        );
        $this->translatedRuleFactory = $combineConditionsFactory;
    }

    /**
     * Processing object after load data
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        // original conditions_serialized data might be destroyed after call to getConditions()
        $this->setTranslatedConditionsSerialized($this->getConditionsSerialized());
        return parent::_afterLoad();
    }

    /**
     * Getter for rule translated combine conditions instance
     *
     * return \Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Combine
     * @return \Smile\ElasticsuiteTargetRule\Model\Rule\Translated\Condition\Combine
     */
    public function getTranslatedConditionsInstance()
    {
        return $this->translatedRuleFactory->create();
    }

    /**
     * Set rule translated combine conditions model
     *
     * param \Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Combine $conditions
     * @param \Smile\ElasticsuiteTargetRule\Model\Rule\Translated\Condition\Combine $conditions
     * @return $this
     */
    public function setTranslatedConditions($conditions)
    {
        $this->translatedConditions = $conditions;
        return $this;
    }

    /**
     * Reset rule translated combine conditions
     *
     * param null|\Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Combine $conditions
     * param null|\Smile\ElasticsuiteTargetRule\Model\Rule\Translated\Condition\Combine $conditions
     * @return $this
     */
    protected function resetTranslatedConditions($conditions = null)
    {
        if (null === $conditions) {
            $conditions = $this->getTranslatedConditionsInstance();
        }
        // TODO change prefix ?
        // $conditions->setRule($this)->setId('1')->setPrefix('conditions');
        $conditions->setRule($this)->setId('1')->setPrefix('translated');
        // Force the model to initialize an empty conditions array according to prefix
        $conditions->setConditions([]);
        $this->setTranslatedConditions($conditions);

        return $this;
    }

    /**
     * Retrieve translated rule combine conditions model
     *
     * @return \Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Combine
     */
    public function getTranslatedConditions()
    {
        if (empty($this->translatedConditions)) {
            $this->resetTranslatedConditions();
        }

        // Load rule conditions if it is applicable
        if ($this->hasTranslatedConditionsSerialized()) {
            $conditions = $this->getTranslatedConditionsSerialized();
            if (!empty($conditions)) {
                $conditions = unserialize($conditions);
                if (is_array($conditions) && !empty($conditions)) {
                    $this->translatedConditions->loadAndTranslateArray($conditions);
                }
            }
            $this->unsTranslatedConditionsSerialized();
        }

        return $this->translatedConditions;
    }

    /**
     * Build a search query for the current rule.
     *
     * @return QueryInterface
     */
    public function getSearchQuery()
    {
        $query      = null;
        /** @var \Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Combine $conditions */
        $conditions = $this->getTranslatedConditions();

        if ($conditions) {
            $query = $conditions->getSearchQuery();
        }

        return $query;
    }
}