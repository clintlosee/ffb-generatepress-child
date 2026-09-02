# Fly Fishing Basics - GeneratePress Child Theme

## What this does
- Adds a Scribe-style welcome row on the homepage (title | icon | tagline), editable under Appearance > Customize > Homepage Welcome
- Adds a Scribe-style featured hero to the homepage (pulls your latest post automatically)
- Adds a "Latest Articles" grid below the hero, 6 posts in 3 columns, automatically excluding whichever post is currently in the hero so nothing repeats
- Adds a "Featured Articles" grid below Latest Articles, up to 6 posts you pick in Appearance > Customize > Homepage Featured Articles (empty slots are skipped; the section hides if none are set)
- Adds a flexible widget area below Featured Articles for a newsletter signup, CTA, or other non-ad content (Appearance > Widgets > "Homepage Flexible Section")
- Adds colored category chips above post titles in archive views
- Adds a "Most Popular" sidebar widget, Info-style, ranked by comment activity in the last 90 days (falls back to most recent posts if nothing has comments)

## Requirements
- Free GeneratePress theme must already be installed and active (this is a CHILD theme, it needs the parent)

## Install steps
1. Zip this whole `flybasics-generatepress-child` folder
2. WordPress admin > Appearance > Themes > Add New > Upload Theme
3. Upload the zip, install, then Activate
4. Go to Appearance > Widgets, add the "Fly Basics: Most Popular Posts" widget to your sidebar
5. Go to Appearance > Customize > Layout > Sidebar, enable a right sidebar on your archive/post templates if it's not already showing
6. Go to Appearance > Customize > Homepage Welcome to set the title, tagline, and icon (text glyph or uploaded image). Blank title/tagline fall back to Settings > General.
7. Go to Appearance > Customize > Homepage Featured Articles to pick up to six posts for that grid. Leave slots empty to skip them.

## AvantLink gear catalog
The theme can synchronize one complete, tab-delimited AvantLink product feed
into a private WordPress catalog. Products have no local permalink; every
product image, title, and Buy button links to the merchant through AvantLink.

### First import
1. In AvantLink, rotate the feed authentication token if its download URL has
   ever been shared publicly.
2. Go to Settings > Gear Catalog.
3. Paste the base HTTPS download URL. It must use
   `datafeed.avantlink.com/download_feed.php` and contain `id` and `auth`
   parameters. The URL is stored in WordPress settings and must never be added
   to this repository.
4. Save, then select **Run import now**. The status area reports inserted,
   updated, drafted, and skipped products.
5. Enable the daily import after confirming the first run. WP-Cron runs on site
   traffic, so a low-traffic site may run it later than the exact scheduled
   time.

The importer uses the complete feed. Products missing from a successful full
download become drafts. It intentionally ignores pricing history, variants,
and incremental download modes.

### Catalog pages
Create ordinary Pages and put one shortcode in each page:

- **Gear**, slug `gear`: `[flyb_products]`
- **Reels**, slug `reels`: `[flyb_products department="Fly Reels"]`
- **Rods**, slug `rods`: `[flyb_products department="Fly Rods"]`
- **Lines**, slug `lines`: `[flyb_products department="Fly Lines"]`

The value of `department` must match the feed's Department column (a taxonomy
slug such as `fly-reels` also works). The unqualified shortcode shows
department and brand filters. A department-specific shortcode locks that
department and keeps the brand filter. All lists show 24 products per page.

Change the product card button text under Appearance > Customize > Gear Catalog.

Add the pages to a WordPress menu as needed. Set the affiliate disclosure under
Appearance > Customize > Affiliate / Ads Disclosure before publishing catalog
pages.

## Ads & disclosure
The theme provides placement and neutral wrappers only. AdSense, Site Kit,
Amazon product markup, and publisher IDs stay in plugins or widgets.

- **Homepage Mid Ad** appears between Latest Articles and Featured Articles.
- **Sidebar Ad** appears after the normal right-sidebar widget area. Put the
  Most Popular widget and other regular widgets in the primary sidebar; put
  only the ad widget in this dedicated area.
- **After Post Content Ad** appears once on single posts. It is inserted at
  `the_content` priority 18, after the Scribe post footer at 15 and before
  related-post filters that use priority 19 or later.

Add a Custom HTML, AdSense, or Site Kit widget under Appearance > Widgets.
Empty ad areas render no wrapper or spacing. Set the sitewide plain-text
disclosure under Appearance > Customize > Affiliate / Ads Disclosure; it
appears above the footer and hides when blank.

True mid-article insertion is intentionally left to Ad Inserter so it can
coordinate with tables of contents, related posts, and product plugins. This
repo has no sample `amz-inserts` markup or stable plugin class names, so the
theme does not guess at product-specific selectors; products inside a slot get
the neutral `.flyb-ad-slot` sizing, and plugin selectors can be added once real
markup is available.

## To adjust colors later
Everything is controlled from the `:root` block at the top of `style.css`.
Change a hex value there and it updates everywhere it's used, no need to
hunt through the rest of the file.

## To change the homepage hero
The large hero always shows your single most recent post. If you'd
rather pick it by hand, use WordPress's built-in "Stick to the top of
the blog" option (Edit Post > Post > Visibility > Stick to the front
page). The separate Featured Articles grid is chosen in Customize >
Homepage Featured Articles and can include that same post.

## Notes
- This intentionally does NOT touch your existing post content or theme,
  it only adds a child theme layer on top of GeneratePress
- The child stylesheet uses `style.css`'s file modification time as its
  enqueue version, so changing that file automatically busts browser caches.
  The theme header version is only the fallback when the file time is unavailable.
- Test on staging first, then activate on the live site once you're happy
- Send screenshots after activating and I can adjust spacing, sizing, or
  layout details from there
