<?php
/**
 * Created by PhpStorm.
 * User: mekanix
 * Date: 01/10/14
 * Time: 10:37
 */
include "IClient.php";

// Active assert and make it quiet
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 0);
assert_options(ASSERT_QUIET_EVAL, 1);

// Create a handler function
function my_assert_handler($file, $line, $code, $desc = null)
{
    echo "Assertion failed at $file:$line: $code";
    if ($desc) {
        echo ": $desc";
    }
    echo "\n";
}

// Set up the callback
assert_options(ASSERT_CALLBACK, 'my_assert_handler');

//Needed to install php5-curl for http

class myIClient implements IClient {

   /*
     * Null-values are used to determine whether the client have been initialized.
     */
    private $api_key = null;
    private $api_secret = null;
    private $api_token = null;
    private $api_token_expires = null;
    private $user_name = null;
    private $user_password = null; //TODO probably bad to store password

    /*
     * CONSTANTS
     */
    private $SUCCESS = "success";
    private $ID = "id"; //TODO Use this
    private $MESSAGE = "message";
    private $DETAILS = "details";
    private $CODE = "code";
    private $RESPONSE_CODE = "response_code";
    private $API_KEY = "api_key";
    private $C_USER_NAME = "email";
    private $C_USER_PASSWORD = "password";


    private $host = "https://api.etilbudsavis.dk";

    /**
     * Initialize the client with api key and secret
     *
     * initialize() initializes the client by getting the necessary credentials to create a session.
     * Create session.
     *
     * $return_message[]
     *      ['success']     boolean     TRUE if operation have been successful, else FALSE
     *      ['code']        int         Error codes according to
     *                                  http://engineering.etilbudsavis.dk/eta-api/pages/help/error-codes.html
     *      ['response_code']   string  HTTP Response code according to
     *                                  http://en.wikipedia.org/wiki/List_of_HTTP_status_codes
     *      ['id']          string      ID provided by etilbudsavis.dk. May be NULL if error occurs before contacting server.
     *      ['message']     string      Short error message
     *      ['details']     string      Detailed error message
     *
     * TODO Session created. Why? Because it is a good place to return an error if a session cannot be created.
     *
     * @param string $key Your API key goes here.
     * @param string $secret Your SECRET key goes here.
     * @return array $return_message (See above)
     */
    public function initialize($key, $secret)
    {
//        $return_message = null; //May be set here... unsure about scopes in PHP.
        if(empty($key) or empty($secret)) {
            //Illegal $key or $secret
            $return_message = array(
                $this->SUCCESS => FALSE,
                $this->MESSAGE => "Illegal \$key or \$secret!",
                $this->DETAILS => "Either \$key or \$secret are either empty, FALSE or null!"
            );
        } elseif (is_null($this->api_key)) {
            //Client have not been initialized previously -- green light to initialize client.
            $this->api_key = $key;
            $this->api_secret = $secret;
            $return_message = $this->create_session();
//            echo "\nReturn from create session".var_dump($return_message);
            if (!$return_message[$this->SUCCESS]) {
                //Failed to create session -- clean up
                $this->api_key = null;
                $this->api_secret = null;
                assert(!empty($this->api_token), "api_token is not null");
            }
        } else {
            $return_message = array(
                $this->SUCCESS => FALSE,
                $this->MESSAGE => "Client already initialized",
                $this->DETAILS => "Client already initialized!"
            );
        }

        return $return_message;

    }

    /**
     * Sign in based on $credentials
     *
     * $credential[]
     *      ['user_name']       string  username of user to be attached to session
     *      ['user_password']   string  password of user
     *
     * $return_message[]
     *      ['success']     boolean     TRUE if operation have been successful, else FALSE
     *      ['code']        int         Error codes according to
     *                                  http://engineering.etilbudsavis.dk/eta-api/pages/help/error-codes.html
     *      ['response_code']   string  HTTP Response code according to
     *                                  http://en.wikipedia.org/wiki/List_of_HTTP_status_codes
     *      ['id']          string      ID provided by etilbudsavis.dk. May be NULL if error occurs before contacting server.
     *      ['message']     string      Short error message
     *      ['details']     string      Detailed error message
     *
     * @param array $credentials See above
     * @return array $return_message See above
     */
    public function signIn($credentials)
    {
        if(empty($credentials["user_name"]) or empty($credentials["user_password"])) {
            //Illegal credentials
            $return_message = array(
                $this->SUCCESS => FALSE,
                $this->MESSAGE => "Illegal credentials!",
                $this->DETAILS => "User_name or user_password are either empty, FALSE or null!"
            );
        } elseif (!isset($this->api_key)) {
            //Client no initialized
            $return_message = array(
                $this->SUCCESS => FALSE,
                $this->MESSAGE => "Client not initialized!",
                $this->DETAILS => "Client not initialized! You need to call initialize(\$key, \$secret) first."
            );
        } elseif (isset($this->user_name)) {
            //A user is already attached to session
            //TODO Check if the user is the same -- if it is ignore request.
            $return_message = array(
                $this->SUCCESS => FALSE,
                $this->MESSAGE => "A user is already attached!",
                $this->DETAILS => "A user is already attached! You need to logout the current user first."
            );
        } else {
            $this->user_name = $credentials["user_name"];
            $this->user_password = $credentials["user_password"];
            $return_message = $this->user_signIn();
            if (!$return_message[$this->SUCCESS]) {
                //Login failed -- clean up
                $this->user_name = null;
                $this->user_password = null;
            }
        }
        return $return_message;
    }


