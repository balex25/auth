<?php

namespace Devdojo\Auth\Traits;

trait HasConfigs
{
    public $appearance = [];

    public $language = [];

    public $settings = [];

    public function loadConfigs()
    {
        $this->appearance = $this->configToArrayObject('devdojo.auth.appearance');
        $this->language = $this->arrayToObject($this->localizedLanguageConfig());
        $this->settings = $this->configToArrayObject('devdojo.auth.settings');
    }

    private function configToArrayObject($configPath)
    {
        $configArray = config($configPath);

        return $this->arrayToObject($configArray);
    }

    private function arrayToObject($array)
    {
        if (! is_array($array)) {
            return $array;
        }

        $object = new \stdClass;
        foreach ($array as $key => $value) {
            $object->$key = $this->arrayToObject($value);
        }

        return $object;
    }

    private function localizedLanguageConfig(): array
    {
        return $this->localizeLanguageValues(config('devdojo.auth.language', []));
    }

    private function localizeLanguageValues(array $values, string $prefix = 'auth'): array
    {
        foreach ($values as $key => $value) {
            $translationKey = $prefix.'.'.$key;

            if (is_array($value)) {
                $values[$key] = $this->localizeLanguageValues($value, $translationKey);

                continue;
            }

            if (! is_string($value)) {
                continue;
            }

            $translated = __($translationKey);

            if ($translated !== $translationKey) {
                $values[$key] = $translated;
            }
        }

        return $values;
    }
}
