<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTargetRule
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTargetRule\Plugin\Index;

/**
 * Plugin to append standard index mapping into targetrule index.
 * Target Rule index must be aware of product-related indices mapping.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTargetRule
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class IndexOperation
{
    /**
     * @var \Smile\ElasticsuiteCore\Api\Client\ClientInterface
     */
    private $client;

    /**
     * IndexOperation constructor.
     *
     * @param \Smile\ElasticsuiteCore\Api\Client\ClientInterface $client Elasticsearch client
     */
    public function __construct(\Smile\ElasticsuiteCore\Api\Client\ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * @param \Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface $subject         Index Operation
     * @param \Smile\ElasticsuiteCore\Api\Index\IndexInterface          $result          Resulting index
     * @param string                                                    $indexIdentifier Index identifier
     * @param integer|string|\Magento\Store\Api\Data\StoreInterface     $store           Store (id, identifier or object).
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\IndexInterface
     */
    public function afterCreateIndex(
        \Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface $subject,
        \Smile\ElasticsuiteCore\Api\Index\IndexInterface $result,
        string $indexIdentifier,
        $store
    ) {
        // If we are rebuilding the targetrule index, we have to merge his mapping with the existing "product" mapping.
        if ($indexIdentifier === \Smile\ElasticsuiteTargetRule\Model\Indexer\TargetRule\Percolator::INDEX_IDENTIFIER) {
            if ($subject->indexExists('catalog_product',$store)) {
                $productIndex = $subject->getIndexByName('catalog_product', $store);
                $this->client->putMapping($result->getName(), '_doc', $productIndex->getMapping()->asArray());
            }
        }

        return $result;
    }
}
