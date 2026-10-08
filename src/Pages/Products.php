<?php


namespace Ifm\Pages;

use Ifm\Generic\Datatable\Column;
use Ifm\Generic\Datatable\Datatable;
use Ifm\Services\CategoryService;
use Ifm\Services\ProductService;

class Products extends Datatable
{
    public static function icon(): string
    {
        return 'bi-basket3';
    }
    public static function columns(): array
    {
        return [
            Column::make('ID')
                ->label(__('ID'))
                ->sortable(),
            Column::make('post_title')
                ->label(__('Name'))
                ->sortable(),
            Column::make('post_name')
                ->label(__('Slug'))
                ->sortable(),
            Column::make('post_excerpt')
                ->label(__('Description'))
                ->sortable(),
            Column::make('post_status')
                ->label(__('Status'))
                ->sortable()
                ->class('text-center'),
        ];
    }
    /**
     * get items
     * @return \Illuminate\Support\Collection
     */
    public static function items()
    {
        $products = ProductService::all($_REQUEST);
        return $products;
    }

    public static function filters(): void
    {
        $categories = CategoryService::all();
?>
        <div class="inline-flex items-center">
            <div class="form-control-container">
                <span class="start-icon"><i class="icon bi-tag"></i></span>
                <select x-model="filters.category" id="per_page" class="form-select has-start-icon xs pill">
                    <option value=""><?php echo esc_html('All'); ?></option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo esc_attr($category->term_id); ?>"><?php echo esc_html($category->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
<?php
    }
}
