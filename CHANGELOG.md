# Changelog

## [1.0.0] - Unreleased

First working release. The package existed before this but was a port of a different blog
extension carried over during the original compatibility-module effort, and `MageOS_Blog` has
been rewritten since — every PHP symbol it referenced and every template path it overrode had
stopped existing. Nothing in it compiled or rendered. See
[`docs/plans/2026-08-24-readiness-assessment.md`](docs/plans/2026-08-24-readiness-assessment.md).

### Added

- **Hyvä-native template set.** Fourteen templates mirroring `MageOS_Blog`'s paths: the blog
  index, post detail, post card, category, tag, author, search results, PDP related posts, and
  all six widgets. Plus `post/cards.phtml` and `post/pagination.phtml`, two partials this module
  owns — `MageOS_Blog` repeats both inline across five templates.
- **Blog entry in the Hyvä main menu.** `MageOS_Blog` links itself into `top.links`, which Hyvä
  does not define, so a Hyvä store previously had no route into the blog. Gated on
  `mageos_blog/general/enabled`.
- **Tailwind 4 asset layer** at `view/frontend/tailwind/module.css`, picked up automatically via
  `hyva:config:generate`. `tailwind-source.css` and `tailwind.config.js` are retained for Hyvä 2
  as best-effort.
- **A Tailwind port of the Luma stylesheet.** Every class `MageOS_Blog::css/blog.css` and
  `blog-cards.css` style has a rule of the same name under `tailwind/components/`, including the
  `--mageos-blog-*` design tokens — so the markup, the selector vocabulary and the override
  surface are identical on both themes, and a merchant's existing rules keep working. Two
  additions have no Luma counterpart: a grid on `.mageos-blog-cards` and minimal widget styling,
  both of which Luma gets from inherited flow that Hyvä has no equivalent for. Tailwind's
  preflight also zeroes the browser defaults `.mageos-blog-post__content` relied on for list
  markers, heading weights, tables and rules; those are restored explicitly.
- Packaging and quality gates: PHPStan level 8, PHPCS (Magento2), PHP-CS-Fixer, PHPUnit, CI,
  OSL-3.0 licence, README. CI mirrors `mage-os/module-blog` — the graycore
  `check-extension` matrix plus PHPStan — with `COMPOSER_AUTH` on every job, since nothing here
  installs without Hyvä credentials, and an extra template-lint job because the `.phtml` set is
  the bulk of the package and neither phpcs nor phpstan looks at it.
- [`docs/storefront.md`](docs/storefront.md), a visual reference for every page the module
  renders, with desktop and mobile screenshots taken from a real Hyvä 3 install.

### Changed

- Package renamed `mageos/module-blog-hyva` → **`mage-os/module-blog-hyva`**, matching the main
  package's vendor and what its README promises.
- `hyva-themes/magento2-compat-module-fallback` is now a hard requirement. `etc/frontend/di.xml`
  always assumed it, so `setup:di:compile` failed wherever it was absent.
- Dropped `minimum-stability: dev`, which resolved dev dependencies into merchant installs.
- Requires `mage-os/module-blog` `^1.3`. Both `require` entries were `*`, which would have
  resolved against any version, including the ones whose template paths this module no longer
  matches.

### Removed

- The recent-posts carousel widget, its slider templates and CSS. It was built on
  `Hyva\Theme\ViewModel\Slider`, which Hyvä has deprecated in favour of the CSS slider, and its
  `etc/widget.xml` declared `MageOS\Blog\Block\Widget\Recent` — a class that does not exist.
  Worth revisiting against the current slider.
- Sidebar CSS, which styled `.blog-search` and `.block-categories`. `MageOS_Blog` renders neither
  on any theme.
- `view/frontend/taiwind/` — misspelled, so `hyva-sources` never found it, which is why none of
  the previous CSS ever reached a build.

### Verified

Against Hyvä 3 (Tailwind 4) on `Hyva/default`, with `MageOS_Blog` 1.3, in the containerised rig:

- `setup:di:compile` passes — previously impossible, since `etc/frontend/di.xml` declared a type
  for a class no `require` entry pulled in.
- All six unit tests pass. They pin the three ways `Hyva\Theme\ViewModel\Navigation` discards a
  malformed menu node **without erroring**: the `is_parent_active` sweep, the id format
  `flattenTree()` expects, and the cache identities the hour-long block cache is keyed on.
- Every storefront page renders Hyvä markup, confirmed by markers the Luma templates do not
  emit. The blog index, post detail, category, tag, author, search, pagination and both product
  cases are captured in [`docs/storefront.md`](docs/storefront.md).
- A product with no linked posts emits nothing — no empty section.
- The menu entry appears and disappears with `mageos_blog/general/enabled`.
- On a Luma theme every page still renders the original `MageOS_Blog` templates, so the
  fallback is inert off Hyvä.

### Known limitations

- **Hyvä 2 is best-effort.** Templates are shared across both Tailwind majors, so a utility
  resolving under only one produces nothing on the other. Hyvä 3 is what gets verified.
- Social share and the blog sidebar are not rendered here. Both are blocked upstream — the
  ViewModel and config exist in `MageOS_Blog` but no template renders them on any theme, and
  building them on Hyvä first would make the Hyvä storefront the reference implementation for a
  Luma feature.
