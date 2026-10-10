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

/**
 * Base class for pages that render a data table.
 */
abstract class Datatable extends Page
{
  public static string $primaryKey = "ID";
  public static string $class = "";

  public static function boot(): void
  {

    static::registerAjax('items', 'itemsAjax');
    static::registerAjax('delete', 'delete');
    // add_action("wp_ajax_" . static::ajaxAction(), [static::class, "datatable"]);
    // add_action("wp_ajax_nopriv_" . static::ajaxAction(), [
    //   static::class,
    //   "datatable",
    // ]);
  }
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
    $additionalCols = !empty(static::actions()) ? 2 : 1;
    return sizeof(static::columns()) + $additionalCols;
  }
  public static function filters(): void {}




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
    $orderBy = request("orderby", "");
    $order = request("order", "");
    return [
      "search" => $search,
      "per_page" => $perPage,
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
    $paged = (int) request("paged", 1);
    return [
      "primaryKey" => static::$primaryKey,
      "ajaxPrefix" => static::ajaxPrefix(),
      "filters" => static::getFilters(),
      "paged" => $paged,
      "columns" => static::getColumns()->toArray(),
      "buttons" => static::getButtons()->toArray(),
      "actions" => static::getActions()->toArray(),
    ];
  }

  public static function datatableData()
  {
    // return static::items();
    $perPage = request("per_page");
    $paged = request("paged", 1);
    $data = static::items()->paginate($perPage, $paged);
    $items = $data->items();
    return [
      "items" => $items,
      "pagination" => Arr::except($data->toArray(), "data"),
    ];
  }

  public static function itemsAjax()
  {
    self::verifyAjax();
    wp_send_json_success(static::items());
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
  public static function headRow()
  {
?>
    <tr>
      <th><input type="checkbox" x-model="selectAll" class="select-all"></th>
      <?php foreach (static::columns() as $column): ?>
        <th class="<?php cssClasses($column->getHeadClass()); ?>">
          <?php if ($column->sortable): ?>
            <div x-on:click="sort('<?php echo esc_attr($column->name); ?>')"
              class="w-full flex items-center gap-1.5 cursor-pointer select-none">
              <div class="flex-1"><?php echo $column->getLabel(); ?></div>
              <div class="flex flex-col text-xxs">
                <i
                  class="icon bi-chevron-up" :class="sortClass('<?php echo esc_attr($column->name); ?>', 'asc')"></i>
                <i
                  class="icon bi-chevron-down" :class="sortClass('<?php echo esc_attr($column->name); ?>', 'desc')"></i>
              </div>
            </div>
          <?php else: ?>
            <?php echo $column->getLabel(); ?>
          <?php endif; ?>
        </th>
      <?php endforeach; ?>
      <?php if (!empty(static::actions())): ?>
        <th><?php echo esc_html(__("Actions")); ?></th>
      <?php endif; ?>
    </tr>
  <?php
  }

  public static function renderButtons()
  {
  ?>
    <div class="btn-group btn-group-xs">
      <?php foreach (self::buttons() as $button):
        $requiresSelectionAttr = $button->requiresSelection
          ? " :disabled=\"!selected.length\""
          : ""; ?>
        <button type="button"
          role="button"
          title="<?php echo esc_attr($button->getLabel()); ?>"
          x-on:click="onButtonClicked('<?php echo esc_attr($button->click); ?>')"
          class="<?php echo esc_attr(cssClasses("btn", $button->getClassName())); ?>" <?php echo $requiresSelectionAttr; ?>>
          <i class="<?php echo esc_attr(cssClasses("icon", $button->icon)); ?>"></i>
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
      <div class="inline-flex items-center">
        <button
          x-on:click="resetFilters"
          type="button"
          title="<?php echo esc_attr(__('Reset filters')) ?>"
          class="btn btn-xs btn-outline-secondary pill w-auto inline-flex gap-0!">
          <i class="icon bi-arrow-repeat w-3 h-3"></i>
        </button>
      </div>
      <?php static::filters(); ?>
      <div class="inline-flex items-center">
        <div class="form-control-container">
          <span class="start-icon"><i class="icon bi-list"></i></span>
          <select x-model="filters.per_page" id="per_page" class="form-select has-start-icon xs pill">
            <?php foreach (per_page_options() as $op): ?>
              <option value="<?php echo esc_attr($op["value"]); ?>"><?php echo esc_html($op["label"]); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="inline-flex items-center">
        <div class="form-control-container">
          <span class="start-icon"><i class="icon bi-search"></i></span>
          <input
            type="search"
            x-model="filters.search"
            placeholder="<?php echo esc_attr("Search..."); ?>"
            class="form-control has-start-icon xs pill">
        </div>
      </div>
    </div>
  <?php
  }

  public static function renderRows()
  {
  ?>

    <tr x-show="!loading && !items.length">
      <td colspan="<?php echo esc_attr(static::columnsCount()); ?>" class="text-center"><?php echo esc_html("No Items!"); ?></td>
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

  /**
   * Render the data table.
   */
  public static function renderTable(): void
  {
  ?>
    <div x-data="Datatable(<?php echo esc_attr(json_encode(static::datatableOptions())); ?>)">
      <div class="flex md:items-center flex-col md:flex-row md:justify-between gap-2 mb-3">
        <?php self::renderButtons(); ?>
        <?php self::renderFilters(); ?>
      </div>
      <div class="table-container">
        <table class="table table-striped table-divide table-rounded table-border table-auto xs <?php echo esc_attr(cssClasses(static::$class)); ?>">
          <thead>
            <?php self::headRow(); ?>
          </thead>
          <tbody x-html="rows">
          </tbody>
          <tfoot>
            <tr>
              <th colspan="<?php echo esc_attr(static::columnsCount()); ?>">
                <div class="flex items-center gap-2 justify-between">
                  <span x-text="`${selected.length} selected.`"></span>
                  <span x-text="`${pagination.total_items} items`"></span>
                </div>
              </th>
            </tr>
            <?php self::headRow(); ?>
          </tfoot>
        </table>
      </div>
      <div class="pagination-container pt-3" x-show="hasPagination()" x-html="paginationHtml"></div>
      <span x-show="isLoading('items')" class="fixed z-50 center-all text-primary text-xl px-4 py-0! bg-white/90 dark:bg-gray-800/70 border shadow backdrop-blur-md pill">
        <i class="icon fg-loader-dots-bounce"></i>
      </span>
      <div class="mt-4">
        <button x-on:click="toggleDebug" type="button" class="btn-circle-secondary btn-circle-sm">
          <i class="icon bi-eye-fill" :class="{'bi-eye-fill-slash': showDebug}"></i>
        </button>
        <pre class="fg-code" x-show="showDebug"><code x-html="debugJson"></code></pre>
      </div>

    </div>
<?php
  }


  public static function render(): void
  {
    static::renderTable();
  }
}
