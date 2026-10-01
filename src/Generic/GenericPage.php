<?php

namespace Ifm\Generic;

if (!defined('ABSPATH')) {
    exit;
}

interface GenericPage
{
    public static function title(): string;

    public static function slug(): string;

    public static function boot(): void;

    public static function render(): void;
}
