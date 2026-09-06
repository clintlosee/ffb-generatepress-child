# Theme improvements roadmap

Saved ideas for the Fly Fishing Basics GeneratePress child theme. This is a planning doc, not a commitment to build everything.

**Bias:** theme settings, layout, and catalog wiring first. New articles and a written “Start Here” page can come later. Empty Customizer sections should render nothing.

**Already shipped (do not rebuild):** module split (`inc/`), shared post cards, popular-posts transient, ad widget slots (homepage mid, sidebar, after content), affiliate disclosure Customizer, AvantLink gear catalog + `[flyb_products]`, Gear Catalog button label.

**Already covered by plugins:** table of contents, related posts. Mid-article ads stay with Ad Inserter (or similar). Keep Amazon/amz-inserts and AdSense IDs out of theme PHP.

---

## Suggested order (highest benefit first)

Benefit here means visitor-facing improvement, reuse of posts/gear you already have, and leverage for ads/affiliates — not “most code.”

| Order | Item | Why it ranks here |
| --- | --- | --- |
| 1 | **Archive / search / `/blog` cards** | Same card UI as the homepage, immediately, on every list. No curation. |
| 2 | **Homepage composer (show/hide + section titles)** | Control Welcome, Latest, Featured, ads, Flexible, View All without editing PHP. |
| 3 | **Start Here Customizer (pick existing posts)** | Guided learning using posts you already wrote. Hides until filled. |
| 4 | **Homepage Gear teaser / department tiles / on-sale strip** | Makes the catalog visible on `/` instead of only `/gear`. |
| 5 | **In-post catalog shortcode + related-gear metabox** | Ties how-to posts to AvantLink. Biggest remaining monetization theme hook. |
| 6 | **Reading time, listing options, hero picker, `/blog` intro** | Trust and polish on content you already have. |
| 7 | **Newsletter CTA + 404 Customizer** | Capture and recovery; empty = hide. |
| 8 | **Category archive intros + optional difficulty/series meta** | Topic hubs without new articles (fill descriptions/meta when ready). |
| 9 | **Breadcrumbs, share, back-to-top, image fallback, print CSS** | Nice; smaller than lists/catalog. |
| 10 | **Git → SiteGround deploy** | Your time, not visitor UX. Do whenever zip uploads hurt. |
| 11 | **Docs sync (`AGENTS.md`) + homepage mid-ad hook priority** | Hygiene. Do with the next PHP PR. |

Implement as three packs if you want fewer, larger PRs: **A (lists)** → **B (homepage modules)** → **C (post × catalog)**. Then polish (6–9) and deploy (10).

---

## Pack A — Listing and navigation

Core idea: `/blog`, categories, tags, and search should not look like leftover GeneratePress while the homepage uses `flyb_render_post_card()`.

### Must-have
- Use the shared post card (or a row/compact variant) on archives, search, and `page-blog.php`.
- Optional Customizer: excerpt on/off, show date, chips on/off, 2 vs 3 columns.

### Branches
- **Three densities:** grid (homepage/archives), compact row (search/sidebar), featured-first (first `/blog` post larger).
- **`/blog` intro:** Customizer title + blurb so the page is not a bare “Blog” heading.
- **Filter chips** on category/tag archives: sibling categories as navigation.
- **Empty / no-results:** search field + a few latest cards + Gear link (also helps 404).
- **Author archives:** same cards + existing author bio, if you add writers later.

---

## Pack B — Homepage modules and Customizer

Core idea: designated homepage blocks you can fill or hide. Keep current hook order unless you insert a new section on purpose.

Current order: Welcome (4) → Hero (5) → Latest (6) → **Mid Ad (also 6 today)** → Featured (7) → Flexible (8) → View All (9). When touching homepage PHP, give the mid-ad slot its own priority so it cannot race Latest.

### Start Here (existing posts, not a new article)
- 4–6 post pickers + heading + optional intro; hide if empty.
- Numbered path with optional captions (“Start with gear”).
- Optional second track (e.g. Beginner vs Seasonal).
- CTA: “View all beginner posts” → a category URL.
- Optional: Start Here button in the welcome row, or a small “New here?” bar on singles pointing at slot 1.
- Optional icons per slot (reuse welcome SVGs).

### Gear on the homepage
- Teaser: heading, department or all, count (e.g. 4).
- Modes: latest imported, on-sale only (`_flyb_sale_price` vs retail), or hand-picked IDs.
- Department tiles: Rods / Reels / Lines / Gear with counts (directory, no grid).
- Featured product hero: one Customizer-picked catalog item.
- Sort: title (current), newest import, price, brand.
- Hide cards with no image; Customizer fallback image shared with post cards.

