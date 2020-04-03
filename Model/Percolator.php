<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTargetRule
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2017 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTargetRule\Model;

use Smile\ElasticsuiteCore\Api\Client\ClientConfigurationInterface;
use \Magento\Store\Model\StoreManagerInterface;
use \Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface;
use Smile\ElasticsuiteCore\Client\ClientBuilder;
use \Smile\ElasticsuiteTargetRule\Model\Indexer\TargetRule\Percolator\Datasource\PercolatorData;
use \Psr\Log\LoggerInterface;

/**
 * Target rules percolator
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTargetRule
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
class Percolator
{
    /**
     * @var \Elasticsearch\Client
     */
    protected $client;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface
     */
    protected $indexManager;

    /**
     * @var \Psr\Log\LoggerInterface;
     */
    protected $logger;

    /**
     * @var string
     */
    protected $indexIdentifier;

    /**
     * Percolator constructor.
     *
     * @param ClientConfigurationInterface $clientConfiguration Client configuration factory.
     * @param ClientBuilder                $clientBuilder       ES client builder.
     * @param StoreManagerInterface        $storeManager        Store manager
     * @param IndexOperationInterface      $indexManager        ES index manager
     * @param LoggerInterface              $logger              Logger
     * @param string                       $indexIdentifier     ES index name/identifier (as defined in XMLs)
     */
    public function __construct(
        ClientConfigurationInterface $clientConfiguration,
        ClientBuilder $clientBuilder,
        StoreManagerInterface $storeManager,
        IndexOperationInterface $indexManager,
        LoggerInterface $logger,
        $indexIdentifier = 'catalog_product'
    ) {
        $this->client          = $clientBuilder->build($clientConfiguration->getOptions());
        $this->storeManager    = $storeManager;
        $this->indexManager    = $indexManager;
        $this->logger          = $logger;
        $this->indexIdentifier = $indexIdentifier;
    }

    /**
     * Get the IDs of target rules whose conditions a given product matches
     *
     * @param int      $productId Product ID to find matching rules for
     * @param int|null $storeId   Store ID
     *
     * @return int[]
     */
    public function getMatchingRuleIds($productId, $storeId = null)
    {
        $ruleIds = [];

        if (null === $storeId) {
            $storeId = $this->storeManager->getStore()->getId();
        }
        /** @var \Smile\ElasticsuiteCore\Api\Index\IndexInterface $index */
        $index = $this->indexManager->getIndexByName($this->indexIdentifier, $storeId);

        try {
            $matches = $this->client->percolate(
                [
                    'index' => $index->getName(),
                    'type'  => 'product',
                    'id'    => $productId,
                    'body'  => [
                        'filter' => [
                            'and' => [
                                ['term' => ['percolator_type' => PercolatorData::PERCOLATOR_TYPE]],
                                // Also done in \Magento\TargetRule\Model\Index::getRuleCollection
                                // but limits the amount of requests/data exchanged.
                                ['term' => ['is_active' => true]],
                            ],
                        ],
                        'percolate_format' => 'ids',
                    ],
                ]
            );

            // $this->logger->debug('Percolator matches id -------------');
            // $this->logger->debug(print_r($matches, true));
            foreach ($matches['matches'] as $match) {
                $percolationData = $this->client->get(
                    ['index' => $index->getName(), 'type' => '.percolator', 'id' => $match['_id']]
                );
                $ruleIds[]       = (int) $percolationData['_source']['rule_id'];
            }

            // $this->logger->debug('Matches rule ids  -------------');
            // $this->logger->debug(print_r($ruleIds, true));
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $ruleIds;
    }
}
