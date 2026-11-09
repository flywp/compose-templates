# FlyWP Docker Compose App templates

The templates that FlyWP shows in its template list ("Start from Template") when a person creates a Docker Compose App.

## How a template gets to FlyWP

1. A person sends a pull request with a new template or a change.
2. CI checks each template (`bin/check.php`).
3. A member of the FlyWP team reviews and merges the pull request.
4. GitHub tells FlyWP about the merge. FlyWP reads this repository again in about one minute. If the message does not arrive, FlyWP reads the repository each hour.
5. FlyWP runs its own safety check on each template. A template that fails it does not show, and its last good version stays.

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

You need PHP 8.2 and Composer. For `--docker`, you also need Docker.

```sh
composer install
php bin/check.php            # the rules of each template
php bin/check.php --docker   # also: docker compose config
BASE_REF=origin/main php bin/check.php   # also: a changed template has a higher version
```

## Add or change a template

Read [CONTRIBUTING.md](CONTRIBUTING.md).
