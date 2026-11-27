# Security

## Report a problem

- Do not open a public issue or a pull request for a security problem.
- Use [Report a vulnerability](https://github.com/flywp/compose-templates/security/advisories/new) on the Security tab. Only the FlyWP team can read the report.
- Tell us the template, what can go wrong, and the steps that show the problem.

## What to report

- A template that makes an app unsafe on a server: a default password, an admin page that anybody can open, or an image with a known security problem.
- A file that passes `bin/check.php` but is dangerous on a server.

## What we do

- The FlyWP team reads each report and replies on the report.
- When we fix a template, we make its version higher and set `security` to `true`. FlyWP shows its update in red to each app that runs the template.
