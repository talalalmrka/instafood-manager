<?php

namespace Ifm;

use Ifm\Pages\Categories;
use Ifm\Pages\Export;
use Ifm\Pages\FixImages;
use Ifm\Pages\Import;
use Ifm\Pages\Products;

class Ifm
{
    public static function pages(): array
    {
        return [
            Import::class,
            Export::class,
            Categories::class,
            // Products::class,
            // FixImages::class,
        ];
    }

    public static function boot(): void
    {
        add_action("admin_menu", [self::class, "adminMenu"]);
        add_action("admin_enqueue_scripts", [self::class, "enqueue_assets"]);

        add_filter("script_loader_tag", [self::class, "inject_module_type"], 10, 2);

        foreach (self::pages() as $page) {
            $page::boot();
        }
    }

    public static function enqueue_assets(string $hook): void
    {
        $allowedHooks = [
            "toplevel_page_" . IFM_PAGE_SLUG,
            ...array_map(
                fn($page) => IFM_PAGE_SLUG . "_page_" . $page::slug(),
                self::pages()
            ),
        ];

        if (!in_array($hook, $allowedHooks, true)) {
            return;
        }
        if (IFM_DEV_MODE) {
            wp_enqueue_script(
                "ifm-debug",
                IFM_PLUGIN_URL . "assets/dist/debug.js",
                [],
                IFM_PLUGIN_VER,
                true
            );
        }
        $scriptHandle = "ifm-main";

        if (IFM_DEV_MODE && is_vite_running()) {
            wp_enqueue_script(
                "ifm-vite",
                "http://localhost:5173/@vite/client",
                [],
                null,
                false
            );

            wp_enqueue_script(
                $scriptHandle,
                "http://localhost:5173/assets/src/js/main.ts",
                ["ifm-vite"],
                null,
                false
            );
        } else {
            wp_enqueue_style(
                "ifm-styles",
                IFM_PLUGIN_URL . "assets/dist/main.css",
                [],
                IFM_PLUGIN_VER
            );

            wp_enqueue_script(
                $scriptHandle,
                IFM_PLUGIN_URL . "assets/dist/main.js",
                [],
                IFM_PLUGIN_VER,
                true
            );
        }

        wp_localize_script($scriptHandle, "Ifm", [
            "ajaxUrl" => admin_url("admin-ajax.php"),
        ]);
    }

    public static function inject_module_type(string $tag, string $handle): string
    {
        $moduleHandles = ["ifm-vite", "ifm-main"];

        if (!in_array($handle, $moduleHandles, true)) {
            return $tag;
        }

        if (str_contains($tag, ' type="module"')) {
            return $tag;
        }

        return str_replace("<script ", '<script type="module" ', $tag);
    }

    public static function adminMenu(): void
    {
        add_menu_page(
            IFM_PLUGIN_TITLE,
            IFM_PLUGIN_TITLE,
            "manage_options",
            IFM_PAGE_SLUG,
            "",
            "dashicons-food",
            25
        );

        foreach (self::pages() as $page) {
            add_submenu_page(
                IFM_PAGE_SLUG,
                $page::title(),
                $page::title(),
                $page::capability(),
                $page::slug(),
                fn() => self::renderPage($page)
            );
        }
    }

    private static function renderPage(string $currentPage): void
    {
        if (!current_user_can($currentPage::capability())) {
            wp_die(esc_html__("You do not have permission to access this page."));
        }

        $result = get_transient($currentPage::resultKey());

        if ($result !== false) {
            delete_transient($currentPage::resultKey());
        }
?>
        <div class="wrap">
            <nav
                class="ifm-nav"
                role="nav"
                aria-label="<?php echo esc_attr($currentPage::title()); ?>">
                <?php foreach (self::pages() as $page): ?>
                    <?php $isCurrent = $page === $currentPage; ?>

                    <a
                        id="tab-<?php echo esc_attr($page::slug()); ?>"
                        href="<?php echo esc_url(
                                    admin_url("admin.php?page=" . $page::slug())
                                ); ?>"
                        class="<?php echo esc_attr(
                                    cssClasses("ifm-nav-link", ["active" => $isCurrent])
                                ); ?>">
                        <?php icon($page::icon()); ?>

                        <span>
                            <?php echo esc_html($page::title()); ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div
                class="ifm-content"
                id="page-<?php echo esc_attr($currentPage::slug()); ?>">
                <h1 class="ifm-page-title">
                    <?php echo esc_html($currentPage::title()); ?>
                </h1>

                <?php $currentPage::render(); ?>
            </div>
        </div>
<?php
    }
}
