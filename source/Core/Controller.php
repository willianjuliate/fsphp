<?php

namespace Source\Core;

use Source\Support\Message;
use Source\Support\Seo;

/**
 *
 */
class Controller
{
    /** @var View */
    protected View $view;

    /** @var Seo */
    protected Seo $seo;

    /**
     * @var Message
     */
    protected Message $message;

    /**
     * Controller constructor
     * @param string|null $path_to_views
     */
    public function __construct(?string $path_to_views = null)
    {
        $this->view = new View($path_to_views);
        $this->seo = new Seo();
        $this->message = new Message();
    }
}