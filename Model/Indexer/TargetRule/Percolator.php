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

namespace Smile\ElasticsuiteTargetRule\Model\Indexer\TargetRule;

use Magento\Framework\ObjectManagerInterface;
use Smile\ElasticsuiteCore\Api\Client\ClientFactoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder as QueryBuilder;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCatalogRule\Model\RuleFactory;
use Smile\ElasticsuiteTargetRule\Helper\RuleConverter;
use Psr\Log\LoggerInterface;

/**
 * Target rule percolator indexer
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTargetRule
 * @author   Richard BAYET <richard.bayet@smile.fr>
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Percolator
{
    /**
     * The percolator type for this entity
     */
    const PERCOLATOR_TYPE = 'target_rule';

    /**
     * The percolator id prefix for rule conditions
     */
    const CONDITIONS_TYPE = 'target_rule_conditions';

    /**
     * (Future, unused) The percolator id prefix for actions conditions
     */
    const ACTIONS_TYPE = 'target_rule_actions';

    /**
     * @var \Elasticsearch\Client
     */
    private $client;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface
     */
    private $indexManager;

    /**
     * @var string
     */
    private $indexIdentifier;

    /**
     * @var \Psr\Log\LoggerInterface;
     */
    private $logger;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \Smile\ElasticsuiteCatalogRule\Model\RuleFactory
     */
    private $ruleFactory;

    /**
     * @var \Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder;
     */
    private $queryBuilder;

    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory
     */
    private $queryFactory;

    /**
     * @var \Smile\ElasticsuiteTargetRule\Helper\RuleConverter
     */
    private $ruleConverter;

    /**
     * Percolator constructor.
     * @param ClientFactoryInterface  $clientFactory   ES client factory
     * @param StoreManagerInterface   $storeManager    Store manager
     * @param IndexOperationInterface $indexManager    ES index manager
     * @param ObjectManagerInterface  $objectManager   Object manager
     * @param RuleFactory             $ruleFactory     ES catalog rule factory
     * @param QueryBuilder            $queryBuilder    ES query builder
     * @param QueryFactory            $queryFactory    ES query component factory
     * @param RuleConverter           $ruleConverter   Target rule to Catalog rule converter helper
     * @param LoggerInterface         $logger          Logger
     * @param string                  $indexIdentifier ES index name/identifier (as defined in XMLs)
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ClientFactoryInterface $clientFactory,
        StoreManagerInterface $storeManager,
        IndexOperationInterface $indexManager,
        ObjectManagerInterface $objectManager,
        RuleFactory $ruleFactory,
        QueryBuilder $queryBuilder,
        QueryFactory $queryFactory,
        RuleConverter $ruleConverter,
        LoggerInterface $logger,
        $indexIdentifier = 'catalog_product'
    ) {
        $this->client           = $clientFactory->createClient();
        $this->storeManager     = $storeManager;
        $this->indexManager     = $indexManager;
        $this->objectManager    = $objectManager;
        $this->ruleFactory      = $ruleFactory;
        $this->queryBuilder     = $queryBuilder;
        $this->queryFactory     = $queryFactory;
        $this->ruleConverter    = $ruleConverter;
        $this->logger           = $logger;
        $this->indexIdentifier  = $indexIdentifier;
    }

    /**
     * Remove the percolator(s) associated with a given target rule
     * (Note: unused)
     *
     * @param \Magento\TargetRule\Model\Rule $rule Target rule
     *
     * @return $this
     */
    public function removeFromIndex(\Magento\TargetRule\Model\Rule $rule)
    {
        $bulk = [];
        $indexNames = [];

        $storeIds = array_keys($this->storeManager->getStores());

        foreach ($storeIds as $storeId) {
            /** @var \Smile\ElasticsuiteCore\Api\Index\IndexInterface $index */
            $index = $this->indexManager->getIndexByName($this->indexIdentifier, $storeId);
            $indexNames[] = $index->getName();

            $docId = sprintf('%s_%d', self::PERCOLATOR_TYPE, $rule->getId());
            $bulk['body'][] = [
                'delete' => ['_index' => $index->getName(), '_type' => '.percolator', '_id' => $docId],
            ];
        }

        try {
            $this->client->bulk($bulk);

            $this->client->indices()->refresh(implode(',', $indexNames));
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $this;
    }

    /**
     * Refresh one or several ElasticSearch indices by name
     *
     * @param string[] $indices Array of ES index names
     *
     * @return $this
     */
    public function refreshIndex(array $indices)
    {
        if (!empty($indices)) {
            try {
                $this->client->indices()->refresh(['index' => implode(',', $indices)]);
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }

        return $this;
    }

    /**
     * Reindex a single target rule.
     *
     * @param \Magento\TargetRule\Model\Rule $rule The target rule.
     *
     * @return void
     */
    public function reindex($rule)
    {
        $storeIds = array_keys($this->storeManager->getStores());

        $docs = [];
        $indices = [];

        foreach ($storeIds as $storeId) {
            // To propagate the store context from the target rule to the ES catalog rule (which might need it).
            $rule->setStoreId($storeId);

            /** @var \Smile\ElasticsuiteCore\Api\Index\IndexInterface $index */
            $index = $this->indexManager->getIndexByName($this->indexIdentifier, $storeId);
            $indices[] = $index->getName();
            $docs  = array_merge($docs, $this->getEntityPercolator($rule, $index));
        }
        $this->addDocuments($docs);
        $this->refreshIndex($indices);
    }

    /**
     * Generate a bulk indexing query for a given target rule.
     *
     * @param \Magento\TargetRule\Model\Rule                   $rule  The target rule
     * @param \Smile\ElasticsuiteCore\Api\Index\IndexInterface $index The destination index
     *
     * @return array
     */
    protected function getEntityPercolator($rule, $index)
    {
        $docs = [];

        $percolatorQueryFilter = $this->getPercolatorQueryFilter($rule);

        // This part create a document for the whole rule into the percolator.
        $data  = $this->getPercolatorRuleData($rule, $percolatorQueryFilter);
        $docId = implode('_', [$data['percolator_type'], $data['rule_id']]);
        $docs  = array_merge($docs, $this->createDocument($docId, $data, $index->getName(), '.percolator'));

        return $docs;
    }

    /**
     * Create document to index.
     *
     * @param string $docId     Document Id
     * @param array  $data      Data indexed
     * @param string $indexName ES index name
     * @param string $type      Document type
     *
     * @return array Array representation of the bulk document
     */
    protected function createDocument($docId, $data, $indexName, $type = '.percolator')
    {
        $headerData = [
            '_index'    => $indexName,
            '_type'     => $type,
            '_id'       => $docId,
            '_routing'  => $docId,
        ];

        if (isset($data['_parent'])) {
            $headerData['_parent'] = $data['_parent'];
        }

        $headerRow = ['index' => $headerData];
        $dataRow = $data;

        $result = [$headerRow, $dataRow];

        return $result;
    }

    /**
     * Bulk document insert
     *
     * @param array $docs Document prepared with createDocument method
     *
     * @return self Self reference
     *
     * @throws \Exception
     */
    protected function addDocuments(array $docs)
    {
        try {
            if (!empty($docs)) {
                $bulkParams = ['body' => $docs];
                $rawBulkResponse = $this->client->bulk($bulkParams);

                /**
                 * @var \Smile\ElasticsuiteCore\Api\Index\Bulk\BulkResponseInterface $bulkResponse
                 */
                $bulkResponse = $this->objectManager->create(
                    'Smile\ElasticsuiteCore\Api\Index\Bulk\BulkResponseInterface',
                    ['rawResponse' => $rawBulkResponse]
                );

                if ($bulkResponse->hasErrors()) {
                    foreach ($bulkResponse->aggregateErrorsByReason() as $error) {
                        $sampleDocumentIds = implode(', ', array_slice($error['document_ids'], 0, 10));
                        $errorMessages = [
                            sprintf(
                                "Bulk %s operation failed %d times in index %s for type %s",
                                $error['operation'],
                                $error['count'],
                                $error['index'],
                                $error['document_type']
                            ),
                            sprintf(
                                "Error (%s) : %s",
                                $error['error']['type'],
                                $error['error']['reason']
                            ),
                            sprintf(
                                "Failed doc ids sample : %s",
                                $sampleDocumentIds
                            ),
                        ];
                        $this->logger->error(implode(" ", $errorMessages));
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $this;
    }

    /**
     * Retrieve Percolator Query filter for a given rule-website couple.
     *
     * @param \Magento\TargetRule\Model\Rule $rule Target rule
     *
     * @return array
     */
    protected function getPercolatorQueryFilter($rule)
    {
        $filter = [];

        /** @var \Smile\ElasticsuiteCatalogRule\Model\Rule $catalogRule */
        $catalogRule = $this->ruleConverter->getCatalogRuleFromConditions($rule);
        $filter[self::CONDITIONS_TYPE] = $catalogRule->getConditions()->getSearchQuery();

        return $filter;
    }

    /**
     * Build Rule Percolator document data.
     *
     * @param \Magento\TargetRule\Model\Rule $rule                  Target rule
     * @param array                          $percolatorQueryFilter An array containing combination of query filters
     *
     * @return array
     */
    protected function getPercolatorRuleData($rule, $percolatorQueryFilter)
    {
        $filter = array_filter($percolatorQueryFilter);
        $percolatorQuery = ['match_all' => []];

        if (!empty($filter)) {
            $filterQuery = current($filter);
            if (count($filter) > 1) {
                // Join the two queries in a "must" clause/query.
                $filterQuery = $this->queryFactory->create(QueryInterface::TYPE_BOOL, ['must' => $filter]);
            }
            $percolatorQuery = $this->queryBuilder->buildQuery($filterQuery);
        }

        $data = [
            'query'           => $percolatorQuery,
            'type'            => 'product',
            'percolator_type' => self::PERCOLATOR_TYPE,
            'rule_id'         => (int) $rule->getId(),
            'is_active'       => (bool) $rule->getIsActive(),
            'rule_name'       => $rule->getName(),
            'apply_to'        => $rule->getApplyTo(),
        ];

        return $data;
    }
}
