<?php
/**
 * Phinx
 *
 * (The MIT license)
 * Copyright (c) 2012 Rob Morgan
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated * documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 * 
 * @package    Phinx
 * @subpackage Phinx\Db
 */
namespace Phinx\Db;

use Phinx\Db\Table\Column,
    Phinx\Db\Table\Index,
    Phinx\Db\Adapter\AdapterInterface;

/**
 *
 * This object is based loosely on: http://api.rubyonrails.org/classes/ActiveRecord/ConnectionAdapters/Table.html.
 */
class Table
{
    /**
     * @var string
     */
    protected $name;
    
    /**
     * @var array
     */
    protected $options = array();
    
    /**
     * @var AdapterInterface
     */
    protected $adapter;
    
    /**
     * @var array
     */
    protected $columns = array();
    
    /**
     * @var array
     */
    protected $indexes = array();
    
    /**
     * Class Constuctor.
     *
     * @param string $name Table Name
     * @param array $options Options
     * @param AdapterInterface $adapter Database Adapter
     * @return void
     */
    public function __construct($name, $options = array(), AdapterInterface $adapter = null)
    {
        $this->setName($name);
        $this->setOptions($options);
        
        if (null !== $adapter) {
            $this->setAdapter($adapter);
        }
    }
    
    /**
     * Sets the table name.
     *
     * @param string $name Table Name
     * @return Table
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
    
    /**
     * Gets the table name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * Sets the table options.
     * 
     * @param array $options
     * @return Table
     */
    public function setOptions($options)
    {
        $this->options = $options;
        return $this;
    }
    
    /**
     * Gets the table options.
     * 
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }
    
    /**
     * Sets the database adapter.
     *
     * @param AdapterInterface $adapter Database Adapter
     * @return Table
     */
    public function setAdapter(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
        return $this;
    }
    
    /**
     * Gets the database adapter.
     *
     * @return AdapterInterface
     */
    public function getAdapter()
    {
        return $this->adapter;
    }
    
    /**
     * Does the table exist?
     *
     * @return boolean
     */
    public function exists()
    {
        return $this->getAdapter()->hasTable($this->getName());
    }
    
    /**
     * Drops the database table.
     *
     * @return void
     */
    public function drop()
    {
        $this->getAdapter()->dropTable($this->getName());
    }
    
    /**
     * Renames the database table.
     *
     * @param string $newTableName New Table Name
     * @return Table
     */
    public function rename($newTableName)
    {
        $this->getAdapter()->renameTable($this->getName(), $newTableName);
        $this->setName($newTableName);
        return $this;
    }
    
    /**
     * Sets an array of columns waiting to be committed.
     *
     * @param array $columns Columns
     * @return Table
     */
    public function setColumns($columns)
    {
        $this->columns = $columns;
        return $this;
    }
    
    /**
     * Gets an array of columns waiting to be committed.
     *
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }
    
    /**
     * Sets an array of columns waiting to be indexed.
     *
     * @param array $indexes Indexes
     * @return Table
     */
    public function setIndexes($indexes)
    {
        $this->indexes = $indexes;
        return $this;
    }
    
    /**
     * Gets an array of indexes waiting to be committed.
     * 
     * @return array
     */
    public function getIndexes()
    {
        return $this->indexes;
    }
    
    /**
     * Resets all of the pending table changes.
     *
     * @return void
     */
    public function reset()
    {
        $this->setColumns(array());
        $this->setIndexes(array());
    }
    
    /**
     * Add a table column.
     *
     * Type can be: primary_key, string, text, integer, float, decimal, datetime, timestamp, time, date, binary, boolean.
     * 
     * Valid options can be: limit, default, null, precision or scale.
     *
     * @param string|Phinx\Db\Table\Column $columnName Column Name
     * @param string $type Column Type
     * @param array $options Column Options
     * @return Table
     */
    public function addColumn($columnName, $type = null, $options = array())
    {
        // we need an adapter set to add a column
        if (null === $this->getAdapter()) {
            throw new \RuntimeException('An adapter must be specified to add a column.');
        }
        
        // create a new column object if only strings were supplied
        if (!$columnName instanceof Column) {
            $column = new Column();
            $column->setName($columnName);
            $column->setType($type);
            $column->setOptions($options); // map options to column methods
        } else {
            $column = $columnName;
        }
        
        // check column type
        if (!in_array($column->getType(), $this->getAdapter()->getColumnTypes())) {
            throw new \InvalidArgumentException('An invalid column type was specified.');
        }
        
        $this->columns[] = $column;
        return $this;
    }
    
    /**
     * Remove a table column.
     *
     * @param string $columnName Column Name
     * @return Table
     */
    public function removeColumn($columnName)
    {
        $this->getAdapter()->dropColumn($this->getName(), $columnName);
        return $this;
    }
    
    /**
     * Rename a table column.
     *
     * @param string $oldName Old Column Name
     * @param string $newName New Column Name
     * @return Table
     */
    public function renameColumn($oldName, $newName)
    {
        $this->getAdapter()->renameColumn($this->getName(), $oldName, $newName);
        return $this;
    }
    
    /**
     * Change a table column type.
     *
     * @param string $columnName Column Name
     * @param Column $newColumn  New Column
     * @return Table
     */
    public function changeColumn($columnName, Column $newColumn)
    {
        // if the name was omitted use the existing column name
        if (null === $newColumn->getName() || strlen($newColumn->getName()) == 0) {
            $newColumn->setName($columnName);
        }
        
        $this->getAdapter()->changeColumn($this->getName(), $columnName, $newColumn);
        return $this;
    }
    
    /**
     * Checks to see if a column exists.
     *
     * @param string $columnName Column Name
     * @param array $options Options
     * @return boolean
     */
    public function hasColumn($columnName, $options = array())
    {
        return $this->getAdapter()->hasColumn($this->getName(), $columnName, $options);
    }
    
    /**
     * Add an index to a database table.
     * 
     * In $options you can specific unique = true/false or name (index name).
     *
     * @param string|array|Index $columns Table Column(s)
     * @param array $options Index Options
     * @return Table
     */
    public function addIndex($columns, $options = array())
    {
        // create a new index object if strings or an array of strings were supplied
        if (!$columns instanceof Index) {
            $index = new Index();
            if (is_string($columns)) {
                $columns = array($columns); // str to array
            }
            $index->setColumns($columns);
            $index->setOptions($options);
        } else {
            $index = $columns;
        }

        $this->indexes[] = $index;        
        return $this;
    }
    
    /**
     * Removes the given index from a table.
     *
     * @param array $columns Columns
     * @param array $options Options
     * @return Table
     */
    public function removeIndex($columns, $options = array())
    {
        $this->getAdapter()->dropIndex($this->getName(), $columns, $options);
        return $this;
    }
    
    /**
     * Checks to see if an index exists.
     *
     * @param string|array $columns Columns
     * @param array        $options Options
     * @return boolean
     */
    public function hasIndex($columns, $options = array())
    {
        return $this->getAdapter()->hasIndex($this->getName(), $columns, $options);
    }
    
    /**
     * Commits the table changes.
     * 
     * @return void
     */
    public function save()
    {
        if ($this->exists()) {
            // update table
            foreach ($this->getColumns() as $column) {
                $this->getAdapter()->addColumn($this, $column);
            }
            
            foreach ($this->getIndexes() as $index) {
                $this->getAdapter()->addIndex($this, $index);
            }
        } else {
            // create table
            $this->getAdapter()->createTable($this);
        }
        
        $this->reset(); // reset pending changes
    }
}