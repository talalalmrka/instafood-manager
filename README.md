# InstaFood JSON Importer

A small WordPress admin plugin for importing InstaFood categories, menu items, and product variations from JSON.

## Requirements

- WordPress 5.8+
- InstaFood active
- The InstaFood post type `appetit_item`
- The InstaFood taxonomy `appetit_items_category`

## Installation

1. Upload `instafood-json-importer.zip` from WordPress Plugins.
2. Activate the plugin.
3. Open `Tools -> InstaFood JSON Importer`.
4. Paste the JSON.
5. Choose the existing-item behavior.
6. Import.

The importer follows the `appetit_item_meta` structure observed in the supplied WordPress XML export.
