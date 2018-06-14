<?php

namespace Effenti\Propel\Behavior;

use Propel\Generator\Model\Behavior;
use Propel\Generator\Util\PhpParser;

class RealEnumBehavior extends Behavior{

    public function modifyTable(){
        foreach($this->getTable()->getColumns() as $column){
            if($column->getType() == 'ENUM'){
                $sqlType = 'enum(';
                foreach($column->getValueSet() as $value){
                    $sqlType .= "'$value',";
                }

                $column->getDomain()->setSqlType(substr($sqlType, 0, -1).')');
                $column->getDomain()->setType("VARCHAR");
                $column->getDomain()->setDescription("RealEnum");
            }
        }
    }

    public function objectAttributes(){
        $attributes = "";

        foreach($this->getTable()->getColumns() as $column){
            if($column->getDomain()->getDescription() == 'RealEnum'){
                $column->setType('ENUM');
                foreach($column->getValueSet() as $value){
                    $attributes .= '
const '. $column->getUppercasedName() . '_' . preg_replace('/\s+/', '_', strtoupper($value)) . " = '$value';";
                }
            }
        }

        return $attributes;
    }

    public function queryMethods(){
        return "
/**
 * Converts value for some column types
 *
 * @param  mixed     \$value  The value to convert
 * @param  \\Propel\\Runtime\\Map\\ColumnMap \$colMap The ColumnMap object
 * @return mixed     The converted value
 */
protected function convertValueForColumn(\$value, \\Propel\\Runtime\\Map\\ColumnMap \$colMap)
{
    if ('ENUM' === \$colMap->getType() && !is_null(\$value)) {
        return \$value;
    } else {
        return parent::convertValueForColumn(\$value, \$colMap);
    }
}";
    }

    public function objectFilter(&$script){
        $parser = new PHPParser($script, true);


        foreach($this->getTable()->getColumns() as $column){
            if($column->getDomain()->getDescription() == 'RealEnum'){
                $parser->replaceMethod("set{$column->getPhpName()}", "
    /**
     * Set the value of [{$column->getLowercasedName()}] column.
     *
     * @param  string \$v new value
     * @return \$this|{$this->getTable()->getPhpName()} The current object (for fluent API support)
     * @throws \\Propel\\Runtime\\Exception\\PropelException
     */
    public function set{$column->getPhpName()}(\$v)
    {
        if (\$v !== null) {
            \$valueSet = {$this->getTable()->getPhpName()}TableMap::getValueSet({$this->getTable()->getPhpName()}TableMap::{$column->getConstantName()});
            if (!in_array(\$v, \$valueSet)) {
                throw new PropelException(sprintf('Value \"%s\" is not accepted in this enumerated column', \$v));
            }
        }

        if (\$this->{$column->getLowercasedName()} !== \$v) {
            \$this->{$column->getLowercasedName()} = \$v;
            \$this->modifiedColumns[{$this->getTable()->getPhpName()}TableMap::{$column->getConstantName()}] = true;
        }

        return \$this;
    }");
                $parser->replaceMethod("get{$column->getPhpName()}", "
    /**
     * Get the [{$column->getLowercasedName()}] column value.
     *
     * @return string
     * @throws \\Propel\\Runtime\\Exception\\PropelException
     */
    public function get{$column->getPhpName()}()
    {
        if (null === \$this->{$column->getLowercasedName()}) {
            return null;
        }
        \$valueSet = {$this->getTable()->getPhpName()}TableMap::getValueSet({$this->getTable()->getPhpName()}TableMap::{$column->getConstantName()});
        if (!in_array(\$this->{$column->getLowercasedName()}, \$valueSet)) {
            throw new PropelException('Unknown stored enum key: ' . \$this->{$column->getLowercasedName()});
        }

        return \$this->{$column->getLowercasedName()};
    }"
                );
            }
        }

        $script = $parser->getCode();
    }

    public function queryFilter(&$script){
        $parser = new PHPParser($script, true);

        $table = $this->getTable();
        foreach($table->getColumns() as $column){
            if($column->getDomain()->getDescription() == 'RealEnum'){
                $parser->replaceMethod("filterBy{$column->getPhpName()}", "

    /**
     * Filter the query on the {$column->getLowercasedName()} column
     *
     * @param     mixed \${$column->getLowercasedName()} The value to use as filter
     * @param     string \$comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return \$this|Child{$table->getPhpName()}Query The current query, for fluid interface
     */
    public function filterBy{$column->getPhpName()}(\${$column->getLowercasedName()} = null, \$comparison = null)
    {
        \$valueSet = {$table->getPhpName()}TableMap::getValueSet({$table->getPhpName()}TableMap::{$column->getConstantName()});
        if (is_scalar(\${$column->getLowercasedName()})) {
            if (!in_array(\${$column->getLowercasedName()}, \$valueSet)) {
                throw new PropelException(sprintf('Value \"%s\" is not accepted in this enumerated column', \${$column->getLowercasedName()}));
            }
        } elseif (is_array(\${$column->getLowercasedName()})) {
            foreach (\${$column->getLowercasedName()} as \$value) {
                if (!in_array(\$value, \$valueSet)) {
                    throw new PropelException(sprintf('Value \"%s\" is not accepted in this enumerated column', \$value));
                }
            }
            if (null === \$comparison) {
                \$comparison = Criteria::IN;
            }
        }

        return \$this->addUsingAlias({$table->getPhpName()}TableMap::{$column->getConstantName()}, \${$column->getLowercasedName()}, \$comparison);
    }");
            }
        }

        $script = $parser->getCode();
    }
}