    /**
     * Sign out
     *
     * TODO As signOut() are given no attribute assumption is only one user can be attached to at session at a time.
     *
     * $return_message[]
     *      ['success']     boolean     TRUE if operation have been successful, else FALSE
     *      ['code']        int         Error codes according to
     *                                  http://engineering.etilbudsavis.dk/eta-api/pages/help/error-codes.html
     *      ['response_code']   string  HTTP Response code according to
     *                                  http://en.wikipedia.org/wiki/List_of_HTTP_status_codes
     *      ['id']          string      ID provided by etilbudsavis.dk. May be NULL if error occurs before contacting server.
     *      ['message']     string      Short error message
     *      ['details']     string      Detailed error message
     *
     * @return array $return_message See above
     *
     */
    public function signOut()
    {
        if (!isset($this->user_name)) {
            $return_message = array(
                $this->SUCCESS => FALSE,
                $this->MESSAGE => "No user signed in!",
                $this->DETAILS => "No user signed in! A user must be signed in first."
            );
        } else {
            $return_message = $this->user_signOut();
            if($return_message[$this->SUCCESS]) {
                //User signed out -- clean up
                $this->user_name = null;
                $this->user_password = null;
            } else {
                //TODO logout failed -- should we do something like retry or should signOut() be called again by the caller?
            }
        }
        return $return_message;
    }

    /**
     * Cleanup function.
     * Must destroy session.
     *
     * $return_message[]
     *      ['success']     boolean     TRUE if operation have been successful, else FALSE
     *      ['code']        int         Error codes according to
     *                                  http://engineering.etilbudsavis.dk/eta-api/pages/help/error-codes.html
     *      ['response_code']   string  HTTP Response code according to
     *                                  http://en.wikipedia.org/wiki/List_of_HTTP_status_codes
     *      ['id']          string      ID provided by etilbudsavis.dk. May be NULL if error occurs before contacting server.
     *      ['message']     string      Short error message
     *      ['details']     string      Detailed error message
     *
     * @return array $return_message See above
     */
    public function destroy()
    {
        if (!isset($this->api_key)) {
            //TODO Redundancy? Better way to test and create error-messages and reduce clutter?
            $return_message = array(
                $this->SUCCESS => FALSE,
                $this->MESSAGE => "Client not initialized!",
                $this->DETAILS => "Client not initialized! You need to call initialize(\$key, \$secret) first."
            );
        } else {
            $return_message = $this->destroy_session();
            if($return_message[$this->SUCCESS]) {
                //Session destroyed -- clean up
                $this->api_key = null;
                $this->api_secret = null;
            } else {
                //TODO destroy failed -- should we do something like retry or should destroy() be called again by the caller?
            }
        }

        return $return_message;

    }

    /**
     */
    public function getCatalogList($options)
    {
        // TODO: Implement getCatalogList() method.
    }

    /**
     */
    public function getCatalog($options)
    {
        // TODO: Implement getCatalog() method.
    }

    /**
     */
    public function getStoreList($options)
    {
        // TODO: Implement getStoreList() method.
    }

    /**
     */
    public function getStore($options)
    {
        // TODO: Implement getStore() method.
    }

    /**
     */
    public function getOfferList($options)
    {
        // TODO: Implement getOfferList() method.
    }

    /**
     */
    public function getOffer($options)
    {
        // TODO: Implement getOffer() method.
    }

    /**
     */
    public function getDealerList($options)
    {
        // TODO: Implement getDealerList() method.
    }

    /**
     */
    public function getDealer($options)
    {
        // TODO: Implement getDealer() method.
    }

