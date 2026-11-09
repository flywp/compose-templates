<?php

declare(strict_types=1);

/*
 * Checks each template of this repository. CI runs it on each pull request and each push to main.
 *
 *   php bin/check.php                     the rules of each template
 *   php bin/check.php --docker            also `docker compose config` of each template
 *   BASE_REF=origin/main php bin/check.php   also: a changed template has a higher version and a new whats_new
 *
 * FlyWP runs its own safety check on each template when it reads this repository. A template that
 * fails it does not show in FlyWP. These rules find most problems before the merge.
 */

use JsonSchema\Validator;
use Symfony\Component\Yaml\Yaml;

require __DIR__ . '/../vendor/autoload.php';

const ROOT = __DIR__ . '/..';
const LOGO_MAX_BYTES = 100 * 1024;
const SECRET_KINDS = ['password', 'hex-32', 'base64-32', 'base64-64'];

$docker = in_array('--docker', $argv, true);
$base = getenv('BASE_REF') ?: null;
$schema = json_decode((string) file_get_contents(ROOT . '/schema/template.schema.json'));
$errors = [];

foreach (glob(ROOT . '/templates/*', GLOB_ONLYDIR) ?: [] as $folder) {
    $slug = basename($folder);
    $fail = function (string $message) use (&$errors, $slug): void {
        $errors[] = "{$slug}: {$message}";
    };

    $files = array_map('basename', glob("{$folder}/*") ?: []);
    sort($files);
    $logos = array_values(array_intersect($files, ['logo.png', 'logo.svg']));

    if (array_diff($files, ['docker-compose.yml', 'logo.png', 'logo.svg', 'template.json']) !== [] || count($logos) !== 1 || !in_array('template.json', $files, true) || !in_array('docker-compose.yml', $files, true)) {
        $fail('the folder has template.json, docker-compose.yml and one logo (logo.svg or logo.png), and no other file');

        continue;
    }

    $raw = (string) file_get_contents("{$folder}/template.json");
    $manifest = json_decode($raw);
    $validator = new Validator;
    $validator->validate($manifest, $schema);

    if (!$validator->isValid()) {
        foreach ($validator->getErrors() as $error) {
            $fail("template.json {$error['property']}: {$error['message']}");
        }

        continue;
    }

    $template = json_decode($raw, true);

    if ($template['slug'] !== $slug) {
        $fail('the slug of template.json is the name of the folder');
    }

    checkLogo("{$folder}/{$logos[0]}", $fail);

    $text = (string) file_get_contents("{$folder}/docker-compose.yml");

    try {
        $file = Yaml::parse($text);
    } catch (Throwable $exception) {
        $fail('docker-compose.yml cannot be read: ' . $exception->getMessage());

        continue;
    }

    checkCompose($template, $text, is_array($file) ? $file : [], $fail);

    if ($docker) {
        checkWithDocker($folder, $template, $fail);
    }

    if ($base !== null) {
        checkVersion($slug, $template, $base, $fail);
    }
}

if ($errors !== []) {
    fwrite(STDERR, implode(PHP_EOL, $errors) . PHP_EOL);

    exit(1);
}

echo 'Each template passes.' . PHP_EOL;

/**
 * The values that FlyWP gives each variable of the template at the create.
 *
 * @param array<string, mixed> $template
 * @return array<string, string>
 */
function values(array $template): array
{
    $values = [];

    foreach ($template['settings'] as $setting) {
        $values[$setting['name']] = match ($setting['rule'] ?? 'text') {
            'email' => 'admin@example.com',
            'url' => 'https://example.com',
            default => 'value',
        };
    }

    foreach ($template['secrets'] as $secret) {
        $values[$secret['name']] = str_repeat('a', 32);
    }

    if ($template['url_setting'] !== null) {
        $values[$template['url_setting']] = 'https://app.example.com';
    }

    if ($template['host_setting'] !== null) {
        $values[$template['host_setting']] = 'app.example.com';
    }

    return $values;
}

/**
 * @param array<string, mixed> $template
 * @param array<string, mixed> $file
 */
