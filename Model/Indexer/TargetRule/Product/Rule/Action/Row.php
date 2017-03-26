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

namespace Smile\ElasticsuiteTargetRule\Model\Indexer\TargetRule\Product\Rule\Action;

use \Smile\ElasticsuiteTargetRule\Model\Indexer\TargetRule\AbstractAction;

/**
 * Class Row reindex action
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTargetRule
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
class Row extends AbstractAction
{
    /**
     * Execute Row reindex
     *
     * @param int|null $productId Product ID
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @return void
     */
    public function execute($productId = null)
    {
        if (!isset($productId) || empty($productId)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('We can\'t rebuild the index for an undefined product.')
            );
        }
        try {
            $this->_reindexByProductId($productId);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()), $e);
        }
    }
}
