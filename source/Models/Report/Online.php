<?php

namespace Source\Models\Report;

use Source\Core\Model;
use Source\Core\Session;

class Online extends Model
{
    private int $session_time;
    
    /**
     * 
     * @param int $session_time
     */
    public function __construct(int $session_time = 20)
    {
        $this->session_time = $session_time;
        parent::__construct("report_online", ["id"], ["ip", "url", "agent", "pages"]);
    }

    /** 
     * @param bool $count
     * @return null|array|int
     */
    public function findByActive(bool $count = false): null|array|int
    {
        $find = $this->find("updated_at >= NOW() - INTERVAL {$this->session_time} MINUTE");
        if ($count) {
            return $find->count();
        }
        return $find->fetch(true);
    }
    
    /**
     * 
     * @param bool $clear
     * @return Online
     */
    public function report(bool $clear = false): Online
    {
        $session = new Session();

        if (!$session->has('online')) {
            $this->user = $session->authUser ?? null;
            $this->url = filter_input(INPUT_GET, "route", FILTER_SANITIZE_SPECIAL_CHARS) ?? "/";
            $this->ip = filter_input(INPUT_SERVER, "REMOTE_ADDR");
            $this->agent = filter_input(INPUT_SERVER, "HTTP_USER_AGENT");

            $this->save();
            $session->set("online", $this->id);
            return $this;
        }

        $find = $this->findById($session->online);

        if (!$find) {
            $session->unset("online");
            return $this;
        }

        $find->user = $session->authUser ?? null;
        $find->url = filter_input(INPUT_GET, "route", FILTER_SANITIZE_SPECIAL_CHARS) ?? "/";
        $find->pages += 1;
        $find->save();

        if ($clear) {
            $this->clear();
        }

        return $this;
    }
    
    /**
     * 
     * @return void
     */
    public function clear(): void
    {
        $this->delete("updated_at <= now() - interval {$this->session_time} minute", null);
    }   
}