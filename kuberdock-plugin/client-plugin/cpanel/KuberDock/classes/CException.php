<?php
/**
 * @project cpanel-whmcs
 * @author: Ruslan Rakhmanberdiev
 */

class CException extends Exception {
    public function __construct($message, $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    public function __toString() {
        $view = new KuberDock_View();

        return $view->render('errors/default', array(
            'message' => $this->message,
        ), false);
    }

    public function getJSON()
    {
        $view = new KuberDock_View();

        return json_encode(array(
            'error' => true,
            'message' => $view->renderPartial('errors/default', array(
                'message' => $this->message,
            ), false),
        ));
    }
}