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
declare(strict_types=1);
//disable timeout
set_time_limit(0);
//the implicit flush is turned on, so output is immediately displayed
ob_implicit_flush();
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
$appDir = '/virtualweb';
$projects = [
    'g7system' => [
        'components' => ['g7system/skins', 'commons/skins', 'g7system/guis', 'commons/guis'],
        'includes' => ['3rdParty/_3rdPartyResources.php', 'commons/g7-bootstrap.php',],
        'web-artifacts' => ['g7system/js', 'g7system/serviceWorker.js', 'commons/js', '3rdParty'],
        'storeURL' => 'file:///artifacts/',
        'artifactPattern' => ['*.js', '*.css', '*.webmanifest', '*.json', '*.wav', '*.mp3'] + $imagePatterns,
        'appDir' => $appDir,
    ],
];

$cache = [];
$timers = [];
if (VERBOSE) echo "Starting project preparation...\n";
foreach ($projects as $key => $project) {
    $componentTimes = [];
    prepareProject($key, $project, $sourceDir, $cache, $componentTimes);
    $timers[$key] = $componentTimes;
}
// Log detailed timing information
generateTimingTable($timers);
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
function prepareProject(string $projectKey, array $projectConfig, string $sourceDir, array &$cache, array &$componentTimes): void {
    $artifactMap = [];
    ['artifactPattern' => $artifactPattern, 'storeURL' => $storeURL,
        'components' => $components, 'web-artifacts' => $webArtifacts,
        'appDir' => $appDir] = $projectConfig;
    foreach (array_merge($components, $webArtifacts) as $source) {
        unset($files);//you know real languages have something called block scope
        $isCached = array_key_exists($source, $cache);
        $componentTimes[$source] = false;
        $artifactMap[$source] =& $cache[$source];
        if ($isCached) continue;
        $startTime = microtime(true);
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
                ['source', 'component'] => ['hash' => hash_file('sha256', $filePath),],
                default => [],
            };
            $fileData['role'] = $filePurpose;
            $fileData['source'] = $fileSource;
            recordFilePath($artifactMap[$source], $sourceDir, $filePath, $fileData);
            $hash = $fileData['hash'] ?? '*ignored*';
            if (DEBUG) echo "Debug: Processed '$fileSource' file for project '$projectKey' as '$filePurpose': $hash -> '$filePath'\n";
        }
        $componentTimes[$source] = microtime(true) - $startTime;
    }

    // Save $artifactMap as a PHP script
    $artifactMapPath = "$appDir/$projectKey/artifactMap.php";
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

function recordFilePath(?array &$artifactMap, string $sourceDir, string $file, array $fileInfo): void {
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

function generateTimingTable(array $statistics): void
{
    $componentTimes = [];
    $cacheHitCount = [];
    // Step 1: Collect actual time per component and count cache hits
    foreach ($statistics as $project) {
        foreach ($project as $component => $time) {
            match (gettype($time)) {
                'double' => $componentTimes[$component] = $time,
                'boolean' => $cacheHitCount[$component]++,
            };
        }
    }
    $projectTimes = [];
    $projectCacheSavings = [];
    // Step 2: Calculate saved and actual times per project
    foreach ($statistics as $projectKey => $project) {
        $actualTime =& $projectTimes[$projectKey];
        $savedTime =& $projectCacheSavings[$projectKey];
        foreach ($project as $component => $time) {
            $timeValue = $componentTimes[$component];
            match (gettype($time)) {
                'double' => $actualTime += $timeValue,
                'boolean' => $savedTime += $timeValue,
            };
        }
    }
    // Step 3: Render the table
    $projectTable = [];
    foreach ($projectTimes as $project => $actualTime){
        $savedTime = $projectCacheSavings[$project];
        $projectTable[] = createTableColumn($actualTime, $savedTime, $project, $componentTimes, $statistics);
    }
    $projectTable[] = createTableColumn(array_sum($componentTimes), null, 'component times', $componentTimes, ['component times' => $componentTimes]);
    $cacheSavings = array_map(fn($a, $b) => ($a ?? 0) * $b, $cacheHitCount, $componentTimes);
    $projectTable[] = createTableColumn(null, array_sum($cacheSavings), 'cache hits', $componentTimes, ['cache hits' => $cacheHitCount]);
    $components = array_keys($componentTimes);
    $componentLegend = ['components' => array_combine($components, $components)];
    $projectTable[] = createTableColumn('actual time', 'saved time', 'components', $componentTimes, $componentLegend, max(array_map(strlen(...), $components)));
    $projectTable = array_map(fn(...$cols) => '|' . implode('|', $cols) . '|', ...$projectTable);
    echo implode("\n", $projectTable);
}

function createTableColumn(mixed $actualTime, mixed $savedTime, int|string $project, array $componentTimes, array $statistics, ?int $digits = null): array {
    $digits ??= 4 + ((int)max(1, log($actualTime ?? 0.0), log($savedTime ?? 0.0)));
    $width = max($digits, strlen($project), strlen('cached'));
    $column = [];
    $column[] = formatValue($project, $width, $digits);
    $column[] = str_repeat('-', $width + 2);
    foreach ($componentTimes as $component => $time) {
        $value =& $statistics[$project][$component];
        $column[] = formatValue($value, $width, $digits);
    }
    $column[] = str_repeat('=', $width + 2);
    $column[] = formatValue($savedTime, $width, $digits);
    $column[] = formatValue($actualTime, $width, $digits);
    $column[] = str_repeat('-', $width + 2);
    return $column;
}

function formatValue(null|false|float|string $value, int $width, int $digits): string {
    return match (gettype($value)) {
        'NULL' => str_repeat(' ', $width + 2),
        'string' => sprintf(" % {$width}s ", $value),
        'boolean' => sprintf(" % {$width}s ", 'cached'),
        'double' => sprintf(" % {$width}s ", sprintf(" %0$digits.3f ", $value)),
    };
}
