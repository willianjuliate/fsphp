<?php

namespace Source\Models\Faq;

use Source\Core\Model;

/**
 *
 */
class Channel extends Model
{
    /**
     * Channel construct
     */
    public function __construct()
    {
        parent::__construct("faq_channels", ["id"], ["channel", "description"]);
    }    
}