function checkCompose(array $template, string $text, array $file, Closure $fail): void
{
    $services = $file['services'] ?? [];

    if (!is_array($services) || $services === []) {
        $fail('docker-compose.yml has no services');

        return;
    }

    // "${ADMIN_PASSWORD:-admin}" is a default password.
    if (preg_match('/\$\{[A-Za-z0-9_]+[:?+=-]/', $text) === 1) {
        $fail('a variable has a default value (${NAME:-value}): give it a setting or a secret');
    }

    // Compose reads a single $ as a variable: a literal $ is $$.
    if (preg_match('/(?<!\$)\$(?!\$)[A-Za-z_]/', $text) === 1) {
        $fail('write each literal $ as $$, and each variable as ${NAME}');
    }

    preg_match_all('/\$\{([A-Z0-9_]+)\}/', $text, $read);
    $read = array_values(array_unique($read[1]));
    $given = array_keys(values($template));
    sort($read);
    sort($given);

    if ($read !== $given) {
        $fail('the variables of the file (' . implode(', ', $read) . ') are not the settings and secrets of template.json (' . implode(', ', $given) . ')');
    }

    $memory = 0;

    foreach ($services as $name => $service) {
        $image = (string) ($service['image'] ?? '');

        // An exact version of each image: never "latest" or a branch that moves.
        if (preg_match('/:[^:\/]*\d[^:\/]*$/', $image) !== 1 || preg_match('/:(latest|main|master|stable)$/', $image) === 1) {
            $fail("{$name}: the image has an exact version tag (not latest or a branch): {$image}");
        }

        // Each mount is a named volume: a backup of FlyWP copies volumes, not folders of the server.
        foreach ($service['volumes'] ?? [] as $mount) {
            if (!is_string($mount) || preg_match('/^[a-z][a-z0-9_-]*:\//', $mount) !== 1) {
                $fail("{$name}: each mount is a named volume (data:/path), not a folder or a file of the server");
            }
        }

        if (preg_match('/^(\d+)m$/', (string) ($service['mem_limit'] ?? ''), $limit) !== 1) {
            $fail("{$name}: the service has a mem_limit in MB (for example 512m)");
        } else {
            $memory += (int) $limit[1];
        }

        checkScript($name, $service['entrypoint'] ?? null, $fail);
    }

    if ($memory !== $template['memory_mb']) {
        $fail("memory_mb is {$template['memory_mb']}, but the mem_limit of the services together is {$memory}");
    }

    $web = $services[$template['domain_service']] ?? null;
    // The ports of the service: its expose, and the container side of its ports ("8080:80" is 80).
    $ports = array_map(
        fn (mixed $port): int => (int) preg_replace('/\/.*$/', '', (string) last(explode(':', (string) $port))),
        [...($web['expose'] ?? []), ...($web['ports'] ?? [])],
    );

    if ($web === null) {
        $fail("domain_service {$template['domain_service']} is not a service of the file");
    } elseif (!in_array($template['domain_port'], $ports, true)) {
        $fail("the service {$template['domain_service']} has domain_port {$template['domain_port']} in its expose");
    }
}

/**
 * @param list<string> $parts
 */
function last(array $parts): string
{
    return $parts[array_key_last($parts)];
}

/**
 * A one-off script of a service ([sh|node, -c|-e, script]) that its program can read.
 */
function checkScript(string $name, mixed $entry, Closure $fail): void
{
    if (!is_array($entry) || count($entry) !== 3 || !in_array($entry[0], ['sh', 'node'], true)) {
        return;
    }

    $file = tempnam(sys_get_temp_dir(), 'template-');
    file_put_contents($file, str_replace('$$', '$', (string) $entry[2]));
    $command = $entry[0] === 'sh' ? 'sh -n ' . escapeshellarg($file) : 'node --check ' . escapeshellarg($file);

    if ($entry[0] === 'sh' || trim((string) shell_exec('command -v node')) !== '') {
        exec("{$command} 2>&1", $output, $status);

        if ($status !== 0) {
            $fail("{$name}: the script of the entrypoint has an error: " . implode(' ', $output));
        }
    }

    unlink($file);
}

/**
 * A logo of at most 100 KB: a PNG, or an SVG with no script, no event attribute and no outside link.
 */
function checkLogo(string $path, Closure $fail): void
{
    $content = (string) file_get_contents($path);

    if (strlen($content) > LOGO_MAX_BYTES) {
        $fail('the logo is larger than 100 KB');
    }

    if (str_ends_with($path, '.png')) {
        if (!str_starts_with($content, "\x89PNG\r\n\x1a\n")) {
            $fail('logo.png is not a PNG file');
        }

        return;
    }

    if (preg_match('/<svg\b/i', $content) !== 1) {
        $fail('logo.svg is not an SVG file');
    }

    if (preg_match('/<script\b|<foreignObject\b|\son[a-z]+\s*=|javascript:|(?:xlink:)?href\s*=\s*["\']\s*(?:https?:|\/\/|data:(?!image\/(?:png|jpeg);))/i', $content) === 1) {
        $fail('logo.svg has a script, an event attribute or an outside link');
    }
}

/**
 * `docker compose config`: Docker can read the file with the values that FlyWP gives.
 *
 * @param array<string, mixed> $template
 */
function checkWithDocker(string $folder, array $template, Closure $fail): void
{
    $env = tempnam(sys_get_temp_dir(), 'template-env-');
    file_put_contents($env, implode("\n", array_map(fn (string $name, string $value): string => "{$name}={$value}", array_keys(values($template)), values($template))) . "\n");
    exec('docker compose --project-name check -f ' . escapeshellarg("{$folder}/docker-compose.yml") . ' --env-file ' . escapeshellarg($env) . ' config --quiet 2>&1', $output, $status);
    unlink($env);

    if ($status !== 0) {
        $fail('docker compose config: ' . implode(' ', $output));
    }
}

/**
 * A changed template has a higher version and a new whats_new than the template of the base branch.
 *
 * @param array<string, mixed> $template
 */
function checkVersion(string $slug, array $template, string $base, Closure $fail): void
{
    $path = "templates/{$slug}";
    exec('git -C ' . escapeshellarg(ROOT) . ' cat-file -e ' . escapeshellarg("{$base}:{$path}/template.json") . ' 2>/dev/null', $output, $missing);

    // A new template: any version.
    if ($missing !== 0) {
        return;
    }

    exec('git -C ' . escapeshellarg(ROOT) . ' diff --quiet ' . escapeshellarg($base) . ' -- ' . escapeshellarg($path), $output, $changed);

    if ($changed === 0) {
        return;
    }

    $before = json_decode((string) shell_exec('git -C ' . escapeshellarg(ROOT) . ' show ' . escapeshellarg("{$base}:{$path}/template.json")), true);

    if (version_compare($template['version'], (string) ($before['version'] ?? '0.0.0'), '<=')) {
        $fail("the template changed: make its version higher than {$before['version']}");
    }

    if ($template['whats_new'] === ($before['whats_new'] ?? null)) {
        $fail('the template changed: say what changed in whats_new');
    }
}
