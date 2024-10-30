#!/usr/bin/env php
<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For a list of contributors, please see the CONTRIBUTORS.md file
 * @see https://github.com/manhart/pool/blob/master/CONTRIBUTORS.md
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, or visit the following link:
 * @see https://github.com/manhart/pool/blob/master/LICENSE
 *
 * For more information about this project:
 * @see https://github.com/manhart/pool
 */

//disable timeout
set_time_limit(0);
//the implicit flush is turned on, so output is immediately displayed
ob_implicit_flush(1);
//don't tolerate errors
set_error_handler(function($severity, $message, $file, $line) {
    echo "got error $message";
    throw new ErrorException($message, 0, $severity, $file, $line);
});

$options = getopt('vh', ['vv::', 'recipe::', 'help', 'insecure-transports', 'source-dir::']);

define('DEBUG', array_key_exists('vv', $options));
define('VERBOSE',  DEBUG || array_key_exists('v', $options));
define('INSECURE_TRANSPORTS', array_key_exists('insecure-transports', $options));
define('SOURCE_DIR', $options['source-dir'] ?? '../');

if (array_key_exists('h', $options) || array_key_exists('help', $options)) {
    printHelp($argv);
}

// Check if SOURCE_DIR environment variable is set or fallback to default
$sourceDir = rtrim(SOURCE_DIR ?: getenv('SOURCE_DIR') ?: '../', '/');
if (!is_dir($sourceDir)) {
    fwrite(STDERR, "Error: SOURCE_DIR '$sourceDir' is not a valid directory.\n");
    exit(1);
}
$imagePatterns = [
    '*.jpg',    // JPEG images
    '*.jpeg',   // JPEG images (alternative extension)
    '*.png',    // PNG images
    '*.gif',    // GIF images
    '*.bmp',    // BMP images
    '*.webp',   // WebP images
    '*.svg',    // SVG vector images
    '*.tif',    // TIFF images
    '*.tiff',   // TIFF images (alternative extension)
    '*.ico',    // ICO images (for favicons)
];
$publish = "/public/";
$appDir = '/virtualweb2/';
$projects = [
    'g7system' => [
        'components' => ['g7system/skins', 'commons/skins', 'g7system/guis', 'commons/guis'],
        'includes' => ['3rdParty/_3rdPartyResources.php', 'commons/g7-bootstrap.php',],
        'web-artifacts' => ['g7system/js', 'g7system/serviceWorker.js', 'commons/js', '3rdParty'],
        'storeURL' => 'file:///artifacts/',
        'artifactPattern' => ['*.js', '*.css', '*.webmanifest', '*.json', '*.wav', '*.mp3'] + $imagePatterns,
        'publish' => $publish,
        'appDir' => $appDir,
    ],
];

// Announce steps and log timing
$timers = [];
if (VERBOSE) echo "Starting project preparation...\n";
$start = microtime(true);
foreach ($projects as $key => $project) {
    $projectStart = microtime(true);
    prepareProject($key, $project, $sourceDir);
    $timers["Project '$key' preparation"] = microtime(true) - $projectStart;
}
$timers['Total prepare'] = microtime(true) - $start;

if (VERBOSE) echo "Publishing projects...\n";
$publishStart = microtime(true);
publish($publish, $projects);
$timers['Publish'] = microtime(true) - $publishStart;

// Log detailed timing information
foreach ($timers as $action => $time) {
    if (VERBOSE) echo "$action time: " . number_format($time, 4) . " seconds\n";
}

// End main execution before defining functions
exit();

function printHelp(array $argv): never {
     echo <<<HELP
Usage: $argv[0] [--help|-h] [--verbose|-v] [--vv[=level]] [--recipe=<name>] [--insecure-transports] [--source-dir=<path>]

Options:
  -h, --help              Show this help message and exit
  -v, --verbose           Enable verbose output
  --vv[=level]            Enable debug mode with an optional level (default: 1)
  --recipe=<name>         Specify a recipe\n  --insecure-transports   Allow fetching sources over insecure transports
  --source-dir=<path>     Specify the source directory (default: '../')
HELP;
    exit();
}

/** @throws Exception
 * Copies sources and artifacts to their destination and generates the artifact index
 */
