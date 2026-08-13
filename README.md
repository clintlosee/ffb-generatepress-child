# Fly Fishing Basics - GeneratePress Child Theme

## What this does
- Adds a Scribe-style welcome row on the homepage (title | icon | tagline), editable under Appearance > Customize > Homepage Welcome
- Adds a Scribe-style featured hero to the homepage (pulls your latest post automatically)
- Adds a "Latest Articles" grid below the hero, 6 posts in 3 columns, automatically excluding whichever post is currently in the hero so nothing repeats
- Adds a flexible widget area below the Latest Articles grid, add a newsletter signup, CTA, ad unit, or any widget here, no code changes needed to swap it later (Appearance > Widgets > "Homepage Flexible Section")
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

## To adjust colors later
Everything is controlled from the `:root` block at the top of `style.css`.
Change a hex value there and it updates everywhere it's used, no need to
hunt through the rest of the file.

## To change what counts as "featured" on the homepage
Right now the hero always shows your single most recent post. If you'd
rather manually choose the featured post, use WordPress's built-in
"Stick to the top of the blog" option on whichever post you want featured
(Edit Post > Post > Visibility > Stick to the front page), the hero code
already respects sticky posts.

## Notes
- This intentionally does NOT touch your existing post content or theme,
  it only adds a child theme layer on top of GeneratePress
- Test on staging first, then activate on the live site once you're happy
- Send screenshots after activating and I can adjust spacing, sizing, or
  layout details from there
