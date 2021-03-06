<?php



/**
 * xml output context validation class
 */

abstract class xmlValidator {


    /**
     * validate element schema attributes
     */

    public static function validateXmlElementSchemaAttributes($attributes) {


        if (!is_array($attributes)) {
            throw new systemErrorException("Schema XML error", "Attributes of schema element is not array");
        }

        foreach ($attributes as $attribute) {


            /**
             * check one attribute
             */

            if (!is_array($attribute)) {
                throw new systemErrorException("Schema XML error", "Attribute of element is not array");
            }

            if (!array_key_exists("name", $attribute)) {
                throw new systemErrorException("Schema XML error", "Name of attribute not found");
            }

            if (!array_key_exists("value", $attribute)) {
                throw new systemErrorException("Schema XML error", "Name of attribute not found");
            }


        }


    }


    /**
     * validate schema element structure
     */

    public static function validateXmlSchemaElement($schemaElement) {


        /**
         * check element
         */

        if (!is_array($schemaElement)) {
            throw new systemErrorException("Schema XML error", "Schema element is not array");
        }


        /**
         * check name of element
         */

        if (!array_key_exists("name", $schemaElement)) {
            throw new systemErrorException("Schema XML error", "Name of schema element not found");
        }


        /**
         * check attributes of element
         */

        if (array_key_exists("attributes", $schemaElement)) {
            self::validateXmlElementSchemaAttributes($schemaElement['attributes']);
        }


        /**
         * check children section
         * only if exists children key
         */

        $existsChildren = false;
        if (array_key_exists("children", $schemaElement)) {


            /**
             * check children
             */

            if (!is_array($schemaElement['children'])) {
                throw new systemErrorException("Schema XML error", "Children of element is not array");
            }

            foreach ($schemaElement['children'] as $element) {
                self::validateXmlSchemaElement($element);
            }


            /**
             * set exists children flag
             */

            $existsChildren = true;


        }


        /**
         * check value for element
         */

        if (array_key_exists("value", $schemaElement)) {


            if ($existsChildren) {
                throw new systemErrorException("Schema XML error", "Value of schema element can't be declared with children");
            }

            if (!validate::likeString($value)) {
                throw new systemErrorException("Schema XML error", "Value of schema element is not string");
            }


        }


    }


}



