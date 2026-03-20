<?php

declare(strict_types=1);

/**
 * Example: Using ps_banner in a PrestaShop theme.
 *
 * The ps_banner module is a WidgetInterface module. It can be used in theme templates
 * via the widget() function, or it will automatically render via the displayHome hook.
 *
 * In a Smarty theme template:
 */

// {widget name="ps_banner"}

// Or hook it via module configuration — the module registers on displayHome by default.

// To render it in a custom position in your theme's .tpl file:
// {widget name="ps_banner" hook_name="displayHome"}

// The module displays an image linked to a configurable URL.
// Configure the banner image and link URL in:
//   Back Office > Modules > ps_banner > Configure
