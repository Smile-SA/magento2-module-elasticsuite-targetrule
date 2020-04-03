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
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response\QueryResponseFactory;
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
     * @var QueryResponseFactory
     */
    private $responseFactory;

    /**
     * Percolator constructor.
     *
     * @param ClientConfigurationInterface $clientConfiguration  Client configuration factory.
     * @param ClientBuilder                $clientBuilder        ES client builder.
     * @param StoreManagerInterface        $storeManager         Store manager
     * @param IndexOperationInterface      $indexManager         ES index manager
     * @param QueryResponseFactory         $queryResponseFactory Search Query Response Factory
     * @param LoggerInterface              $logger               Logger
     * @param string                       $indexIdentifier      ES index name/identifier (as defined in XMLs)
     */
    public function __construct(
        ClientConfigurationInterface $clientConfiguration,
        ClientBuilder $clientBuilder,
        StoreManagerInterface $storeManager,
        IndexOperationInterface $indexManager,
        QueryResponseFactory $queryResponseFactory,
        LoggerInterface $logger,
        $indexIdentifier = \Smile\ElasticsuiteTargetRule\Model\Indexer\TargetRule\Percolator::INDEX_IDENTIFIER
    ) {
        $this->client          = $clientBuilder->build($clientConfiguration->getOptions());
        $this->storeManager    = $storeManager;
        $this->indexManager    = $indexManager;
        $this->responseFactory = $queryResponseFactory;
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
        $index        = $this->indexManager->getIndexByName($this->indexIdentifier, $storeId);
        $productIndex = $this->indexManager->getIndexByName('catalog_product', $storeId);

        $percolatorQuery['percolate'] = [
            'field' => '_targetrule.query',
            'index' => $productIndex->getName(),
            'type'  => $productIndex->getDefaultSearchType()->getName(),
            'id'    => $productId,
        ];

        $percolatorFilter['query']['bool']['must'] = [
            ['term' => ['_targetrule.percolator_type' => PercolatorData::PERCOLATOR_TYPE]],
            // Also done in \Magento\TargetRule\Model\Index::getRuleCollection
            // but limits the amount of requests/data exchanged.
            ['term' => ['_targetrule.is_active' => true]],
            $percolatorQuery,
        ];

        \Magento\Framework\Profiler::start('ES:Percolate Document');
        try {
            $searchParams   = ['index' => $index->getName(), 'type' => '_doc', 'body' => $percolatorFilter];
            $searchResponse = $this->client->search($searchParams);
        } catch (\Exception $e) {
            $searchResponse = [];
            $this->logger->error($e->getMessage());
        }
        \Magento\Framework\Profiler::stop('ES:Percolate Document');

        $response = $this->responseFactory->create(['searchResponse' => $searchResponse]);
        foreach ($response->getIterator() as $document) {
            $ruleIds[] = $document->getId();
        }

        return $ruleIds;
    }
}
