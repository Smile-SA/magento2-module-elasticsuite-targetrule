<?php

namespace Smile\ElasticsuiteTargetRule\Helper;
class VersionChecker
{
    public function __construct(
        \Magento\Framework\App\ProductMetadataInterface $productMetadata
    )
    {
        $this->productMetadata = $productMetadata;
    }

    public function unserializeField($field, $conditionMapping)
    {
        if (version_compare($this->getMagentoVersion(), "2.2", "lt")) {
            $targetRuleConditions = unserialize($field);
            $targetRuleConditions = json_encode($targetRuleConditions);
            $targetRuleConditions = str_replace(
                array_map('addslashes', array_keys($conditionMapping)),
                array_map('addslashes', array_values($conditionMapping)),
                $targetRuleConditions
            );
            $targetRuleConditions = json_decode($targetRuleConditions, true);
        } else {
            $targetRuleConditions = json_encode($field);
        }
        return $targetRuleConditions;
    }

    public function getMagentoVersion()
    {
        return $this->productMetadata->getVersion();
    }
}