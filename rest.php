<?php
    class Rest{

        public function __construct(){
            $handler = fopen('php://input', 'r');
            echo $request = stream_get_contents($handler);
        }

        /**
         * Permet de valider la requête
         */
        public function validateRequest()
        {

        }

         /**
         * Permet de controler le processuce de l'api
         */
        public function processApi()
        {

        }

        /**
         * Permet de valider les paramètres
         */
        public function validateParameter($fieldName, $value, $dataType, $required)
        {

        }

        /**
         * Permet de retourner les erreurs
         */
        public function throwError($code, $message)
        {

        }

        /**
         * Permet de return la reponse
         */
        public function returnResponse()
        {

        }
    }
?>