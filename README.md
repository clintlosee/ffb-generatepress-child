# Fly Fishing Basics - GeneratePress Child Theme

## What this does
- Adds a Scribe-style welcome row on the homepage (title | icon | tagline), editable under Appearance > Customize > Homepage Welcome
- Adds a Scribe-style featured hero to the homepage (pulls your latest post automatically)
- Adds a "Latest Articles" grid below the hero, 6 posts in 3 columns, automatically excluding whichever post is currently in the hero so nothing repeats
- Adds a "Featured Articles" grid below Latest Articles, up to 6 posts you pick in Appearance > Customize > Homepage Featured Articles (empty slots are skipped; the section hides if none are set)
- Adds a flexible widget area below Featured Articles, add a newsletter signup, CTA, ad unit, or any widget here, no code changes needed to swap it later (Appearance > Widgets > "Homepage Flexible Section")
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
- Test on staging first, then activate on the live site once you're happy
- Send screenshots after activating and I can adjust spacing, sizing, or
  layout details from there