function prepareProject(string $projectKey, array $projectConfig, string $sourceDir): void {
    $artifactMap = [];
    ['artifactPattern' => $artifactPattern, 'storeURL' => $storeURL,
        'components' => $components, 'web-artifacts' => $webArtifacts,
        'appDir' => $appDir] = $projectConfig;

    foreach (array_merge($components, $webArtifacts) as $source) {
        unset($files);//you know real languages have something called block scope
        $type = in_array($source, $components) ? 'component' : 'web-artifact';
        // Prepend SOURCE_DIR if the component or artifact does not start with '/'
        $fullPath = str_starts_with($source, '/') ? $source : "$sourceDir/$source";
        if (is_file($fullPath)) {
            $files = new ArrayIterator([new SplFileInfo($fullPath)]);
        } elseif (!is_dir($fullPath)) {
            fwrite(STDERR, "Warning: Directory '$fullPath' for project '$projectKey' does not exist.\n");
            continue;
        }
        if (VERBOSE) echo "Processing $type $source in $fullPath\n";
        // Enumerate all files in the directory
        $files ??= new RecursiveIteratorIterator(new RecursiveDirectoryIterator($fullPath));
        foreach ($files as $file) {
            if (!$file->isFile()) continue;
            $filePath = $file->getPathname();
            $filePurpose = determineFilePurpose($filePath, $artifactPattern);
            $fileSource = $type;
            $fileData = match ([$filePurpose, $fileSource]) {
                ['artifact', 'web-artifact'], ['artifact', 'component'] => storeArtifact($filePath, $storeURL),
                ['source', 'component'] => storeSource($filePath, $appDir, $sourceDir),
                    default => [],
            };
            $fileData['role'] = $filePurpose;
            $fileData['source'] = $fileSource;
            recordFilePath($artifactMap, $sourceDir, $filePath, $fileData);
            $hash = $fileData['hash'] ?? '*ignored*';
            if (DEBUG) echo "Debug: Processed '$fileSource' file for project '$projectKey' as '$filePurpose': $hash -> '$filePath'\n";
        }
    }

    // Save $artifactMap as a PHP script
    $artifactMapPath = "$appDir/artifactMap.php";
    $artifactMapExport = var_export($artifactMap, true);
    file_put_contents($artifactMapPath, "<?php\nreturn $artifactMapExport;\n");
    if (VERBOSE) echo "Artifact map saved to $artifactMapPath for project '$projectKey'\n";
}

function determineFilePurpose(string $file, array $artifactPattern): string {
    foreach ($artifactPattern as $pattern) {
        if (fnmatch($pattern, basename($file))) {
            return 'artifact';
        }
    }
    return 'source';
}

function storeSource(string $file, string $appDir, string $sourceDir): array {
    // Create the relative path by removing everything before the component directory
    $relativePath = str_replace("$sourceDir/", '', $file);
    $destination = "$appDir/$relativePath";
    $destinationDir = dirname($destination);
    if (!is_dir($destinationDir)) {
        mkdir($destinationDir, 0755, true);
    }
    copy($file, $destination);
    return [
        'hash' => hash_file('sha256', $file),
        'location' => $destination,
    ];
}

function recordFilePath(array &$artifactMap, string $sourceDir, string $file, array $fileInfo): void {
    $relativePath = str_replace($sourceDir . '/', '', $file);
    $extension = pathinfo($file, PATHINFO_EXTENSION);
    $nestedPath = explode('/', $relativePath);
    $current = &$artifactMap[$extension];
    foreach ($nestedPath as $pathPart) {
        $current =& $current[$pathPart];
    }
    $current = $fileInfo;
}

/** @throws Exception */
function storeArtifact(string $artifactPath, string $artifactStore): array {
    $hash = hash_file('sha256', $artifactPath);
    $extension = pathinfo($artifactPath, PATHINFO_EXTENSION);
    $parsedArtifactStore = parse_url($artifactStore);
    // Validate URL
    if ($parsedArtifactStore === false || !isset($parsedArtifactStore['scheme'], $parsedArtifactStore['path'])) {
        throw new Exception("Invalid artifact store URL: $artifactStore");
    }

    ['scheme' => $scheme, 'path' => $destPath, 'query' => &$destQuery] = $parsedArtifactStore;
    $storeFunction = match ($scheme) {
        'file' => fileArtifactStore(...),
        default => throw new Exception("Protocol '$scheme' is not supported for storing artifacts")
    };
    $destPath = rtrim($destPath, '/');
    return $storeFunction($artifactPath, $hash, $extension, $destPath, $destQuery);
}

function fileArtifactStore($artifactPath, $hash, $extension, $path): array {
    $dir = sprintf('%s/%s/', $path, substr($hash, 0, 2));
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $hashedFileName = "$hash.$extension";
    $location = "$dir$hashedFileName";
    copy($artifactPath, $location);
    if (DEBUG) echo "Debug: Stored artifact '$artifactPath' at '$location'\n";
    return compact(['hash', 'location']);
}

function publish(string $publish, array $projects): void {
    foreach ($projects as $name => $project) {
        $publicDir = rtrim($publish, "/") . "/$name";
        if (!is_dir($publicDir)) mkdir($publicDir, 0755, true);
        // Generate the public entrypoints
        $entryPoint = $publicDir . '/index.php';
        $includeFiles = implode("\n", array_map(fn($file) => "require_once '{$_SERVER['DOCUMENT_ROOT']}/$file';", $project['includes']));
        $artifactMapInclude = "require_once '{$_SERVER['DOCUMENT_ROOT']}/artifactMap.php';\n";
        file_put_contents($entryPoint, <<<INDEX
<?php
// Entrypoint for project {$name}
$artifactMapInclude
$includeFiles
INDEX
        );
        if (VERBOSE) echo "Entrypoint created at $entryPoint\n";
    }

    if (!getenv(getenv('SOURCE_DATE_EPOCH'))) return;
    // Recursively set SOURCE DATE EPOCH on all app dirs and the publish dir
    $dirs = array_merge([$publish], array_column($projects, 'appDir'));
    foreach ($dirs as $dir) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $file) {
            touch($file->getPathname(), getenv('SOURCE_DATE_EPOCH'));
            if (DEBUG) echo "Debug: Set SOURCE_DATE_EPOCH for file '{$file->getPathname()}'\n";
        }
    }
    if (VERBOSE) echo "SOURCE DATE EPOCH set for all files in app and publish directories\n";
}
