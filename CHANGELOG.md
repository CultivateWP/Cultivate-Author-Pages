# Changelog

All notable changes to this plugin will be documented in this file.

## [1.2.0]

### Added

- Add an "Edit Author Page" toolbar link on author archives, including paginated archives, for users who can edit the assigned landing page.
- Add GitHub updates from `CultivateWP/Cultivate-Author-Pages` on the `master` branch using Plugin Update Checker 5.7, matching Cultivate Category Pages.
- Use the attached `cultivate-author-pages.zip` release asset for updates to preserve the plugin folder name.
- Register the updater on all request types so scheduled checks, WP-CLI, and external management tools can discover updates.
- Add the GitHub Update URI and declare the updater's minimum PHP requirement of 5.6.20.

### Changed

- Update the plugin description to list all three supported landing page providers.

### Fixed

- Ignore missing, trashed, or incorrectly typed author landing page assignments when rendering content or adding the toolbar link.
- Fall back to the author's display name when no first name is set in the "All posts by" heading, and make the heading translatable.
- Allow an explicit first-page query while continuing to suppress landing content on paginated author archives.
- Prevent direct access to the plugin bootstrap outside WordPress.

## [1.1.0]

### Added

- Support for the Cultivate Landing Pages plugin.

## [1.0.0]

### Added

- Initial release.
