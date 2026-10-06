<?php

if (!defined('ABSPATH')) {
    exit;
}

use Ifm\Collections\PaginatedCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

if (!function_exists('data_get')) {
    function data_get(mixed $target, string|array|null $key, mixed $default = null): mixed
    {
        if ($key === null) {
            return $target;
        }

        $keys = is_array($key)
            ? $key
            : explode('.', $key);

        foreach ($keys as $segment) {
            if ($segment === '*') {
                if (!is_array($target) && !($target instanceof Traversable)) {
                    return value($default);
                }

                $result = [];

                foreach ($target as $item) {
                    $result[] = data_get($item, array_slice($keys, 1), $default);
                }

                return $result;
            }

            if (is_array($target) && array_key_exists($segment, $target)) {
                $target = $target[$segment];
                continue;
            }

            if (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
                continue;
            }

            return value($default);
        }

        return $target;
    }
}

if (!function_exists('data_set')) {
    function data_set(
        mixed &$target,
        string|array $key,
        mixed $value,
        bool $overwrite = true
    ): mixed {
        $keys = is_array($key)
            ? $key
            : explode('.', $key);

        $current = &$target;

        foreach ($keys as $index => $segment) {
            $last = $index === array_key_last($keys);

            if ($last) {
                if ($overwrite || !data_has($current, $segment)) {
                    if (is_array($current)) {
                        $current[$segment] = value($value);
                    } elseif (is_object($current)) {
                        $current->{$segment} = value($value);
                    }
                }

                break;
            }

            if (is_array($current)) {
                if (!array_key_exists($segment, $current) || !is_array($current[$segment])) {
                    $current[$segment] = [];
                }

                $current = &$current[$segment];
                continue;
            }

            if (is_object($current)) {
                if (!isset($current->{$segment}) || !is_array($current->{$segment})) {
                    $current->{$segment} = [];
                }

                $current = &$current->{$segment};
            }
        }

        return $target;
    }
}

if (!function_exists('data_has')) {
    function data_has(mixed $target, string|array $key): bool
    {
        $keys = is_array($key)
            ? $key
            : explode('.', $key);

        foreach ($keys as $segment) {
            if (is_array($target) && array_key_exists($segment, $target)) {
                $target = $target[$segment];
                continue;
            }

            if (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
                continue;
            }

            return false;
        }

        return true;
    }
}

if (!function_exists('value')) {
    function value(mixed $value, mixed ...$args): mixed
    {
        return $value instanceof Closure
            ? $value(...$args)
            : $value;
    }
}

if (!function_exists('is_vite_running')) {
    function is_vite_running(
        string $host = "localhost",
        int $port = 5173,
        bool $ssl = false,
    ) {
        $url = ($ssl ? "https://" : "http://") . $host . ($port ? ":" . $port : '');
        $response = wp_remote_get($url, [
            'timeout' => 1,
            'sslverify' => $ssl,
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $status = wp_remote_retrieve_response_code($response);

        return $status >= 200 && $status < 500;
    }
}

if (!function_exists('dump')) {
    function dump(mixed $data, bool $return = false)
    {
        ob_start();
?>
        <pre class="fg-code"><code><?php print_r($data); ?></code></pre>
<?php
        if ($return) {
            return ob_get_clean();
        }
        echo ob_get_clean();
    }
}

if (!function_exists('str')) {
    function str(?string $value = null): Stringable|string
    {
        if ($value === null) {
            return '';
        }

        return Str::of($value);
    }
}

if (!function_exists('str_title')) {
    function str_title(string $value): string
    {
        return Str::title($value);
    }
}

if (!function_exists('icon')) {
    function icon(?string $icon, bool $return = false)
    {
        if (empty($icon)) {
            return;
        }
        $content = '<i class="icon ' . $icon . '"></i>';
        if ($return) {
            return $content;
        }
        echo $content;
    }
}

if (!function_exists('cssClasses')) {
    function cssClasses(string|array ...$classes): string
    {
        $classes = array_merge(
            ...array_map(
                static fn($class) => is_array($class) ? $class : [$class],
                $classes
            )
        );

        return Arr::toCssClasses($classes);
    }
}

if (!function_exists('per_page_options')) {
    function per_page_options()
    {
        $ops = range(5, 100, 5);
        return Arr::map($ops, fn($i) => [
            'label' => sprintf(__('%s entries'), $i),
            'value' => $i,
        ]);
    }
}

if (!function_exists('request')) {
    function request(string $key, mixed $default = null)
    {
        return isset($_REQUEST[$key])
            ? sanitize_text_field(wp_unslash($_REQUEST[$key]))
            : $default;
    }
}

if (!function_exists('pcollect')) {
    function pcollect(array $items = [])
    {
        return new PaginatedCollection($items);
    }
}
