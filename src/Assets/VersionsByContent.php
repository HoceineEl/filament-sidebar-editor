<?php

declare(strict_types=1);

namespace HoceineEl\SidebarEditor\Assets;

trait VersionsByContent
{
    public function getVersion(): string
    {
        $path = $this->getPath();

        return $path !== null && is_file($path)
            ? parent::getVersion().'.'.substr((string) md5_file($path), 0, 8)
            : parent::getVersion();
    }
}
