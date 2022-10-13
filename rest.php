<?php
require_once('constants.php');
class Rest
{
    protected $request;
    protected $serviceName;
    protected $param;

    public function __construct()
    {
        // On v"rifie la méthode de la requête
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
    public function validateRequest()
    {
        // Vérifie si le content-type
        if ($_SERVER['CONTENT_TYPE'] !== "application/json") {
            $this->throwError(REQUEST_CONTENTTYPE_NOT_VALID, "Request 'content-type' is not valid. only 'application/json' allowed");
        }

        $data = json_decode($this->request, true);

        // Vérifie si le champ 'name' est dans la requête
        if (!isset($data['name']) || $data['name'] == "") {
            $this->throwError(API_NAME_REQUIRED, "API name required");
        }

        $this->serviceName = $data['name'];

        // Vérifie si le champ 'param' est présent et est un tableau
        if (!is_array($data['param'])) {
            $this->throwError(API_PARAM_REQUIRED, "API PARAM is required.");
        }
        $this->param = $data['param'];
    }

    /**
     * Permet de controler le processuce de l'api
     */
    public function processApi()
    {
        $api = new API;
        $rMethod = new ReflectionMethod('API', $this->serviceName);

        if (!method_exists($api, $this->serviceName)) {
            $this->throwError(API_DOST_NOT_EXIST, "API does not exist.");
        }

        $rMethod->invoke($api);
    }

    /**
     * Permet de valider les paramètres
     */
    public function validateParameter($fieldName, $value, $dataType, $required = true)
    {
        // Vérifie si les champs requis du 'param' sont remplis
        if ($required == true && empty($value) == true) {
            $this->throwError(VALIDATE_PARAMETER_REQUIRED, $fieldName . " parameter is required.");
        }

        // Permet de controller que le type de donnée est exact
        switch ($dataType) {
            case BOOLEAN:
                if (!is_bool($value)) {
                    $this->throwError(VALIDATE_PARAMETER_DATATYPE, "Datatype is not valid for '" . $fieldName . "'. It should be boolean.");
                }
                break;

            case INTEGER:
                if (!is_numeric($value)) {
                    $this->throwError(VALIDATE_PARAMETER_DATATYPE, "Datatype is not valid for '" . $fieldName . "'. It should be numeric.");
                }
                break;

            case STRING:
                if (!is_string($value)) {
                    $this->throwError(VALIDATE_PARAMETER_DATATYPE, "Datatype is not valid for '" . $fieldName . "'. It should be string.");
                }
                break;

            default:
                $this->throwError(VALIDATE_PARAMETER_DATATYPE, "Datatype is not valid for '" . $fieldName);
                break;
        }

        return $value;
    }

    /**
     * Permet de retourner les erreurs
     */
    public function throwError($code, $message)
    {
        header("content-type:application/json");
        $errMsg = json_encode(["error" => ['status' => $code, 'message' => $message]]);
        echo $errMsg;
        exit();
    }

    /**
     * Permet de return la reponse
     */
    public function returnResponse($code, $message)
    {
        header("content-type:application/json");
        $response = json_encode(["response" => ['status' => $code, 'message' => $message]]);
        echo $response;
        exit();
    }
}
