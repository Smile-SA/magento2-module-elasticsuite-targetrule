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

namespace Smile\ElasticsuiteTargetRule\Plugin\Core\Index\Mapping;

use \Smile\ElasticsuiteCore\Index\Mapping\Field as MappingField;
use \Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;

/**
 * Field plugin to fix the mapping produced by \Smile\ElasticsuiteCore\Index\Mapping\Field::getPropertyConfig
 * for fields of type "object".
 * => ElasticSearch does not support "doc_values" and "norms" on them.
 *
 * @package Smile\ElasticsuiteTargetRule
 */
class Field
{
    /**
     * After - Return ES mapping properties associated with the field.
     * Removes "doc_values" dans "norms" from the properties for fields of type "object"
     *
     * @param MappingField $field      The field declared in XML index config
     * @param array        $properties ES mapping properties associated with the field.
     * @return array
     */
    public function afterGetMappingPropertyConfig(MappingField $field, array $properties)
    {
        if ($field->getType() == FieldInterface::FIELD_TYPE_OBJECT) {
            unset($properties['doc_values']);
            unset($properties['norms']);
        }

        return $properties;
    }
}
