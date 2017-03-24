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
use Smile\ElasticsuiteCatalogRule\Model\RuleFactory;
use Psr\Log\LoggerInterface;

/**
 * Target rule percolator indexer
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTargetRule
 * @author   Richard BAYET <richard.bayet@smile.fr>
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
     * Percolator constructor.
     * @param ClientFactoryInterface             $clientFactory   ES client factory
     * @param StoreManagerInterface              $storeManager    Store manager
     * @param IndexOperationInterface            $indexManager    ES index manager
     * @param \Magento\Framework\Stdlib\DateTime $dateTime        Datetime manipulation library/helper
     * @param ObjectManagerInterface             $objectManager   Object manager
     * @param RuleFactory                        $ruleFactory     ES catalog rule factory
     * @param QueryBuilder                       $queryBuilder    ES query builder
     * @param QueryFactory                       $queryFactory    ES query component factory
     * @param LoggerInterface                    $logger          Logger
     * @param string                             $indexIdentifier ES index name/identifier (as defined in XMLs)
     */
    public function __construct(
        ClientFactoryInterface $clientFactory,
        StoreManagerInterface $storeManager,
        IndexOperationInterface $indexManager,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        ObjectManagerInterface $objectManager,
        RuleFactory $ruleFactory,
        QueryBuilder $queryBuilder,
        QueryFactory $queryFactory,
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
        $this->logger           = $logger;
        $this->indexIdentifier  = $indexIdentifier;
    }

    /**
     * {@inheritdoc}
     */
    public function removeFromIndex($object)
    {
        $bulk = array();

        $indexNames = array();

        $storeIds = array_keys($this->storeManager->getStores());

        foreach ($storeIds as $storeId) {
            /** @var \Smile\ElasticsuiteCore\Api\Index\IndexInterface $index */
            $index = $this->indexManager->getIndexByName($this->indexIdentifier, $storeId);
            $indexNames[] = $index->getName();

            $docId = sprintf('%s_%d', self::PERCOLATOR_TYPE, $object->getId());
            $bulk['body'][] = [
                'delete' => ['_index' => $index->getName(), '_type' => '.percolator', '_id' => $docId],
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
     * @param \Magento\Framework\DataObject $object The rule based entity.
     *
     * @return void
     */
    public function reindex($object)
    {
        if (true) {
            /*
            $docs = $this->getEntityPercolator($object);
            $defaultStore = $this->storeManager->getDefaultStoreView();
            $index        = $this->indexManager->getIndexByName($this->indexIdentifier, $defaultStore);
            */

            $storeIds = array_keys($this->storeManager->getStores());

            foreach ($storeIds as $storeId) {
                // To propagate the store context from the target rule to the ES catalog rule (which might need it).
                $object->setStoreId($storeId);

                /** @var \Smile\ElasticsuiteCore\Api\Index\IndexInterface $index */
                $index = $this->indexManager->getIndexByName($this->indexIdentifier, $storeId);
                $docs  = $this->getEntityPercolator($object, $index);
                $this->logger->error(print_r($docs, true));
                $this->addDocuments($docs);

                // Not possible because I don't have a type declared or a TypeInterface impl.
                /*
                $this->indexManager->createBulk()->addDocuments(...)
                $this->indexOperation->executeBulk($bulk);
                ---
                    $bulk = $this->indexOperation->createBulk()->addDocuments($index, $type, $batchDocuments);
                    $this->indexOperation->executeBulk($bulk);
                */

                /*
                    $docs = $this->getEntityPercolator($object);
                    $this->_index->addDocuments($docs)->refresh();
                */
            }

            // $this->refreshIndex($index);
        }
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
        $docId = implode('_', [$data['percolator_type'], $data['rule_id']]); // , $data['website_id']]);
        // $docs  = array_merge($docs, $this->_index->createDocument($docId, $data, '.percolator'));
        $docs  = array_merge($docs, $this->createDocument($docId, $data, $index->getName(), '.percolator'));

        return $docs;

        // TODO update me : This part create a document for rule "actions" and rule "conditions" into the percolator.
        foreach ($percolatorQueryFilter as $filterType => $filter) {
            if (empty($filter)) {
                continue;
            }
            $data  = $this->getPercolatorRuleData($rule, [$filter]);
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
            throw($e);
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

        if ($rule->hasConditionsSerialized()) {
            /*
             * replace the Combine and Product condition models
             * "Magento\TargetRule\Model\Rule\Condition\Combine"
             *      -> "Smile\ElasticsuiteVirtualCategory\Model\Rule\Condition\Combine"
             * "Magento\TargetRule\Model\Rule\Condition\Product\Attributes" ->
             *      -> "Smile\ElasticsuiteVirtualCategory\Model\Rule\Condition\Product"
             *
             * TODO : compute this only once per rule, whatever the number of Magento stores
             * (store it at the $rule level ?)
             */
            $targetRuleConditions = unserialize($rule->getConditionsSerialized());

            $targetRuleConditions = json_encode($targetRuleConditions);
            $targetRuleConditions = str_replace(
                addslashes('Magento\TargetRule\Model\Rule\Condition\Combine'),
                addslashes('Smile\ElasticsuiteVirtualCategory\Model\Rule\Condition\Combine'),
                $targetRuleConditions
            );
            $targetRuleConditions = str_replace(
                addslashes('Magento\TargetRule\Model\Rule\Condition\Product\Attributes'),
                addslashes('Smile\ElasticsuiteVirtualCategory\Model\Rule\Condition\Product'),
                $targetRuleConditions
            );
            $targetRuleConditions = json_decode($targetRuleConditions, true);

            /** @var \Smile\ElasticsuiteCatalogRule\Model\Rule $catalogRule */
            $catalogRule = $this->ruleFactory->create();
            $catalogRule->setStoreId($rule->getStoreId());

            $catalogRule->getConditions()->loadArray($targetRuleConditions);

            $filter[self::CONDITIONS_TYPE] = $catalogRule->getConditions()->getSearchQuery();
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
    protected function getPercolatorRuleData($rule, $percolatorQueryFilter)
    {
        $filter = array_filter($percolatorQueryFilter);
        $percolatorQuery = ['match_all' => []];

        if (!empty($filter)) {
            if (count($filter) > 1) {
                // Join the two queries in a "must" clause/query.
                $filter = $this->queryFactory->create(QueryInterface::TYPE_BOOL, $filter);
            } else {
                $filter = current($filter);
            }
            // $percolatorQuery = ['query_string' => ['query' => $filter]];
            $percolatorQuery = $this->queryBuilder->buildQuery($filter);
        }

        $partialQuery = false;
        /*
         * To impl. or to remove : the query is NOT expressed as a query_string component,
         * but the argument could be mad that this still applies to our queries
         * ----
         * Prevent trying to index a query containing too many boolean clauses (> to ES default limit)
         * This is meant to ensure having all rules in the percolator, even if only partially.
         * ---
         * # @var Cultura_ElasticSearchRule_Helper_Data $helper #
        $helper = Mage::helper("cultura_elasticsearchrule");
        if (isset($percolatorQuery['query_string']['query'])) {
            if ($helper->hasTooManyClauses($percolatorQuery['query_string']['query'])) {
                $percolatorQuery = ['match_all' => []];
                $partialQuery = true;
            }
        }
        */

        $data = [
            'query'           => $percolatorQuery,
            'type'            => 'product',
            'percolator_type' => self::PERCOLATOR_TYPE,
            'rule_id'         => (int) $rule->getId(),
            'is_active'       => (bool) $rule->getIsActive(),
            'rule_name'       => $rule->getName(),
            'is_partial'      => $partialQuery,
            /*
            'from_date'       => Mage::helper('cultura_elasticsearchrule')->parseDate($rule->getFromDate()),
            'to_date'         => Mage::helper('cultura_elasticsearchrule')->parseDate($rule->getToDate()),
            */
        ];

        return $data;
    }
}
