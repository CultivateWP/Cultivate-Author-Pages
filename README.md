# Cultivate Author Pages

Assign a Cultivate landing page to a WordPress user to display its block content on the first page of that author's archive. Requires ACF and one of Cultivate Category Pages, Cultivate Landing Pages, or Cultivate Pro.

Users who can edit the assigned landing page see **Edit Author Page** in the frontend toolbar, including on paginated author archives. Missing, trashed, and non-landing assignments do not receive a link.

## Updates and releases

The plugin bundles Plugin Update Checker 5.7 and uses the same GitHub configuration as Cultivate Category Pages. Update hooks load on all request types. The updater uses GitHub releases/tags when available, with `master` as the fallback.

For each release, update the plugin header version and [CHANGELOG.md](CHANGELOG.md), then tag the reviewed commit with the matching version, such as `1.2.0`. Attach `cultivate-author-pages.zip` to the GitHub release with a single top-level `cultivate-author-pages/` folder. The updater selects this asset instead of GitHub's generated source archive. The bundled updater and its MIT license must be included in the distributed plugin; omit tests and repository metadata from the ZIP.

Sites running 1.1.0 or earlier need a manual installation of 1.2.0 to receive the updater; subsequent versions can use WordPress updates.

## Validation

Run PHP lint and the integration checks against a disposable local WordPress installation with a landing page provider active:

```sh
find . -name '*.php' -print0 | xargs -0 -n1 php -l
wp --skip-plugins=cultivate-author-pages eval-file /absolute/path/to/tests/integration.php
```

The integration checks load this checkout, create temporary users and posts, and remove them afterward. They cover toolbar permissions and destinations, pagination, invalid assignments, author heading fallback, and updater registration. An optional filename argument exports the rendered WordPress toolbar for visual inspection.
