# Add or change a template

## Before you start

- For a new app, you can first [request the template](https://github.com/flywp/compose-templates/issues/new?template=request-template.yml). Then we can tell you if FlyWP can run it.
- Fork this repository and make a branch from `develop`.
- Send the pull request to `develop`. The FlyWP team merges `develop` into `main` after a test. FlyWP reads `main`.

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

- Use a published image from a public registry. Do not use `build`.
- Give each image an exact version tag (`ghost:5.116.0`). Do not use `latest`, `main`, `master` or `stable`.
- Give each service a `mem_limit` in MB (`512m`).
- Use only named volumes (`data:/var/lib/data`). Do not mount a folder or a file of the server. A FlyWP backup copies the named volumes.
- Write each variable as `${NAME}`, with no default value. Each variable is a setting, a secret, `url_setting` or `host_setting` of `template.json`, and each of them is in the file.
- Write each literal `$` as `$$`. Compose reads a single `$` as a variable.
- Put the port of the domain in the `expose` of its service. FlyWP connects the domain. You do not publish a port of the server.

FlyWP adds the rest to each service: the HTTPS entry, a private network, a restart rule, limits, labels and log rotation.

## What the safety check of FlyWP refuses

FlyWP runs its safety check on each template when it reads `main`. CI does not run this check. A template that fails it does not show in FlyWP. Do not use these keys and values:

- `privileged: true`, `sysctls`, `devices`, `gpus`, and `cap_add` with a capability that Docker does not give by default.
- `security_opt`, but `no-new-privileges:true` is permitted.
- `host` in `network_mode`, `pid`, `ipc`, `uts`, `userns_mode` or `cgroup`.
- A `network_mode` other than `none` or `service:<a service of this file>`. `external_links`, and `container:` in `network_mode`, `pid`, `ipc` or `volumes_from`.
- The Docker socket (`docker.sock`, `use_api_socket`).
- `extends` and `include`.
- `secrets`, `configs`, `dns`, `dns_search`, `dns_opt`, `group_add`, `annotations`, `mac_address`, `post_start`, `pre_stop`.
- Host controls: `runtime`, `cgroup_parent`, `oom_kill_disable`, `oom_score_adj`, `storage_opt`, `blkio_config`.
- A volume or a network with `external: true`. A volume `driver` other than `local`, or `driver_opts`. A network `driver` other than `bridge`, or `driver_opts` or `ipam`.
- More than two networks, not counting `default`.
- A service, volume or network name that starts with `flywp`.
- A key that Compose does not know.

FlyWP removes or replaces these keys. They have no effect, so do not use them:

- `ports`, `container_name`, `env_file`, `logging`, `deploy`, `scale`, `pull_policy`, `profiles`.
- The top-level `name` and `version`, and the `name` of a volume or a network.
- Environment variables that start with `VIRTUAL_`, `LETSENCRYPT_` or `ACME_`.
- Labels that start with `ofelia.`, `com.docker.` or `flywp.`.

## The logo

- `logo.svg` or `logo.png`, at most 100 KB.
- An SVG has no script, no event attribute (`onload`) and no link to another address.
- Use the logo of the project, as the project lets others use it.

## Change a template

- Make `version` higher: `1.0.1` for a fix, `1.1.0` for a new version of the app, `2.0.0` for a change that the person must do something for.
- Write the change in `whats_new`.
- The apps that run this template get the update offer in FlyWP. The person chooses when to update.

## Before you send the pull request

1. Run `php bin/check.php --docker`, or the Docker command of the [README](README.md#check-the-templates-on-your-computer).
2. If you have a FlyWP account: make an app from your file on a FlyWP server ("Paste a file" on the create page), and do the `first_steps`. If you do not have an account, the FlyWP team does this test.
3. Fill in the checklist of the pull request.

## After the merge

- The FlyWP team tests `develop` and merges it into `main`.
- If the safety check of FlyWP refuses your template, a member of the team tells you on the pull request.

## Remove a template

Remove its folder. The apps that run it keep running, and they get no update.

## A security problem

Do not open a public issue or a pull request. Read [SECURITY.md](SECURITY.md).
