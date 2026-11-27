<p align="center">
  <a href="https://flywp.com">
    <picture>
      <source media="(prefers-color-scheme: dark)" srcset=".github/flywp-logo-dark.svg">
      <img src=".github/flywp-logo.svg" alt="FlyWP" width="220">
    </picture>
  </a>
</p>

# FlyWP Docker Compose App templates

[FlyWP](https://flywp.com) is a control panel for your own servers. A Docker Compose App is a site type of FlyWP: FlyWP runs a Docker Compose file on your server, with a domain and HTTPS.

The templates of this repository show in the template list of FlyWP ("Start from Template"). A person selects a template, types a few values, and FlyWP deploys the app.

## How a template gets to FlyWP

1. You send a pull request to the `develop` branch.
2. CI checks each template (`bin/check.php`).
3. A member of the FlyWP team reviews the pull request and merges it.
4. The FlyWP team tests `develop` on its test server.
5. The FlyWP team merges `develop` into `main`. FlyWP reads `main`: a new or changed template shows in FlyWP within one hour.
6. FlyWP runs its own safety check on each template. A template that fails it does not show, and its last good version stays.

FlyWP keeps its own copy of the templates. A page of FlyWP never reads this repository.

## What is in this repository

```
templates/<slug>/template.json        what the template list shows, and the settings of the app
templates/<slug>/docker-compose.yml   the compose file
templates/<slug>/logo.svg             the logo (or logo.png)
schema/template.schema.json           the rules of template.json
bin/check.php                         the check of each template
```

## Check the templates on your computer

With PHP 8.2 or later and Composer:

```sh
composer install
php bin/check.php            # the rules of each template
php bin/check.php --docker   # also: docker compose config (needs Docker)
BASE_REF=origin/develop php bin/check.php   # also: a changed template has a higher version
```

With Docker only:

```sh
docker run --rm -v "$PWD":/app -w /app composer:2 sh -c 'composer install -q && php bin/check.php'
```

## Contribute

- To add or change a template, read [CONTRIBUTING.md](CONTRIBUTING.md).
- To ask for a template or to tell us about a broken one, [open an issue](https://github.com/flywp/compose-templates/issues/new/choose).
- For a security problem, read [SECURITY.md](SECURITY.md). Do not open a public issue.
- Everybody who takes part obeys the [code of conduct](CODE_OF_CONDUCT.md).

## Licence

- [MIT](LICENSE).
- The logos are trademarks of their owners. The licence does not cover them.
