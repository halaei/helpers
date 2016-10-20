<?php

namespace Halaei\Helpers\View;

use Illuminate\View\Factory;

class ViewFactory extends Factory
{
    protected function extendSection($section, $content)
    {
        if (isset($this->sections[$section])) {
            $content = $this->sections[$section];
        }
        $this->sections[$section] = $content;
    }

    public function yieldContent($section, $default = '')
    {
        $sectionContent = $default;
        if (isset($this->sections[$section])) {
            $sectionContent = $this->sections[$section];
        }
        return $sectionContent;
    }
}
