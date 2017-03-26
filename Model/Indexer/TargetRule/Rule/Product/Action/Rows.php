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

namespace Smile\ElasticsuiteTargetRule\Model\Indexer\TargetRule\Rule\Product\Action;

use \Smile\ElasticsuiteTargetRule\Model\Indexer\TargetRule\AbstractAction;

/**
 * Class Rows reindex action for mass actions
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTargetRule
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
class Rows extends AbstractAction
{
    /**
     * Execute Rows reindex
     *
     * @param array $ruleIds Rule IDs
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @return void
     */
    public function execute($ruleIds)
    {
        if (empty($ruleIds)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Could not rebuild index for empty products array')
            );
        }
        try {
            $this->_reindexByRuleIds($ruleIds);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()), $e);
        }
    }
}
