<?php

namespace MySkeleton;

class ScriptHandler
{
    protected static $defaultPlaceholder = [];

    public static function collectGenerellInformations()
    {
        self::$defaultPlaceholder['packageName']     = self::ask('package name');
        $parts = explode('-', self::$defaultPlaceholder['packageName']);
        $suggest = '';
        foreach ($parts as $part) {
            $suggest .= ucfirst($part);
        }
        self::$defaultPlaceholder['vendorNamespace'] = self::ask('vendor namespace', $suggest);
        self::$defaultPlaceholder['description']     = self::ask('description');
    }

    public static function generateComposerJson()
    {
        $composerJson = file_get_contents('composer.json');
        if ($composerJson === null) {
            die('Failed parsing composer.json'.PHP_EOL);
        }

        $bin = self::ask('bin name');
        if (!empty($bin)) {
            $bin = ',' . PHP_EOL . '    "bin": ["' . $bin . '"]';
        }
        $composerJson = preg_replace('~,\s*"scripts[^\}]*\}~', $bin, $composerJson);
        $composerJson = preg_replace('~,\s*"files[^\]]*\]~', '', $composerJson);
        $composerJson = preg_replace('~,\s*"MySkeleton.*~', '', $composerJson);
        $composerJson = str_replace('my-skeleton', '{$packageName}', $composerJson);

        $composerJson = self::replacePlaceholder($composerJson);
        file_put_contents('composer.json', $composerJson);
    }

    public static function generateTravis()
    {
        $travisFile = file_get_contents('.travis.yml');
        $travisFile = self::replacePlaceholder($travisFile);
        file_put_contents('.travis.yml', $travisFile);
    }

    public static function generateReadme()
    {
        $suggest = ucfirst(str_replace('-', ' ', self::$defaultPlaceholder['packageName']));
        $cleanName   = self::ask('clean name', $suggest);

        $readme = file_get_contents('README.md');
        $readme = self::replacePlaceholder($readme, ['cleanName' => $cleanName]);
        file_put_contents('README.md', $readme);
    }

    public static function cleanup()
    {
        mkdir('test/' . self::$defaultPlaceholder['vendorNamespace'], 0777, true);
        mkdir('src/');
        fwrite(STDOUT, 'Attetion: you need to run composer dump-autoload after installation');
        unlink(__FILE__);
    }

    protected static function ask($question, $default = '')
    {
        fwrite(STDOUT, sprintf('Enter %s [%s] ', $question, $default));
        $input = trim(fgets(STDIN));
        return (empty($input)) ? $default : $input;
    }

    protected static function replacePlaceholder($subject, $placeholder = [])
    {
        $placeholder = array_merge(self::$defaultPlaceholder, $placeholder);
        foreach ($placeholder as $key => $value) {
            $subject = str_replace('{$' . $key . '}', $value, $subject);
        }
        return $subject;
    }
}
