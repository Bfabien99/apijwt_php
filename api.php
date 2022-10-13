<?php

class Api extends Rest
{
    public function __construct()
    {
        parent::__construct();
        $db = new DbConnect();
        $this->dbConn = $db->connect();
    }

    public function generateToken()
    {
        $email = $this->validateParameter('email', $this->param['email'], STRING);
        $pass = $this->validateParameter('pass', $this->param['pass'], STRING);

        try {
            $stmt = $this->dbConn->prepare("SELECT * FROM users WHERE email = :email AND password = :pass");
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":pass", $pass);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!is_array($user)) {
                $this->returnResponse(INVALID_USER_PASS, "Email or Password is incorrect");
            }

            if ($user['active'] == 0) {
                $this->returnResponse(USER_NOT_ACTIVE, "User is not activated. Please contact admin");
            }

            // Création du Token
            $payload = [
                'iat' => time(),
                'iss' => 'localhost',
                'exp' => time() + (60*60),
                'userId' => $user['id']
            ];
            $token = JWT::encode($payload, SECRETE_KEY);

            $data = ['token' => $token];
            $this->returnResponse(SUCCESS_RESPONSE, $data);
        } 
        catch (Exception $e) {
            $this->throwError(JWT_PROCESSING_ERROR, $e->getMessage());
        }
    }

    public function addCustomer() {
        $name = $this->validateParameter('name', $this->param['name'], STRING, false);
        $email = $this->validateParameter('email', $this->param['email'], STRING, false);
        $addr = $this->validateParameter('addr', $this->param['addr'], STRING, false);
        $mobile = $this->validateParameter('mobile', $this->param['mobile'], INTEGER, false);

        $cust = new Customer;
        $cust->setName($name);
        $cust->setEmail($email);
        $cust->setAddress($addr);
        $cust->setMobile($mobile);
        $cust->setCreatedBy($this->userId);
        $cust->setCreatedOn(date('Y-m-d'));

        if(!$cust->insert()) {
            $message = 'Failed to insert.';
        } else {
            $message = "Inserted successfully.";
        }

        $this->returnResponse(SUCCESS_RESPONSE, $message);
    }

    public function getAllCustomers() {

        $cust = new Customer;
        $customers = $cust->getAllCustomers();
        if(!is_array($customer)) {
            $this->returnResponse(SUCCESS_RESPONSE, ['message' => 'Customers not found.']);
        }

        $this->returnResponse(SUCCESS_RESPONSE, $customers);
    }

    public function getCustomerDetails() {
        $customerId = $this->validateParameter('customerId', $this->param['customerId'], INTEGER);

        $cust = new Customer;
        $cust->setId($customerId);
        $customer = $cust->getCustomerDetailsById();
        if(!is_array($customer)) {
            $this->returnResponse(SUCCESS_RESPONSE, ['message' => 'Customer details not found.']);
        }

        $this->returnResponse(SUCCESS_RESPONSE, $customer);
    }

    public function updateCustomer() {
        $customerId = $this->validateParameter('customerId', $this->param['customerId'], INTEGER);
        $name = $this->validateParameter('name', $this->param['name'], STRING, false);
        $addr = $this->validateParameter('addr', $this->param['addr'], STRING, false);
        $mobile = $this->validateParameter('mobile', $this->param['mobile'], INTEGER, false);

        $cust = new Customer;
        $cust->setId($customerId);
        $cust->setName($name);
        $cust->setAddress($addr);
        $cust->setMobile($mobile);
        $cust->setUpdatedBy($this->userId);
        $cust->setUpdatedOn(date('Y-m-d'));

        if(!$cust->update()) {
            $message = 'Failed to update.';
        } else {
            $message = "Updated successfully.";
        }

        $this->returnResponse(SUCCESS_RESPONSE, $message);
    }

    public function deleteCustomer() {
        $customerId = $this->validateParameter('customerId', $this->param['customerId'], INTEGER);

        $cust = new Customer;
        $cust->setId($customerId);

        if(!$cust->delete()) {
            $message = 'Failed to delete.';
        } else {
            $message = "deleted successfully.";
        }

        $this->returnResponse(SUCCESS_RESPONSE, $message);
    }
}
