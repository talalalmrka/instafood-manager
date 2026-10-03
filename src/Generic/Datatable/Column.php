<?php

namespace Ifm\Generic\Datatable;

if (!defined('ABSPATH')) {
    exit;
}

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Contracts\Support\Arrayable;

/**
 * Represents a datatable column definition.
 *
 * Provides a fluent API for configuring column
 * properties such as label, sorting, searching,
 * CSS classes, and custom content rendering.
 */
class Column implements Arrayable
{

    public function __construct(
        public string $name,
        public ?string $label = null,
        public bool $sortable = false,
        public bool $searchable = false,
        public string $headClass = '',
        public string $class = '',
        public bool $noWrap = true,
    ) {}
    /**
     * Create a new column instance.
     *
     * @param string $name Column attribute name.
     * @return static
     */
    public static function make($name)
    {
        $column = new static($name);
        $column->name = $name;

        return $column;
    }

    /**
     * Set the column label.
     *
     * @param string $label
     * @return $this
     */
    public function label($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Enable or disable column sorting.
     *
     * @param bool $isSortable
     * @return $this
     */
    public function sortable($isSortable = true)
    {
        $this->sortable = $isSortable;

        return $this;
    }

    /**
     * Enable or disable column searching.
     *
     * @param bool $isSearchable
     * @return $this
     */
    public function searchable($isSearchable = true)
    {
        $this->searchable = $isSearchable;

        return $this;
    }

    /**
     * Set header cell CSS classes.
     *
     * @param string $class
     * @return $this
     */
    public function headClass($class)
    {
        $this->headClass = $class;

        return $this;
    }

    /**
     * Set body cell CSS classes.
     *
     * @param string $class
     * @return $this
     */
    public function class($class)
    {
        $this->class = $class;

        return $this;
    }

    /**
     * Enable or disable text wrapping.
     *
     * @param bool $noWrap
     * @return $this
     */
    public function noWrap($noWrap)
    {
        $this->noWrap = $noWrap;

        return $this;
    }

    /**
     * Get the display label for the column.
     *
     * If no label is defined, a label will be
     * automatically generated from the column name.
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label
            ?? Str::of($this->name)->replace(['-', '_'], '')->title()->value();
    }

    /**
     * Get the CSS classes for the table body cell.
     *
     * @param string|null $className Additional classes.
     * @return string
     */
    public function getClassName($className = null)
    {
        return Arr::toCssClasses([
            'w-max text-nowrap' => $this->noWrap,
            $this->class,
            $className,
        ]);
    }

    /**
     * Get the CSS classes for the table header cell.
     *
     * @param string|null $className Additional classes.
     * @return string
     */
    public function getHeadClass($className = null)
    {
        return Arr::toCssClasses([
            'w-max text-nowrap' => $this->noWrap,
            $this->headClass,
            $className,
        ]);
    }

    /**
     * Convert the column definition to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray()
    {
        return [
            'name' => $this->name,
            'label' => $this->getLabel(),
            'sortable' => $this->sortable,
            'searchable' => $this->searchable,
            'headClass' => $this->getHeadClass(),
            'class' => $this->getClassName(),
            'noWrap' => $this->noWrap,
        ];
    }
}
