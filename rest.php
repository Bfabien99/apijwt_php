<?php
require_once('constants.php');
class Rest
{
    protected $request;
    protected $serviceName;
    protected $param;
    protected $dbConn;
    protected $userId;

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

        // Verification du token JWT
        $db = new DbConnect;
        $this->dbConn = $db->connect();

        if ('generatetoken' != strtolower($this->serviceName)) {
            $this->validateToken();
        }
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
     * Permet de valider le token JWT
     */
    public function validateToken()
    {
        try {
            $token = $this->getBearerToken();
            $payload = JWT::decode($token, SECRETE_KEY, ['HS256']);

            $stmt = $this->dbConn->prepare("SELECT * FROM users WHERE id = :userId");
            $stmt->bindParam(":userId", $payload->userId);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!is_array($user)) {
                $this->returnResponse(INVALID_USER_PASS, "This user is not found in our database.");
            }

            if ($user['active'] == 0) {
                $this->returnResponse(USER_NOT_ACTIVE, "This user may be desactived. Please contact to admin.");
            }
            $this->userId = $payload->userId;
        } catch (Exception $e) {
            $this->throwError(ACCESS_TOKEN_ERRORS, $e->getMessage());
        }
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
     * Permet de retourner les erreurs
     */
    public function throwError($code, $message)
    {
        header("content-type:application/json");
        $errMsg = json_encode(["error" => ['statusCode' => $code, 'message' => $message]]);
        http_response_code($code);
        echo $errMsg;
        exit();
    }

    /**
     * Permet de return la reponse
     */
    public function returnResponse($code, $message)
    {
        header("content-type:application/json");
        $response = json_encode(["response" => ['statusCode' => $code, 'result' => $message]]);
        http_response_code($code);
        echo $response;
        exit();
    }

    /**
     * Recuperer 'Authorization' dans le header
     * */
    public function getAuthorizationHeader()
    {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }

    /**
     * Recuperer le token dans header
     * */
    public function getBearerToken()
    {
        $headers = $this->getAuthorizationHeader();
        // HEADER: Get the access token from the header
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        $this->throwError(ATHORIZATION_HEADER_NOT_FOUND, 'Access Token Not found');
    }
}
