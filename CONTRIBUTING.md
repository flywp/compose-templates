# Add or change a template

## Before you start

- For a new app, you can first [request the template](https://github.com/flywp/compose-templates/issues/new?template=request-template.yml). Then we can tell you if FlyWP can run it.
- Fork this repository and make a branch from `develop`.
- Send the pull request to `develop`. The FlyWP team merges `develop` into `main` after a test. FlyWP reads `main`.
- A template pull request changes only `templates/`, and the category list of the schema when it adds a category. Send a change to `bin/`, `schema/` or `.github/` in its own pull request.

## The folder

Make the folder `templates/<slug>/`. The slug has lower case letters, digits and hyphens (`open-webui`). The folder has three files and no other file:

- `template.json`
- `docker-compose.yml`
- `logo.svg` or `logo.png`

## template.json

`schema/template.schema.json` has each field and its rule. An editor that reads JSON Schema shows the rules when you type.

- `slug`: the name of the folder.
- `version`: the version of the template, not of the app. Start at `1.0.0`.
- `category`: one of the categories of the schema. To add a category, change the schema in the same pull request.
- `summary`: one line for the card. `description`: two or three sentences, what the app does and who it is for.
- `licence` and `licence_note`: the licence of the app, and what it means for a customer who hosts it.
- `memory_mb`: the `mem_limit` of the services together, in MB.
- `cpu` and `disk_gb`: what the app needs at least.
- `domain_service` and `domain_port`: the service and the port that the domain of the app goes to.
- `settings`: the values that the person types at the create (an admin email, for example). `rule` is `text`, `email` or `url`. `secret: true` saves the value as a secret.
- `secrets`: the values that FlyWP makes at the create. `kind` is `password`, `hex-32`, `base64-32` or `base64-64`.
- `url_setting`: the variable that gets the address of the app (`https://<domain>`). `host_setting`: the variable that gets the domain. Use `null` when the app does not need it.
- `website`: the address of the project. It starts with `https://`.
- `whats_new`: what this version of the template changes. FlyWP shows it with the update.
- `first_steps`: what the person does when the app runs. Say who becomes the admin and where the password is.
- `security`: `true` when this version fixes a security problem. FlyWP shows its update in red.

## docker-compose.yml

- Use the official image that the project publishes, from a public registry. Do not use `build`.
- Give each image an exact version tag (`ghost:5.116.0`). Do not use `latest`, `main`, `master` or `stable`.
- Give each service a `mem_limit` in MB (`512m`).
- Use only named volumes (`data:/var/lib/data`). Do not mount a folder or a file of the server. A FlyWP backup copies the named volumes.
- Write each variable as `${NAME}`, with no default value. Each variable is a setting, a secret, `url_setting` or `host_setting` of `template.json`, and each of them is in the file.
- Write each literal `$` as `$$`. Compose reads a single `$` as a variable.
- Put the port of the domain in the `expose` of its service. FlyWP connects the domain. Do not use `ports`: FlyWP publishes no port of the server, and it will refuse a reserved host port (80, 443, 22, 3306, 20022) in a later release.

FlyWP adds the rest to each service: the HTTPS entry, a private network, a restart rule, limits, labels and log rotation.

## What the safety check of FlyWP refuses

FlyWP runs its safety check on each template when it reads `develop` (the test server) and `main`. CI does not run this check. A template that fails it does not show in FlyWP.

The check permits only the keys that it knows. This list has the refusals that you can expect, but it is not complete. Do not use these keys and values:

- `privileged: true`, `sysctls`, `devices`, `device_cgroup_rules`, `gpus`, `deploy.resources.reservations.devices`, and `cap_add` with a capability that Docker does not give by default.
- `security_opt`, but `no-new-privileges:true` is permitted.
- `host` in `network_mode`, `pid`, `ipc`, `uts`, `userns_mode` or `cgroup`.
- A `network_mode` other than `none` or `service:<a service of this file>`. `external_links`, and `container:` in `network_mode`, `pid`, `ipc` or `volumes_from`.
- The Docker socket (`docker.sock`, `use_api_socket`).
- `extends` and `include`.
- `secrets`, `configs`, `models`, `provider`, `dns`, `dns_search`, `dns_opt`, `group_add`, `annotations`, `mac_address`, `isolation`, `credential_spec`, `pre_start`, `post_start`, `pre_stop`.
- Host controls: `runtime`, `cgroup_parent`, `oom_kill_disable`, `oom_score_adj`, `storage_opt`, `blkio_config`.
- A volume or a network with `external: true`. A volume `driver` other than `local`, or `driver_opts`. A network `driver` other than `bridge`, or `driver_opts`, `ipam`, `enable_ipv4` or `enable_ipv6`.
- More than two networks, not counting `default`.
- In the `networks` of a service: a key other than `aliases` (for example `ipv4_address`), more than five aliases, or an alias that starts with `flywp`.
- A service, volume or network name that starts with `flywp`, that is only digits, or that has a character other than letters, digits, `_`, `.` and `-`.
- A network `name` that FlyWP uses (`wordpress-sites`, `db-network`, `site-network`, or a name that starts with `flywp` or `flyapp-`).
- Any other key.

FlyWP removes or replaces these keys, values and services:

- `container_name`, `env_file`, `logging`, `scale`, `pull_policy`.
- The limit keys (`cpus`, `pids_limit`, `mem_reservation`, the limits of `deploy`, and more). FlyWP sets its own limits, and uses `mem_limit` as the memory of the service.
- A service with `profiles`: FlyWP does not start it.
- The top-level `name` and `version`, and the `name` of a volume or a network.
- Environment variables that start with `VIRTUAL_`, `LETSENCRYPT_` or `ACME_`, and `CERT_NAME`, `HTTPS_METHOD`, `HSTS`, `NETWORK_ACCESS`, `SSL_POLICY`, `ENABLE_IPV6`, `SERVER_TOKENS`, `TRUST_DOWNSTREAM_PROXY`, in any letter case.
- Labels that start with `ofelia.`, `com.docker.`, `com.github.nginx-proxy.` or `flywp.`.

## The logo

- `logo.svg` or `logo.png`, at most 100 KB.
- An SVG has no script, no event attribute (`onload`) and no link to another address.
- Use the logo of the project, as the project lets others use it.

## Change a template

- Make `version` higher: `1.0.1` for a fix, `1.1.0` for a new version of the app, `2.0.0` for a change that the person must do something for.
- Write the change in `whats_new`.
- The apps that run this template get the update offer in FlyWP. The person chooses when to update.

## Before you send the pull request

1. Run `php bin/check.php` (with `--docker` if you have Docker), or the Docker command of the [README](README.md#check-the-templates-on-your-computer).
2. If you have a FlyWP account: make an app from your file on a FlyWP server ("Paste a file" on the create page), and do the `first_steps`. If FlyWP asks for advanced mode, the template is refused. If you do not have an account, the FlyWP team does this test.
3. Fill in the checklist of the pull request.

## After the merge

- The FlyWP team tests `develop` and merges it into `main`.
- If the safety check of FlyWP refuses your template, a member of the team tells you on the pull request. The team fixes or removes the template before the release to `main`.

## Release (FlyWP team)

- Before you approve the workflow run of an outside contributor, read each change to `.github/`, `bin/`, `schema/` and `composer.*`.
- Before each release, read the sync result of the test server. Fix or remove each refused template on `develop`.
- Release with a pull request from `develop` to `main`, with a merge commit. `main` permits no other merge method.
- After a fix on `main`, merge `main` back into `develop`.

## Remove a template

Remove its folder. The apps that run it keep running, and they get no update.

## A security problem

Do not open a public issue or a pull request. Read [SECURITY.md](SECURITY.md).
