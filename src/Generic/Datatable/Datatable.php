<?php

namespace Ifm\Generic\Datatable;

if (!defined('ABSPATH')) {
    exit;
}

use Ifm\Generic\Page;
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
trait Datatable {}
