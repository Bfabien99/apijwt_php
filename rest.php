<?php
    require_once('constants.php');
    class Rest{
        protected $request;
        protected $serviceName;
        protected $param;

        public function __construct()
        {
            // On v"rifie la méthode de la requête
            if($_SERVER['REQUEST_METHOD'] !== 'POST'){
                $this->throwError(REQUEST_METHOD_NOT_VALID, 'Request method is not valid.');
                exit();
            }

            // On récupère les données envoyées 
            $handler = fopen('php://input', 'r');
            $this->request = stream_get_contents($handler);

            // On vérifie si les données sont valides
            $this->validateRequest($this->request);
        }

        /**
         * Permet de valider la requête
         */
        private function validateRequest()
        {
            if($_SERVER['CONTENT_TYPE'] !== "application/json"){
                $this->throwError(REQUEST_CONTENTTYPE_NOT_VALID, "Request 'content-type' is not valid. only 'application/json' allowed");
            }

            $data = json_decode($this->request, true);
            print_r($data);
        }

         /**
         * Permet de controler le processuce de l'api
         */
        protected function processApi()
        {

        }

        /**
         * Permet de valider les paramètres
         */
        protected function validateParameter($fieldName, $value, $dataType, $required)
        {

        }

        /**
         * Permet de retourner les erreurs
         */
        protected function throwError($code, $message)
        {
            header("content-type:application/json");
            $errMsg = json_encode(["error" => ['status'=>$code, 'message'=>$message]]);
            echo $errMsg; exit();
        }

        /**
         * Permet de return la reponse
         */
        protected function returnResponse()
        {

        }
    }
?>