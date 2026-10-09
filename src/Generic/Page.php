<?php

namespace Ifm\Generic;

if (!defined('ABSPATH')) {
    exit;
}

use Illuminate\Support\Str;

abstract class Page implements GenericPage
{
    public static function title(): string
    {
        return Str::title(class_basename(static::class));
    }

    public static function slug(): string
    {
        return Str::slug(IFM_PAGE_SLUG . '-' . static::title());
    }

    public static function icon(): string
    {
        return '';
    }

    public static function resultKey(): string
    {
        return static::slug() . '_' . get_current_user_id();
    }

    public static function nonce(): string
    {
        return str_ireplace('-', '_', static::slug());
    }

    public static function capability(): string
    {
        return 'manage_options';
    }

    /**
     * set ajax action
     * @return string
     */
    public static function ajaxPrefix()
    {
        return Str::of(class_basename(static::class))->lower()->slug('_');
    }
    /**
     * register ajax
     * @param string $action
     * @param string $callback
     * @param bool $nopriv default true
     */
    public static function registerAjax($action, $callback, $nopriv = true)
    {
        add_action('wp_ajax_' . static::ajaxPrefix() . "_" . $action, [static::class, $callback]);
        if ($nopriv) {
            add_action('wp_ajax_nopriv_' . static::ajaxPrefix() . "_" . $action, [static::class, $callback]);
        }
    }
    public static function renderResult(mixed $result): void
    {
        if (!$result || !is_array($result)) {
            return;
        }

        $type = data_get($result, 'type');
        $message = data_get($result, 'message');
        $summary = data_get($result, 'summary', []);
        if ($type === 'error') { ?>
            <div class="alert-soft-error alert-sm flex items-center gap-2">
                <div>
                    <i class="icon bi-exclamation-traingle"></i>
                </div>
                <div class="flex-1">
                    <?php echo esc_html($message ?? 'Unknown error.'); ?>
                </div>
            </div>
        <?php
            return;
        }
        if (!empty($message) || !empty($summary)) {
        ?>
            <div class="alert-soft-success alert-sm">
                <?php if (!empty($message)): ?>
                    <p><?php echo esc_html($message); ?></p>
                <?php endif; ?>
                <?php if (!empty($summary)): ?>
                    <ul>
                        <?php foreach ($summary as $key => $value): ?>
                            <li>
                                <?php if (is_string($key)): ?>
                                    <?php echo esc_html($key); ?>:
                                <?php endif; ?>
                                <?php echo esc_html($value); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
<?php
        }
    }

    public static function redirect_with_error(string $message): void
    {
        set_transient(
            self::resultKey(),
            [
                'type' => 'error',
                'message' => $message,
            ],
            60
        );

        wp_safe_redirect(
            admin_url('admin.php?page=' . self::slug())
        );

        exit;
    }

    public static function dump(mixed $data)
    {
        dump($data);
    }
}
