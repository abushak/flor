Bones

A Drupal 8 theme with all the bells & whistles, without the fluff. We've kept the codebase as slim as possible to help you get your custom theme up and running as quickly as possible. This theme has been developed by Xequals for the Drupal community.

Features:
-Sass
-Compass
-Breakpoint
-Susy

How to use the theme (suggestions!)

1. Download the theme folder to your site repo at root/themes
2. Make a sub-theme (link https://www.drupal.org/docs/8/theming-drupal-8/creating-a-drupal-8-sub-theme-or-sub-theme-of-sub-theme)
3. Open terminal/command prompt
4. cd into the theme directory
5. If you don't have bundler installed, install it now (link http://bundler.io/)
	$ gem install bundler
6. Install the necessary gems
	$ bundle install
7. Open the scss files and make your changes
	- Alternatively, write directly into the css files if you prefer
8. Compile scss into css (http://compass-style.org/reference/compass/)
	$ compass watch (for one-time compiling)
	$ compass compile (for constant compiling after every save)
	$ compass reset (to re-compile from scratch)