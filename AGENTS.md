# Fly Fishing Basics — GeneratePress child theme

WordPress child theme for [theflyfishingbasics.com](https://theflyfishingbasics.com). Parent theme is **GeneratePress** (`Template: generatepress`). Do not edit the parent. This repo is the child only.

Site design is Scribe-style homepage + Info-style popular-posts sidebar.

## Files

| File | Role |
| --- | --- |
| `style.css` | Theme header (name, `Template`, **Version**) + all child CSS. Tokens live in `:root`. |
| `functions.php` | Enqueue, Customizer, homepage sections, widgets, GP filters. |
| `page-blog.php` | `/blog` listing when Reading is “Your latest posts” (no Posts page assigned). |
| `README.md` | Install + Customizer notes for humans. |

There is no build step, package.json, or tests. Zip the folder and upload via Appearance → Themes, or copy into `wp-content/themes/`.

## Prefixes

- PHP functions / hooks / theme mods / options: `flyb_`
- PHP classes: `Flyb_`
- CSS classes and custom properties: `flyb-` / `--flyb-*`
- Image size: `flyb-card-thumb` (600×400, hard crop)

## Homepage (front page, not paged)

Reading is expected to be **Your latest posts**, so `/` is the blog index. Custom sections hook `generate_before_main_content` in this order:

1. Welcome row (4) — Customizer “Homepage Welcome”; empty title/tagline fall back to Settings → General
2. Featured hero (5) — latest post (sticky respected); stores ID in `$GLOBALS['flyb_featured_post_id']`
3. Latest Articles grid (6) — 6 posts, excludes the hero ID
4. Flexible widget area (7) — `flyb-homepage-flexible`; render nothing if empty
5. View All Posts (8) — links to `flyb_get_blog_url()` (`/blog`)

`pre_get_posts` suppresses the default GP loop on front page page 1 only (`post__in => array(0)` — **never** `posts_per_page => 0`). Paged archives stay normal.

Front page is full-width; `/blog`, home-paged, archives, and search get a right sidebar via `generate_sidebar_layout`.

## Conventions

- Prefer GeneratePress hooks/filters over copying parent templates.
- Escape output (`esc_html`, `esc_url`, `esc_attr`). Sanitize Customizer settings.
- `wp_reset_postdata()` after every custom `WP_Query`.
- Guard PHP files with `if ( ! defined( 'ABSPATH' ) ) { exit; }`.
- Tabs for PHP (WordPress style). Change colors only in `:root` unless a new token is required.
- After CSS or enqueue changes, bump `Version` in the `style.css` theme header — it is the stylesheet cache-buster.
- If rewrite rules change, bump `flyb_rewrite_ver` in `flyb_flush_blog_rewrites()`.
- Mobile nav breakpoint is 1024px; both `generate_mobile_menu_media_query` and `generate_not_mobile_menu_media_query` must stay in sync.

## Do not

- Modify GeneratePress parent files or duplicate its templates unless a hook cannot do the job.
- Add a page builder, extra CSS framework, or jQuery for layout that CSS + GP hooks can handle.
- Put secrets, wp-config, or production credentials in this repo.
