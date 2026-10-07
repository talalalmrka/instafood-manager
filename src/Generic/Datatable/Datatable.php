<?php

namespace Ifm\Generic\Datatable;

if (!defined("ABSPATH")) {
  exit();
}

use Ifm\Generic\Page;
use Ifm\Generic\Datatable\Column;
use Ifm\Generic\Datatable\Button;
use Ifm\Generic\Datatable\Action;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Base class for pages that render a data table.
 */
abstract class Datatable extends Page
{
  public static string $primaryKey = "id";

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
      Button::make("create")
        ->icon("bi-plus-lg")
        ->label(__("Create"))
        ->class("btn-green"),
      Button::make("deleteSelected")
        ->icon("bi-trash")
        ->label(__("Delete selected"))
        ->requiresSelection()
        ->class("btn-red"),
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
      Action::make("edit")
        ->icon("bi-pencil-square")
        ->label(__("Edit")),
      Action::make("delete")
        ->icon("bi-trash")
        ->label(__("Delete")),
    ];
  }

  /**
   * Define the table items.
   *
   * @return \Ifm\Collections\PaginatedCollection
   */
  abstract public static function items();
  public static function columnsCount(): int
  {
    return sizeof(self::columns()) + 2;
  }
  public static function filters(): void
  {
  }
  /**
   * set ajax action
   * @return string
   */
  public static function ajaxAction()
  {
    $suffix = Str::slug(class_basename(static::class), "_");
    return "datatable_" . $suffix;
  }
  public static function boot(): void
  {
    add_action("wp_ajax_" . static::ajaxAction(), [static::class, "datatable"]);
    add_action("wp_ajax_nopriv_" . static::ajaxAction(), [
      static::class,
      "datatable",
    ]);
  }
  public static function headRow()
  {
    ?>
        <tr>
            <th><input type="checkbox" x-model="selectAll" class="select-all"></th>
            <template x-for="col in columns">
                <th :class="col.headClass" x-html="col.label"></th>
            </template>
            <th><?php echo esc_html(__("Actions")); ?></th>
        </tr>
    <?php
  }
  /* public static function headRow()
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
    } */

  /* public static function renderButtons()
    {
    ?>
        <div class="btn-group btn-group-xs">
            <template x-for="button in buttons">
                <button type="button" :title="button.label" class="btn" :class="button.class" x-on:click="onButtonClicked(button.click)">
                    <i class="icon" :class="button.icon"></i>
                </button>
            </template>
        </div>
    <?php
    } */
  public static function renderButtons()
  {
    ?>
        <div class="btn-group btn-group-xs">

            <?php foreach (self::buttons() as $button):
              $requiresSelectionAttr = $button->requiresSelection
                ? " :disabled=\"!selected.length\""
                : ""; ?>
                <button type="button" role="button" title="<?php echo esc_attr(
                  $button->getLabel()
                ); ?>" x-on:click="onButtonClicked('<?php echo esc_attr(
  $button->click
); ?>')" class="<?php echo esc_attr(
  cssClasses("btn", $button->getClassName())
); ?>" <?php echo $requiresSelectionAttr; ?>>
                    <i class="<?php echo esc_attr(
                      cssClasses("icon", $button->icon)
                    ); ?>"></i>
                </button>
            <?php
            endforeach; ?>
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
                    <select x-model="filters.per_page" id="per_page" class="form-select has-start-icon xs pill">
                        <?php foreach (per_page_options() as $op): ?>
                            <option value="<?php echo esc_attr(
                              $op["value"]
                            ); ?>"><?php echo esc_html(
  $op["label"]
); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="inline-flex items-center">
                <div class="form-control-container">
                    <span class="start-icon"><i class="icon bi-search"></i></span>
                    <input type="search" x-model="filters.search" placeholder="<?php echo esc_attr(
                      "Search..."
                    ); ?>" class="form-control has-start-icon xs pill">
                </div>
            </div>
        </div>
    <?php
  }
  /* public static function renderFilters()
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
    } */
  public static function renderRows()
  {
    ?>

        <tr x-show="!loading && !items.length">
            <td colspan="<?php echo esc_attr(
              static::columnsCount()
            ); ?>" class="text-center"><?php echo esc_html("No Items!"); ?></td>
        </tr>
        <template x-for="item in items">
            <tr>
                <td>
                    <input
                        x-ref="selectItem"
                        type="checkbox"
                        x-model="selected[]"
                        :value="item[primaryKey]">
                </td>
                <template x-for="col in columns">
                    <td :class="col.class" x-html="item[col.name]"></td>
                </template>
                <td></td>
            </tr>
        </template>
    <?php
  }
  /* public static function renderRows()
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
                            value="<?php echo esc_attr(data_get($item, self::$primaryKey)); ?>">
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
                                    x-on:click="action('<?php echo esc_attr(data_get($action, 'click')) ?>', <?php echo esc_attr(data_get($item, self::$primaryKey)); ?>)">
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
    } */
  /**
   * Render the data table.
   */
  public static function renderTable(): void
  {
    ?>
        <div x-data="Datatable(<?php echo esc_attr(
          json_encode(static::datatableOptions())
        ); ?>)">
            <div class="flex md:items-center flex-col md:flex-row md:justify-between gap-2 mb-3">
                <?php self::renderButtons(); ?>
                <?php self::renderFilters(); ?>
            </div>
            <div class="table-container">
                <table class="table table-divide table-striped table-border xs">
                    <thead>
                        <?php self::headRow(); ?>
                    </thead>
                    <tbody x-html="rows">
                    </tbody>
                    <tfoot>
                        <?php self::headRow(); ?>
                    </tfoot>
                </table>
            </div>
            <span x-show="loading" class="fixed z-50 center-all text-white bg-primary/80 pill px-3">
                <i class="icon fg-loader-dots-move text-xl leading-0 m-0! p-0!"></i>
            </span>
        </div>
    <?php
  }
  public static function renderTablee(): void
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
  /**
   * get columns
   * @return \Illuminate\Support\Collection
   */
  public static function getColumns()
  {
    $columns = static::columns();
    return !$columns instanceof Collection ? collect($columns) : $columns;
  }

  /**
   * get buttons
   * @return \Illuminate\Support\Collection
   */
  public static function getButtons()
  {
    $buttons = static::buttons();
    return !$buttons instanceof Collection ? collect($buttons) : $buttons;
  }

  /**
   * get actions
   * @return \Illuminate\Support\Collection
   */
  public static function getActions()
  {
    $actions = static::actions();
    return !$actions instanceof Collection ? collect($actions) : $actions;
  }

  /**
   * get filters
   * @return array
   */
  public static function getFilters()
  {
    $search = request("search", "");
    $perPage = (int) request("per_page", 15);
    $page = (int) request("page", 1);
    $orderBy = request("orderby", "");
    $order = request("order", "");
    return [
      "search" => $search,
      "per_page" => $perPage,
      "page" => $page,
      "orderby" => $orderBy,
      "order" => $order,
    ];
  }

  /**
   * alpine datatable options
   * @return array
   */
  public static function datatableOptions()
  {
    return [
      "primaryKey" => static::$primaryKey,
      "ajaxAction" => static::ajaxAction(),
      "filters" => static::getFilters(),
      "columns" => static::getColumns()->toArray(),
      "buttons" => static::getButtons()->toArray(),
      "actions" => static::getActions()->toArray(),
    ];
  }

  public static function datatableData()
  {
    $perPage = request("per_page");
    $page = request("paged", 1);
    $data = static::items()->paginate($perPage, $page);
    $items = $data->items();
    return [
      "items" => $items,
      "pagination" => Arr::except($data->toArray(), "data"),
    ];
  }

  public static function datatable()
  {
    wp_send_json_success(static::datatableData());
  }

  public static function datatablee(): void
  {
    // self::verifyAjax();

    $search = request("search", "");
    $perPage = (int) request("per_page", 20);
    $orderBy = request("orderby", "");
    $order = request("order", "");

    wp_send_json_success([
      "columns" => collect(static::columns())->toArray(),
      "buttons" => collect(static::buttons())->toArray(),
      "actions" => collect(static::actions())->toArray(),
      "filters" => [
        "search" => $search,
        "per_page" => $perPage,
        "orderby" => $orderBy,
        "order" => $order,
      ],
      "items" => collect(static::items())->toArray(),
    ]);
  }
  public static function verifyAjax(): void
  {
    if (!current_user_can(static::capability())) {
      wp_send_json_error(
        [
          "message" => __("Unauthorized."),
        ],
        403
      );
    }

    check_ajax_referer("ifm_nonce", "nonce");
  }
}