### Homepage control panel
- Show/hide: Welcome, Hero, Start Here, Latest, Mid Ad, Featured, Gear teaser, Flexible, View All.
- Editable labels: Latest / Featured headings, View All, hero “Read More”.
- Latest count: 3 / 6 / 9.
- Hero source: latest | sticky | specific post | latest in a category.
- View All target: `/blog` vs category vs a chosen URL.

### Capture and recovery
- **Newsletter:** heading, body, button, embed/HTML. Checkboxes for homepage, after singles, footer. Empty = hide.
- **404:** title, text, show search, show Start Here, show latest 3.
- **Footer extras:** extra line, Shop gear link, social repeat (keep GP footer widgets).

---

## Pack C — Posts × gear catalog

Core idea: the catalog is page-level only (`[flyb_products]` with optional `department`). Posts should be able to embed and attach products.

### Shortcode
- `[flyb_products ids="12,34"]`
- `[flyb_products department="Fly Rods" count="3" layout="row"]`
- `[flyb_products on_sale="1" count="4"]`
- Layouts: grid, horizontal row, single inline callout (image | price | button).
- Comparison table: 2–4 IDs (name, brand, price, CTA) from existing meta.

### Post UI
- Metabox: pick products → “Gear from this article” after content (coordinate with after-content ad at `the_content` 18).
- Auto-suggest by title/tag/brand overlap; you confirm.
- Category default: e.g. Gear category shows 3 Fly Rods unless the post overrides.
- Roundup checkbox: tighter product grid; maybe hide author social.
- Caption under inserts: reuse disclosure text (“We may earn a commission”) so it is not footer-only.

### Catalog extras (theme/settings, not feed rewrite)
- Text search on catalog pages (on top of department/brand).
- Empty catalog copy Customizer.

---

## Singles, trust, and topic hubs

Works on existing posts; meta/descriptions can stay blank.

- Reading time in the Scribe header.
- “Updated” badge on cards if modified within N days (Customizer).
- Optional post meta chips: Beginner / Intermediate, How-to / Gear / Seasonal.
- Series: group existing posts; prev/next in series vs chronology only.
- Key takeaways callout from excerpt or a meta field; hide if empty.
- Style the TOC plugin to match chips/navy, or a Customizer-gated H2 jump list if you drop the plugin.
- Category/tag archive header: term description + optional category image (term meta or Customizer).

---

## Polish (do after A–C unless you are already in those files)

- Breadcrumbs: Home / Category / Post; hide on homepage; Customizer separator. Prefer GP’s breadcrumb if it is already on.
- Share this post (copy link + a few networks). Separate from header *profile* icons. Customizer on/off.
- Back to top (show after scroll; orange button).
- Default card image for posts and products.
- Print CSS: hide ads, slots, nav, related-plugin chrome; keep title, dates, body.
- `Article` / `BreadcrumbList` JSON-LD only if Yoast/Rank Math does not already output it. Be careful with `Product` schema on affiliate cards.
- Skip-to-content link.
- Comment form/list styling so GP comments match cards/chips.

Optional: expose **only** orange + navy in Customizer. Do not rebuild GP typography/logo/color UI.

---

## Deploy (workflow, not visitors)

SiteGround: SSH / Git / staging are usually **GrowBig and GoGeek**, not StartUp. Check Site Tools → SSH Keys, Staging, Git.

- **GrowBig+ with SSH:** GitHub Action rsync/SFTP or `git pull` in the theme directory on push to `main` (or `production`). Optional `wp sg purge`.
- **SiteGround Git UI:** fine if the plan shows it; less flexible than Actions.
- **StartUp / no SSH:** SFTP deploy action, or keep zip upload.
- Staging (GrowBig+): deploy theme there, then push staging → live.

Keep the theme folder name stable. Never commit feed `auth` URLs, AdSense IDs, or `wp-config.php`. amz-inserts stays its own plugin/repo.

---

## Hygiene (bundle with the next code PR)

- Update `AGENTS.md` for `inc/`, monetization slots, gear catalog, and CSS `filemtime` cache-busting (README is already closer to the truth).
- Homepage mid-ad: do not share priority `6` with Latest Articles.
- Compress `screenshot.png` (~800KB) when convenient.

---

## Do not add (unless the goal changes)

- Page builder, extra CSS framework, or jQuery for layout GP hooks + CSS can do.
- AdSense snippets or publisher IDs in the theme.
- Amazon API / amz-inserts logic in this repo (CSS against real plugin markup is OK later).
- Duplicate TOC or related-posts plugins.
- Cart, membership, or courses.
- Full visual rebrand or a new parent theme.

---

## How to use this file

1. Pick a pack (A, B, or C) or a single row from the order table.
2. In a new session, implement only that slice; check open PRs before branching.
3. Customizer fields and widget slots must hide when empty.
4. Cross out or delete items here when they ship (and mention them in README if operators need them).
