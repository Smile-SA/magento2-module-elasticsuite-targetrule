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

use Magento\Framework\ObjectManager\ObjectManager;
use Magento\Framework\ObjectManagerInterface;
use Smile\ElasticsuiteCore\Api\Client\ClientFactoryInterface;
use Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface;
use Smile\ElasticsuiteCore\Api\Index\Bulk\BulkRequestInterface;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface;
use Smile\ElasticsuiteCore\Api\Index\IndexInterface;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder as QueryBuilder;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Psr\Log\LoggerInterface;


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
    private $indexName;

    /**
     * @var \Psr\Log\LoggerInterface;
     */
    private $logger;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder;
     */
    private $queryBuilder;

    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory
     */
    private $queryFactory;

    /**
     * Percolator constructor.
     * @param ClientFactoryInterface             $clientFactory ES Client Factory
     * @param StoreManagerInterface              $storeManager  Store manager
     * @param IndexOperationInterface            $indexManager  ES Index manager
     * @param \Magento\Framework\Stdlib\DateTime $dateTime      Datetime manipulation library/helper
     * @param LoggerInterface                    $logger        Logger
     * @param string $indexName
     */
    public function __construct(
        ClientFactoryInterface $clientFactory,
        StoreManagerInterface $storeManager,
        IndexOperationInterface $indexManager,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        ObjectManagerInterface $objectManager,
        LoggerInterface $logger,
        QueryBuilder $queryBuilder,
        QueryFactory $queryFactory,
        $indexName = 'catalog_product'
    ) {
        $this->client       = $clientFactory->createClient();
        $this->storeManager = $storeManager;
        $this->indexManager = $indexManager;
        $this->logger       = $logger;
        $this->indexName    = $indexName;
        $this->objectManager = $objectManager;
        $this->queryBuilder = $queryBuilder;
        $this->queryFactory = $queryFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function removeFromIndex($object)
    {
        $bulk = array();

        $indexNames = array();

        /*
        foreach (Mage::app()->getWebsites() as $website) {
            $docId = sprintf('sales_rule_%s_%s', $object->getId(), $website->getId());
            $bulk['body'][] = [
                'delete' => ['_index' => $this->_index->getCurrentName(), '_type' => '.percolator', '_id' => $docId]
            ];

            foreach ([self::ACTIONS_TYPE, self::CONDITIONS_TYPE] as $type) {
                $docId = implode("_", [$type, $object->getId(), $website->getId()]);
                $bulk['body'][] = [
                    'delete' => ['_index' => $this->_index->getCurrentName(), '_type' => '.percolator', '_id' => $docId]
                ];
            }
        }
        */

        $storeIds = array_keys($this->storeManager->getStores());

        foreach ($storeIds as $storeId) {
            /** @var \Smile\ElasticsuiteCore\Api\Index\IndexInterface $index */
            $index = $this->indexManager->getIndexByName($this->indexName, $storeId);
            $indexNames[] = $index->getName();

            $docId = sprintf('%s_%d', self::PERCOLATOR_TYPE, $object->getId());
            $bulk['body'][] = [
                'delete' => ['_index' => $index->getName(), '_type' => '.percolator', '_id' => $docId]
            ];

            /*
            foreach ([self::ACTIONS_TYPE, self::CONDITIONS_TYPE] as $type) {
                $docId = implode("_", [$type, $object->getId(), $website->getId()]);
                $bulk['body'][] = [
                    'delete' => ['_index' => $this->_index->getCurrentName(), '_type' => '.percolator', '_id' => $docId]
                ];
            }
            */
        }


        try {
            $this->client->bulk($bulk);

            // $this->_index->refresh();
            $this->client->indices()->refresh(implode(',', $indexNames));

        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     * @deprecated
     */
    public function refreshIndex(IndexInterface $index)
    {
        try {
            $this->client->indices()->refresh(['index' => $index->getName()]);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $this;
    }

    /**
     * Reindex a single target rule.
     *
     * @param Varien_Object $object The rule based entity.
     *
     * @return void
     */
    public function reindex($object)
    {
        if (true) {
            /*
            $docs = $this->_getEntityPercolator($object);
            $defaultStore = $this->storeManager->getDefaultStoreView();
            $index        = $this->indexManager->getIndexByName($this->indexName, $defaultStore);
            */

            $storeIds = array_keys($this->storeManager->getStores());

            foreach ($storeIds as $storeId) {
                /** @var \Smile\ElasticsuiteCore\Api\Index\IndexInterface $index */
                $index = $this->indexManager->getIndexByName($this->indexName, $storeId);
                $docs  = $this->_getEntityPercolator($object, $index);
                $this->logger->error(print_r($docs, true));
                $this->addDocuments($docs);

                // Not possible because I don't have a type declare or a TypeInterface impl.
                /*
                $this->indexManager->createBulk()->addDocuments(...)
                $this->indexOperation->executeBulk($bulk);

                ----
                public function addDocument(IndexInterface $index, TypeInterface $type, $docId, array $data)
                {
                    $this->bulkData[] = ['index' => ['_index' => $index->getName(), '_type' => $type->getName(), '_id' => $docId]];
                    $this->bulkData[] = $data;

                    return $this;
                }

                /*
                    $this->client->
                    $index->addDocuments($docs)->refresh();
                */

                /*
                    $bulk = $this->indexOperation->createBulk()->addDocuments($index, $type, $batchDocuments);
                    $this->indexOperation->executeBulk($bulk);
                 */

                /*
                    $docs = $this->_getEntityPercolator($object);
                    $this->_index->addDocuments($docs)->refresh();
                */
            }
        }
    }

    /**
     * Generate a bulk indexing query for a given target rule.
     *
     * @param \Magento\TargetRule\Model\Rule $                 rule   The target rule
     * @param \Smile\ElasticsuiteCore\Api\Index\IndexInterface $index The destination index
     *
     * @return array
     */
    protected function _getEntityPercolator($rule, $index)
    {
        $docs = [];

        $percolatorQueryFilter = $this->_getPercolatorQueryFilter($rule);

        // This part create a document for the whole rule into the percolator
        $data  = $this->_getPercolatorRuleData($rule, $percolatorQueryFilter);
        $docId = implode('_', [$data['percolator_type'], $data['rule_id']]); // , $data['website_id']]);
        // $docs  = array_merge($docs, $this->_index->createDocument($docId, $data, '.percolator'));
        $docs  = array_merge($docs, $this->createDocument($docId, $data, $index->getName(), '.percolator'));

        return $docs;

        // TODO update me : This part create a document for rule "actions" and rule "conditions" into the percolator
        foreach ($percolatorQueryFilter as $filterType => $filter) {
            if (empty($filter)) {
                continue;
            }
            $data  = $this->_getPercolatorRuleData($rule, [$filter]);
            $data['percolator_type'] = $filterType;
            $docId = implode('_', [$data['percolator_type'], $data['rule_id']]); // , $data['website_id']]);
            // $docs  = array_merge($docs, $this->_index->createDocument($docId, $data, '.percolator'));
            $docs  = array_merge($docs, $this->createDocument($docId, $data, $index->getName(), '.percolator'));
        }

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
            '_routing'  => $docId
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
    public function addDocuments(array $docs)
    {
        try {
            if (!empty($docs)) {
                $bulkParams = ['body' => $docs];
                $rawBulkResponse = $this->client->bulk($bulkParams);

                /**
                 * @var BulkResponseInterface
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
            throw($e);
        }

        return $this;
    }

    /**
     * Retrieve Percolator Query filter for a given rule-website couple.
     *
     * @param \Magento\TargetRule\Model\Rule $rule    Target rule
     *
     * @return array
     */
    protected function _getPercolatorQueryFilter($rule)
    {
        $filter = [];

        // TODO : replace me by getConditions()
        if ($rule->getConditionsSerialized()) {
            $this->logger->debug(get_class($rule->getConditions()));
            $this->logger->debug(gettype($rule->getConditions()->getConditions()));
            if (is_array($rule->getConditions()->getConditions())) {
                foreach ($rule->getConditions()->getConditions() as $condition) {
                    $this->logger->debug(gettype($condition));
                    if (is_object($condition)) {
                        $this->logger->debug(get_class($condition));
                    }
                }

            }

            $this->logger->debug('---------------------------------');
            $ruleConditions = $rule->getTranslatedConditions();
            $this->logger->debug(get_class($ruleConditions));
            $this->logger->debug(gettype($ruleConditions->getConditions()));
            if (is_array($ruleConditions->getConditions())) {
                foreach ($ruleConditions->getConditions() as $condition) {
                    $this->logger->debug(gettype($condition));
                    if (is_object($condition)) {
                        $this->logger->debug(get_class($condition));
                    }
                }
            }
            $this->logger->debug(print_r($ruleConditions->getSearchQuery(), true));
            $this->logger->debug(print_r($this->queryBuilder->buildQuery($ruleConditions->getSearchQuery()), true));


            // $this->logger->debug(print_r($rule->getConditions(), true));
            // die("STOP");

            // $filter[self::CONDITIONS_TYPE] = ['match_all' => []];
            // $filter[self::CONDITIONS_TYPE] = $rule->getConditions()->getSearchQuery(array(), false);
            // TODO : or delay query building ?
            // $filter[self::CONDITIONS_TYPE] = $this->queryBuilder->buildQuery($ruleConditions->getSearchQuery());
            $filter[self::CONDITIONS_TYPE] = $ruleConditions->getSearchQuery();
        }

        /*
        if ($rule->getActions()) {
            $filter[self::ACTIONS_TYPE] = $rule->getActions()->getSearchQuery(array(), false);
        }
        */

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
    protected function _getPercolatorRuleData($rule, $percolatorQueryFilter)
    {
        $filter = array_filter($percolatorQueryFilter);
        $percolatorQuery = ['match_all' => []];

        /*
        #** @var Cultura_ElasticSearchRule_Helper_Data $helper *#
        $helper = Mage::helper("cultura_elasticsearchrule");
        */

        if (!empty($filter)) {
            if (count($filter) > 1) {
                // TODO : refactor with the query building NOT producing query_string elements
                // $filter = '(' . implode(' AND ', $filter) . ')';
                // $filter = current($filter);
                // $this->queryFactory->create(QueryFactory::
                // $subQuery = $this->queryFactory->create(QueryInterface::TYPE_NOT, ['query' => $subQuery]);
                // join the two queries in a "must" clause/query
                $filter = $this->queryFactory->create(QueryInterface::TYPE_BOOL, $filter);
            } else {
                $filter = current($filter);
            }
            // $percolatorQuery = ['query_string' => ['query' => $filter]];
            $percolatorQuery = $this->queryBuilder->buildQuery($filter);
        }

        // Prevent trying to index a query containing too many boolean clauses (> to ES default limit)
        // This is meant to ensure having all rules in the percolator, even if only partially.
        $partialQuery = false;
        if (isset($percolatorQuery['query_string']['query'])) {
            // if ($helper->hasTooManyClauses($percolatorQuery['query_string']['query'])) {
            if (false) {
                $percolatorQuery = ['match_all' => []];
                $partialQuery = true;
            }
        }

        // TODO this is just for tests
        // $percolatorQuery = ['match_all' => []];

        $data = [
            'query'           => $percolatorQuery,
            'type'            => 'product',
            'percolator_type' => self::PERCOLATOR_TYPE,
            'rule_id'         => (int) $rule->getId(),
            'is_active'       => (bool) $rule->getIsActive(),
            'rule_name'       => $rule->getName(),
            'is_partial'      => $partialQuery/*,
            'from_date'       => Mage::helper('cultura_elasticsearchrule')->parseDate($rule->getFromDate()),
            'to_date'         => Mage::helper('cultura_elasticsearchrule')->parseDate($rule->getToDate()),
            */
        ];

        return $data;
    }
}