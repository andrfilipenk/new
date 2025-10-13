<?php

use Core\Di\Injectable;
// app/_Core/UI/Layout.php


class Layout {



    use Injectable;


    public function getMessages() {
        $session  = $this->getDI()->get('session');
        if ($session->has('messages')) {
            $messages = $session->get('messages');
            $session->remove('messages');
            return $messages;
        }
        return null;
    }
}