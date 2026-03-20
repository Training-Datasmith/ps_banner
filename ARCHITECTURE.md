# Architecture: ps_banner

## Purpose

A PrestaShop module that displays a customizable promotional banner on the store homepage.
Supports per-language banner images and links, with migration support from the PS 1.6
equivalent `blockbanner` module.

## Directory Structure

```
ps_banner.php     # Main module class (Ps_Banner extends Module implements WidgetInterface)
views/
  templates/      # Smarty/Twig templates for front-office banner display
  img/            # Default banner images
upgrade/          # Database migration scripts for module upgrades
translations/     # Module translation files
tests/            # PHPStan configuration and unit tests
```

## Key Design Decisions

### WidgetInterface

The module implements `WidgetInterface`, enabling it to be placed in any widget-capable
theme position, not just hardcoded hooks. The `renderWidget()` method is the primary
rendering entry point.

### Multi-Language Support

Banner image and URL are stored per-language using PrestaShop's `Configuration::getInt()`
with language-specific keys. The `actionObjectLanguageAddAfter` hook copies the default
language's configuration when a new language is added.

### PS 1.6 Migration

`uninstallPrestaShop16Module()` reads the old `blockbanner` module's configuration and
migrates it to the new format, then uninstalls the old module.

## Extension Points

- Hook into `displayHome` to place the banner; the widget can also be placed in other
  widget-compatible positions via theme configuration.
