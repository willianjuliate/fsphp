<?php

namespace Source\Core;

use League\Plates\Engine;

/**
 * FSPHP | Class View
 *
 * @author Robson V. Leite <cursos@upinside.com.br>
 * @package Source\Core
 */
class View
{
    /** @var Engine */
    private Engine $engine;

    /**
     * View constructor.
     * @param string $path
     * @param string $ext
     */
    public function __construct(string $path = CONF_VIEW_PATH, string $ext = CONF_VIEW_EXT)
    {
        $this->engine = new Engine($path, $ext); //Engine::create($path, $ext);
    }

    /**
     * @param string $name
     * @param string $path
     * @return View
     */
    public function path(string $name, string $path): View
    {
        $this->engine->addFolder($name, CONF_VIEW_PATH . $path);
        return $this;
    }

    /**
     * @param string $template_name
     * @param array $data
     * @return string
     */
    public function render(string $template_name, array $data): string
    {
        return $this->engine->render($template_name, $data);
    }

    /**
     * @return Engine
     */
    public function engine(): Engine
    {
        return $this->engine;
    }
}