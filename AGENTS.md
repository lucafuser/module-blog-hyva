# AGENTS.md

Guidance for AI agents working in this repository. The user-facing documentation is
[README.md](README.md); this covers what is non-obvious and what fails silently.

## What this repo is

The Hyvä compatibility module for `mage-os/module-blog` (`MageOS_BlogHyva`, composer package
`mage-os/module-blog-hyva`). The repo root *is* the module directory — no `src/`, matching
`mage-os/module-blog`. Hyvä's own guidelines specify `src/`; that convention is deliberately not
followed here, so do not propose moving to it.

**It contains no blog logic.** One PHP class. Blocks, ViewModels, routing, GraphQL and admin all
live in `mage-os/module-blog`; this replaces what is rendered and how it is styled. If you find
yourself adding business logic, it belongs upstream.

Target is **Hyvä 3 / Tailwind 4**. `tailwind-source.css` and `tailwind.config.js` are kept as
best-effort Hyvä 2 support and are ignored by a Hyvä 3 build.

## Things that fail silently

This module is unusual in how much of it breaks with no error. Treat each of these as a hazard.

**Template paths must mirror `MageOS_Blog` exactly.** `Hyva_CompatModuleFallback` injects this
module's view directories ahead of the original's; an override fires only when the relative path
matches a template the original actually renders. A file at a path upstream does not use is
simply never loaded — no warning. Before adding an override, confirm the upstream path exists.

**The menu node has three ways to vanish.** `Plugin/Hyva/Theme/Service/Navigation.php` adds the
Blog entry, and `Hyva\Theme\ViewModel\Navigation` discards a malformed node without erroring:

- `removeChildrenWithoutActiveParent()` deletes any top-level child whose `is_parent_active` is
  strictly `false`. Set it `true` or omit it.
- `flattenTree()` keys nodes on the substring after the last hyphen, so the id needs Hyvä's
  `…-node-<n>` shape. Ours is `blog-node-0`.
- The topmenu block caches for an hour on the node's `identities`. A node carrying none outlives
  the config change that should remove it.

`Test/Unit/Plugin/Hyva/Theme/Service/NavigationTest.php` pins all three. Keep it that way.

**`product/related-posts.phtml` must emit nothing when there are no posts** — not even a
wrapper. Hyvä's `product-sections.phtml` skips a section on `empty(trim($sectionHtml))`, so an
empty `<div>` produces a titled, empty section on every product with no linked posts. The
template returns early. Luma applies the same check, so this is not Hyvä-specific.

**Styling changes need a Tailwind rebuild, not a cache flush.** Templates update on
`cache:clean`; CSS does not exist until `hyva:config:generate` registers the module in
`app/etc/hyva-themes.json` and the theme is rebuilt. A correct-but-unstyled page means the build
step was skipped.

## Styling policy

A **port of the Luma stylesheet, not a redesign.** Every class `MageOS_Blog::css/blog.css` and
`blog-cards.css` style has a rule of the same name under `view/frontend/tailwind/components/`,
and templates carry `mageos-blog-*` classes **and nothing else**.

Do not add Tailwind utilities to template class attributes, and do not reach for Hyvä's own
`card` / `btn` component classes. That was tried and deliberately reverted: it spread the design
across sixteen templates, let the two themes drift, and made merchant overrides fight utilities
of equal specificity. One class, one definition, one place.

Three deviations from Luma exist, each commented where it lives: a grid on `.mageos-blog-cards`,
`margin-inline: auto` on `.mageos-blog-container`, and minimal widget styling — all because Hyvä
lacks an inherited flow Luma provides. Plus the preflight repairs in `post.css`, which restore
browser defaults Tailwind removes. Anything further needs a reason.

The `--mageos-blog-*` custom properties in `base.css` are the override surface, ported verbatim.
`.mageos-blog-related-posts` shrinks the card purely by reassigning two of them. Inlining those
values breaks that.

## Working here

**Everything runs in the rig at `/home/mark/testing`** — never PHP, Composer or MySQL on the
host, and a linked module must not have its own `vendor/` (a second copy of the project's
packages makes `setup:di:compile` fatal on a redeclared class).

```bash
bin/test-unit app/code/MageOS/BlogHyva/Test/Unit   # unit tests
bin/mage setup:di:compile                          # the real smoke test for DI wiring
bin/hyva-build                                     # after any CSS change
bin/install-hyva --theme=Magento/luma              # prove the fallback is inert off Hyvä
```

**Never run `git`.** Write the commands out for the user to run.

After a change, verify against [docs/storefront.md](docs/storefront.md) — it has a screenshot of
every page. Two checks are worth repeating because assertions do not catch them: the post body
on a rich post (preflight damage looks fine to a test and obviously wrong to an eye), and a
product with no linked posts.

If a page renders as Luma when it should be Hyvä, it is the rig's full-page cache; append a
query string to bust it rather than concluding the fallback is broken.

## Related repositories

- `../module-blog` — the module this one renders. Its `CLAUDE.md` is known to be substantially
  inaccurate; trust the code.
- `../module-blog/docs/plans/2026-08-24-pending-work.md` — upstream gaps. Two touch this module:
  the sidebar and social share both have config and a ViewModel but no template on any theme.
  Do not implement either here first; that would make the Hyvä storefront the reference
  implementation for a Luma feature.
