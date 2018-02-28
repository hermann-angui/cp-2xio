<?php

// src/AppBundle/Controller/ApiController.php

namespace AppBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations\Route;

class ApiController extends FOSRestController
{
    /**
     * @Route("/api/v1/test")
     */
    public function indexAction()
    {
        $data = array(
            "@api" => "Bienvenue sur l'API rest code postaux",
            "@version" => "1.0",
            "@description" => "API servant à trouver une liste de communes et leurs code postaux faisant parti d'un rayon donné autour d'une ville en France",
            "@author" => "Angui hermann",
            "@email" => "ha@link2b.fr",
            "@company" => "Link To Business"
        );
        $view = $this->view($data);
        return $this->handleView($view);
    }
}