    private function create_session()
    {
        $json_array = array(
            $this->API_KEY => $this->api_key
        );
        $req_message = json_encode($json_array);
        $req_http = new HttpRequest($this->host."/v2/sessions", HttpRequest::METH_POST);
        $req_http->setContentType("application/json");
        $req_http->setBody($req_message);
        try {
            $req_http->send();
            if ($req_http->getResponseCode() >= 200 and $req_http->getResponseCode() < 300) {
                //Success

                //TODO apply error check of json_decode.
                //TODO research multidimensionality of json_decode/arrays
                $json_array = json_decode($req_http->getResponseBody(), true);
                $this->api_token = $json_array["token"];
                $this->api_token_expires = $json_array["expires"];
                $return_message = array(
                    $this->SUCCESS => true,
                    $this->RESPONSE_CODE => $req_http->getResponseCode(),
                    $this->MESSAGE => "Session token acquired.",
                    $this->DETAILS => "Session token acquired. Token: ".$this->api_token."."
                );
                echo ("\nBody dump for create session\n".$req_http->getResponseBody()."\n");
            } else {
                //Something went wrong.
                $response_header = $req_http->getResponseHeader();
                if ($response_header["Content-Type"] == "application/json") { //TODO be aware of case-sensitivity -- would be great with a getResponseContentType()
                    $return_message = $this->json_to_error_message($req_http);
                    if ($return_message[$this->CODE] == 1101) { //Token have expired
                        //TODO  Two options: 1) Clean up and prepare for caller to reinitialize
                        //      OR 2) We have all information to create a new session.
                        //BUT it should not happen here!
                    }
                } else {
                    //Did not receive a json response
                    $return_message = array(
                        $this->SUCCESS => false,
                        $this->RESPONSE_CODE => $req_http->getResponseCode(),
                        $this->MESSAGE => "Initialization failed.",
                        $this->DETAILS => "Initialization failed. Consult Error Code."
                    );
                }

            }
            return $return_message;
        } catch (Exception $e) {
            $return_message = array(
                $this->SUCCESS => false,
                $this->MESSAGE => $e,
            );
            //TODO Check if $e is eg. time-out and we could just reissue $req_http->send();
            return $return_message;
        }
    }

    private function destroy_session()
    {
        $req_http = new HttpRequest($this->host."/v2/sessions", HttpRequest::METH_DELETE);
        $headers = array(
            "X-Token" => $this->api_token,
            "X-Signature" => hash("sha256",$this->api_secret.$this->api_token)
        );
        $req_http->setHeaders($headers);
        try {
            $req_http->send();
            if ($req_http->getResponseCode() >= 200 and $req_http->getResponseCode() < 300) {
                //Request successful -- session deleted
                $return_message = array(
                    $this->SUCCESS => true,
                    $this->RESPONSE_CODE => $req_http->getResponseCode(),
                    $this->MESSAGE => "Session destroyed.",
                    $this->DETAILS => "Session destroyed."
                );
            } else {
                //Something went wrong
                $response_header = $req_http->getResponseHeader();
                if ($response_header["Content-Type"] == "application/json") { //TODO be aware of case-sensitivity -- would be great with a getResponseContentType()
                    $return_message = $this->json_to_error_message($req_http);
                } else {
                    $return_message = array(
                        $this->SUCCESS => true,
                        $this->RESPONSE_CODE => $req_http->getResponseCode(),
                        $this->MESSAGE => "Failed to destroy session.",
                        $this->DETAILS => "Failed to destroy session."
                    );
                }
            }
            return $return_message;
        } catch (Exception $e) {
            $return_message = array(
                $this->SUCCESS => false,
                $this->MESSAGE => $e,
            );
            //TODO Check if $e is eg. time-out and we could just reissue $req_http->send();
            return $return_message;
        }
    }

    private function json_to_error_message($req_http)
    {
        //TODO getResonseBody() not recognised according to PHPStorm, but running code seems to work. Why?
        $json_array = json_decode($req_http->getResponseBody(), true);
        $return_message = array(
            $this->SUCCESS => false,
            $this->CODE => $json_array[$this->CODE],
            $this->RESPONSE_CODE => $req_http->getResponseCode(),
            $this->MESSAGE => $json_array[$this->MESSAGE],
            $this->DETAILS => $json_array[$this->DETAILS],
            $this->ID => $json_array[$this->ID]
        );
        return $return_message;
    }

