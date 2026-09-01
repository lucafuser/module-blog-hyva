# MageOS Blog — Hyvä

The Hyvä companion for [`mage-os/module-blog`](https://github.com/mage-os/module-blog).
Hyvä-native storefront templates, Tailwind styles, and a blog entry in the Hyvä main menu.

This package contains no blog logic. Blocks, ViewModels, routing, GraphQL and admin all stay
in `mage-os/module-blog`; this only replaces what is rendered and how it is styled.

**[See what it looks like →](docs/storefront.md)** — every page, desktop and mobile.

## Requirements

| | |
| --- | --- |
| Hyvä | **3.x (Tailwind 4)** — verified. Hyvä 2 / Tailwind 3 is best-effort; see below. |
| Also needs | `hyva-themes/magento2-compat-module-fallback` (a hard requirement, not bundled with the theme) |
| Blog | `mage-os/module-blog` ^1.3 |

## Install

```bash
composer require mage-os/module-blog-hyva
bin/magento module:enable MageOS_BlogHyva
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:clean
```

**That is not the whole install.** The templates now render, but nothing is styled yet — the
CSS does not exist until the module is registered for Tailwind and the theme is rebuilt:

```bash
bin/magento hyva:config:generate   # adds this module to app/etc/hyva-themes.json
cd <your-theme>/web/tailwind && npm run build
```

If the blog renders as unstyled HTML, this is why. Check the module appears in
`app/etc/hyva-themes.json` and that an `@import` for it landed in your theme's
`web/tailwind/generated/hyva-source.css`.

## How it works

Registering in `Hyva\CompatModuleFallback\Model\CompatModuleRegistry` (see
[`etc/frontend/di.xml`](etc/frontend/di.xml)) does two jobs:

1. **Templates.** Hyvä injects this module's view directories ahead of `MageOS_Blog`'s, so a
   template at the *same relative path* replaces the Luma one — on Hyvä themes only, with no
   layout XML. On Luma the original renders untouched.
2. **Tailwind.** `hyva:config:generate` adds this module to `app/etc/hyva-themes.json`, which is
   what pulls [`view/frontend/tailwind/module.css`](view/frontend/tailwind/module.css) into the
   theme's build.

A path `MageOS_Blog` does not render is unreachable — silently. When adding an override, mirror
the upstream path exactly.

### The menu entry

`MageOS_Blog` puts its storefront link in `top.links`, which Hyvä does not define — the
`referenceBlock` matches nothing and is dropped without error, leaving a Hyvä store with no
route into the blog. [`Plugin/Hyva/Theme/Service/Navigation.php`](Plugin/Hyva/Theme/Service/Navigation.php)
adds a top-level **Blog** entry to the Hyvä menu instead, gated on `mageos_blog/general/enabled`.

## What is overridden

Fourteen templates, mirroring `MageOS_Blog`:

| | |
| --- | --- |
| Pages | `post/listing`, `post/view`, `post/card`, `category/view`, `tag/view`, `author/view`, `search/results` |
| Product page | `product/related-posts` |
| Widgets | `recent-posts`, `post-list`, `featured-post`, `post-link`, `category-link`, `tag-link` |

Plus two partials this module owns: `post/cards.phtml` (the shared card grid) and
`post/pagination.phtml`.

Not overridden, deliberately: `post/head-meta.phtml` and `post/jsonld.phtml` emit no markup, and
`sidebar/container.phtml` is not rendered by any layout in `MageOS_Blog`.

## Styling

This is a port of the Luma stylesheet, not a redesign. Every class `MageOS_Blog::css/blog.css`
and `blog-cards.css` style has a rule of the same name under
[`view/frontend/tailwind/components/`](view/frontend/tailwind/components/), and the markup
carries the same `mageos-blog-*` classes and nothing else. The two themes therefore share one
selector vocabulary and one override surface: a merchant rule written against Luma behaves
identically here, and the two stylesheets stay diffable as the design changes.

| File | Mirrors |
| --- | --- |
| `base.css` | the `--mageos-blog-*` design tokens and base typography |
| `card.css` | the post card |
| `related-posts.css` | the compact card inside a product section |
| `page.css` | container, context header, pagination, empty state |
| `post.css` | post detail, body, tags, share, related |
| `widget.css` | the six storefront widgets |

The design tokens are ported verbatim and are the intended way to restyle:
`.mageos-blog-related-posts` shrinks the card purely by reassigning `--mageos-blog-text` and
`--mageos-blog-h2`. Point them at your Hyvä theme tokens rather than editing the rules.

Two things deviate from Luma, both because Hyvä has no equivalent inherited flow: a grid on
`.mageos-blog-cards`, and minimal widget styling. And because Tailwind's preflight zeroes the
browser defaults `.mageos-blog-post__content` relied on — list markers, heading weights, tables,
rules — those are restored explicitly in `post.css`. Without that a post body renders as one
undifferentiated block of text.

### Hyvä 2

`tailwind-source.css` and `tailwind.config.js` are kept for Hyvä 2, and a Hyvä 3 build ignores
them once `module.css` exists. Treat this as best-effort: the templates are shared across both
majors, so a utility resolving under only one silently produces nothing on the other. Hyvä 3 is
what gets verified.

## Contributing

Quality gates match the main module:

```bash
composer test          # unit + phpstan + cs-fixer + phpcs
```

PHPStan cannot see `Hyva\…` classes without licence credentials, so those are ignored in
[`phpstan.neon`](phpstan.neon); the plugin's signatures are verified against a real Hyvä install
instead.

## Licence

OSL-3.0. See [LICENSE](LICENSE).
