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
class Button implements Arrayable
{

    public function __construct(
        public string $click,
        public ?string $label = null,
        public ?string $icon = null,
        public string $class = '',
        public bool $requiresSelection = false,
    ) {}
    /**
     * Create a new column instance.
     *
     * @param string $click Button click event.
     * @return static
     */
    public static function make($click)
    {
        $column = new static($click);
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
     * Button icon.
     *
     * @param string|null $icon
     * @return $this
     */
    public function icon(?string $icon)
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Button class.
     *
     * @param string $class
     * @return $this
     */
    public function class(?string $class)
    {
        $this->class = $class;

        return $this;
    }

    /**
     * Button requires selection.
     *
     * @param bool $requiresSelection
     * @return $this
     */
    public function requiresSelection(bool $requiresSelection = true)
    {
        $this->requiresSelection = $requiresSelection;

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
            ?? Str::of($this->click)->replace(['-', '_'], '')->title()->value();
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
            'btn',
            $this->class,
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
            'click' => $this->click,
            'label' => $this->getLabel(),
            'icon' => $this->icon,
            'class' => $this->getClassName(),
            'requiresSelection' => $this->requiresSelection,
        ];
    }
}
