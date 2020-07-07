<?php
namespace Germania\ConfigReader;

interface ConfigReaderInterface
{
    /**
     * @param string[] $files
     */
    public function __invoke( ... $files );

}