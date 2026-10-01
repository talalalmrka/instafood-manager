<?php


namespace Ifm\Pages;

use Ifm\Generic\Page;
use Ifm\Services\ProductService;

class Products extends Page
{
    /* public static function title(): string
    {
        return __('Products');
    }

    public static function slug(): string
    {
        return IFM_PAGE_SLUG . '-products';
    } */
    public static function icon(): string
    {
        return 'bi-basket3';
    }
    public static function boot(): void {}

    public static function render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to access this page.');
        }

        $result = get_transient(self::resultKey());

        if ($result !== false) {
            delete_transient(self::resultKey());
        }
        /* $perPage = data_get($_REQUEST, 'per_page', 20);

        $currentPage = isset($_GET['paged'])
            ? max(1, absint($_GET['paged']))
            : 1;

        $query = new \WP_Query([
            'post_type' => IFM_ITEM_POST_TYPE,
            'post_status' => 'any',
            'posts_per_page' => $perPage,
            'paged' => $currentPage,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'no_found_rows' => false,
            'suppress_filters' => true,
        ]);

        $items = $query->posts; */
        $products = ProductService::all($_REQUEST);
?>

        <div class="wrap">
            <h1><?php echo esc_html(self::title()); ?></h1>
            <div class="page-wrapper">
                <?php self::renderResult($result); ?>
                <?php dump($products); ?>
            </div>
        </div>
    <?php
    }

    public static function headRow(): void
    {
    ?>
        <tr>
            <th><input type="checkbox" class="select-all" value="1"></th>
            <th>#</th>
            <th>Product</th>
            <th>Category</th>
            <th>Variations</th>
            <th>Status</th>
            <th>Order</th>
            <th>Image</th>
        </tr>
<?php
    }
}