    private function user_signIn()
    {
        $json_array = array(
            $this->C_USER_NAME => $this->user_name,
            $this->C_USER_PASSWORD => $this->user_password
        );
        $headers = array(
            "X-Token" => $this->api_token,
            "X-Signature" => hash("sha256",$this->api_secret.$this->api_token)
        );
        $req_message = json_encode($json_array);
        $req_http = new HttpRequest($this->host."/v2/sessions", HttpRequest::METH_PUT);
        $req_http->setContentType("application/json");
        $req_http->setPutData($req_message);
        echo("\nMessage Dump\n".$req_message);
        $req_http->setHeaders($headers);
        try {
            $req_http->send();
            if ($req_http->getResponseCode() >= 200 and $req_http->getResponseCode() < 300) {
                //Request successful -- User signed in.
                $return_message = array(
                    $this->SUCCESS => true,
                    $this->RESPONSE_CODE => $req_http->getResponseCode(),
                    $this->MESSAGE => "User signed in.",
                    $this->DETAILS => "User ".$this->user_name." signed in."
                );
                //TODO apply error check of json_decode.
                //TODO research multidimensionality of json_decode/arrays
                $json_array = json_decode($req_http->getResponseBody(), true);
                $this->api_token = $json_array["token"];
                $this->api_token_expires = $json_array["expires"];

                //Dump body
                echo ("\nBody dump\n".$req_http->getResponseBody()."\n");

            } else {
                //Sign in failed.
                $response_header = $req_http->getResponseHeader();
                if ($response_header["Content-Type"] == "application/json") { //TODO be aware of case-sensitivity -- would be great with a getResponseContentType()
                    $return_message = $this->json_to_error_message($req_http);
                    if ($return_message[$this->CODE] == 1101) { //Token have expired
                        //TODO  Two options: 1) Clean up and prepare for caller to reinitialize
                        //      OR 2) We have all information to create a new session silently
                    }
                } else {
                    //Did not receive a json response
                    $return_message = array(
                        $this->SUCCESS => false,
                        $this->RESPONSE_CODE => $req_http->getResponseCode(),
                        $this->MESSAGE => "Sign in failed.",
                        $this->DETAILS => "Sign in failed. Consult Error Code."
                    );
                }
            }
            return $return_message;
        } catch (Exception $e) {
            $return_message = array(
                $this->SUCCESS => false,
                $this->MESSAGE => $e,
            );
            //TODO Check if $e is eg. time-out and we could just reissue $req_http->send();
            return $return_message;
        }


    }

    private function user_signOut()
    {

        $json_array = array(
            $this->C_USER_NAME => ""
        );
        $headers = array(
            "X-Token" => $this->api_token,
            "X-Signature" => hash("sha256",$this->api_secret.$this->api_token)
        );
        $req_message = json_encode($json_array);
        $req_http = new HttpRequest($this->host."/v2/sessions", HttpRequest::METH_PUT);
        $req_http->setContentType("application/json");
        $req_http->setPutData($req_message);
        $req_http->setHeaders($headers);
        try {
            $req_http->send();
            if ($req_http->getResponseCode() >= 200 and $req_http->getResponseCode() < 300) {
                //Request successful -- User signed in.
                $return_message = array(
                    $this->SUCCESS => true,
                    $this->RESPONSE_CODE => $req_http->getResponseCode(),
                    $this->MESSAGE => "User signed out.",
                    $this->DETAILS => "User signed out. Session still alive"
                );
                //TODO apply error check of json_decode.
                //TODO research multidimensionality of json_decode/arrays
                $json_array = json_decode($req_http->getResponseBody(), true);
                $this->api_token = $json_array["token"];
                $this->api_token_expires = $json_array["expires"];

                echo ("\nSignOutDump".$req_http->getResponseBody()."\n");
            } else {
                //Sign out failed.
                $response_header = $req_http->getResponseHeader();
                if ($response_header["Content-Type"] == "application/json") { //TODO be aware of case-sensitivity -- would be great with a getResponseContentType()
                    $return_message = $this->json_to_error_message($req_http);
                    if ($return_message[$this->CODE] == 1101) { //Token have expired
                        //TODO  Two options: 1) Clean up and prepare for caller to reinitialize
                        //      OR 2) We have all information to create a new session silently
                    }
                } else {
                    //Did not receive a json response
                    $return_message = array(
                        $this->SUCCESS => false,
                        $this->RESPONSE_CODE => $req_http->getResponseCode(),
                        $this->MESSAGE => "Sign out failed.",
                        $this->DETAILS => "Sign out failed. Consult Error Code."
                    );
                }
            }
            return $return_message;

        } catch (Exception $e) {
            $return_message = array(
                $this->SUCCESS => false,
                $this->MESSAGE => $e,
            );
            //TODO Check if $e is eg. time-out and we could just reissue $req_http->send();
            return $return_message;

        }

    }

}