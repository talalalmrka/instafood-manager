<?php

namespace Ifm\Generic\Datatable;

if (!defined('ABSPATH')) {
    exit;
}

use Ifm\Generic\Page;
use Ifm\Generic\Datatable\Column;
use Ifm\Generic\Datatable\Button;
use Ifm\Generic\Datatable\Action;

/**
 * Base class for pages that render a data table.
 */
abstract class Datatable extends Page
{
    public static string $key = 'id';

    /**
     * Define the table columns.
     *
     * @return Column[]
     */
    abstract public static function columns(): array;

    /**
     * Define the table buttons.
     *
     * @return Button[]
     */
    public static function buttons(): array
    {
        return [
            Button::make('create')
                ->icon('bi-plus-lg')
                ->label(__('Create'))
                ->class('btn-green'),
            Button::make('deleteSelected')
                ->icon('bi-trash')
                ->label(__('Delete selected'))
                ->class('btn-red'),
        ];
    }

    /**
     * Define the row actions.
     *
     * @return Action[]
     */
    public static function actions()
    {
        return [
            Action::make('edit')
                ->icon('bi-pencil-square')
                ->label(__('Edit')),
            Action::make('delete')
                ->icon('bi-trash')
                ->label(__('Delete')),
        ];
    }


    /**
     * Define the table items.
     *
     * @return array
     */
    abstract public static function items(): array;
    public static function columnsCount(): int
    {
        return sizeof(self::columns()) + 2;
    }
    public static function filters(): void {}

    public static function headRow()
    {
?>
        <tr>
            <th><input type="checkbox" class="select-all"></th>
            <?php foreach (static::columns() as $col): ?>
                <th class="<?php echo esc_attr(cssClasses($col->getHeadClass())) ?>">
                    <?php echo esc_html($col->getLabel()); ?>
                </th>
            <?php endforeach; ?>
            <th><?php echo esc_html(__('Actions')) ?></th>
        </tr>
    <?php
    }

    public static function renderButtons()
    {
    ?>
        <div class="btn-group btn-group-xs">

            <?php foreach (self::buttons() as $button): ?>
                <button type="button" role="button" class="<?php echo esc_attr(cssClasses('btn', $button->getClassName())); ?>">
                    <i class="<?php echo esc_attr(cssClasses('icon', $button->icon)); ?>"></i>
                </button>
            <?php endforeach; ?>
        </div>
    <?php
    }

    public static function renderFilters()
    {
    ?>
        <div class="flex items-center gap-2">
            <?php self::filters(); ?>
            <div class="inline-flex items-center">
                <div class="form-control-container">
                    <span class="start-icon"><i class="icon bi-list"></i></span>
                    <select name="per_page" id="per_page" class="form-select has-start-icon xs pill">
                        <?php foreach (per_page_options() as $op): ?>
                            <option value="<?php echo esc_attr($op['value']); ?>"><?php echo esc_html($op['label']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="inline-flex items-center">
                <div class="form-control-container">
                    <span class="start-icon"><i class="icon bi-search"></i></span>
                    <input type="search" name="search" placeholder="Search..." class="form-control has-start-icon xs pill">
                </div>
            </div>
        </div>
    <?php
    }
    public static function renderRows()
    {
    ?>
        <?php if (!empty(static::items())): ?>
            <?php foreach (static::items() as $item): ?>
                <tr>
                    <td>
                        <input
                            x-ref="selectItem"
                            type="checkbox"
                            name="selected[]"
                            value="<?php echo esc_attr(data_get($item, self::$key)); ?>">
                    </td>
                    <?php foreach (static::columns() as $col): ?>
                        <td class="<?php echo esc_attr(cssClasses($col->getClassName())); ?>"><?php echo esc_html(data_get($item, $col->name)); ?></td>
                    <?php endforeach; ?>
                    <td>
                        <div class="flex items-center gap-2 md:gap-3 justify-center">
                            <?php foreach (static::actions() as $action): ?>
                                <button
                                    type="button"
                                    title="<?php echo esc_attr($action->getLabel()); ?>"
                                    class="<?php echo esc_attr(cssClasses($action->getClassName())); ?>"
                                    x-on:click="action('<?php echo esc_attr(data_get($action, 'click')) ?>', <?php echo esc_attr(data_get($item, self::$key)); ?>)">
                                    <i class="<?php echo esc_attr(cssClasses('icon', $action->icon)); ?>"></i>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="<?php echo esc_attr(static::columnsCount()) ?>" class="text-center"><?php echo esc_html('No Items!'); ?></td>
            </tr>
        <?php endif; ?>
    <?php
    }
    /**
     * Render the data table.
     */
    public static function renderTable(): void
    {
    ?>
        <div class="flex md:items-center flex-col md:flex-row md:justify-between gap-2 mb-3">
            <?php self::renderButtons(); ?>
            <?php self::renderFilters(); ?>
        </div>
        <div class="table-container">
            <table class="table table-divide table-striped table-border xs">
                <thead>
                    <?php self::headRow(); ?>
                </thead>
                <tbody>
                    <?php self::renderRows(); ?>
                </tbody>
                <tfoot>
                    <?php self::headRow(); ?>
                </tfoot>
            </table>
        </div>
<?php
    }
}
