<?php declare(strict_types=1);
/*
 * pool
 *
 * ConfigurationLoader.class.php created at 22.06.21, 09:32
 *
 * @author A.Manhart <A.Manhart@manhart-it.de>
 */

class JSONConfigurationLoader extends ConfigurationLoader
{
    /**
     * @var int Storage Engine Type
     */
    public static int $storageEngine = self::STORAGE_ENGINE_FILESYSTEM;
    protected string $fileExtension = 'json';
    protected string $mimeType = 'application/json';

    private string $filePath;
    private string $fileName;

    /**
     * Requires the "filePath" and "fileName" keys to load / store the JSON data
     *
     * @param array $options
     */
    public function setup(array $options)
    {
        if(isset($options['filePath'])) {
            $this->filePath = addEndingSlash($options['filePath']);
        }
        if(isset($options['fileName'])) {
            $this->fileName = $options['fileName'];
        }
    }

    public function loadConfiguration(): array
    {
        if(!$this->configuration_exists()) {
            return []; // todo exception?
        }
        $file = $this->getFile();
        $json = file_get_contents($file);

        $config = json_decode($json, true);
        if(json_last_error() != JSON_ERROR_NONE) {
            return []; // todo exception?
        }
        return $config;
    }

    public function saveConfiguration(array $config): bool
    {
//        $options = $this->options + ['columns' => $this->columns];
        $file = $this->getFile();
        $json = json_encode($config);
        $result = file_put_contents($file, $json);
        return ($result !== false and $result > 0);
    }

    public function configuration_exists(): bool
    {
        return file_exists($this->getFile());
    }

    public function getFile(): string
    {
        return $this->filePath.$this->fileName;
    }

    public static function getDescription(): string
    {
        return 'JSON-File';
    }
}