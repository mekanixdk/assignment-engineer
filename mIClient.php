<?php
/**
 * Created by PhpStorm.
 * User: mekanix
 * Date: 03/10/14
 * Time: 08:51
 */
include "IClient.php";
include_once "keywords.php";


class mIClient implements IClient {

    private $api_key;
    private $api_secret;
    private $api_token;
    private $api_token_expires; //TODO don't really use this.

    private $response_body;

    //TODO don't need to store those.
    private $user_email;
    private $user_password;

    //Error handling objects
    private $last_client_code;
    private $last_exception_error;
    private $last_response_error;
    private $last_response_code;

    //private $keywords;

    /**
     * __construct() initialize keywords-object when mIClient is created.
     */
    function __construct ()
    {
        //Setting keywords
        $this->keywords = new keywords();
    }

    /**
     * Initialize the client with api key and secret
     *
     * @param   string  $key        API key
     * @param   string  $secret     API secret
     * @return  bool                Returns FALSE in case of failure. TRUE in case of success.
     */
    public function initialize($key, $secret)
    {
        /*
         * TODO it is possible to get an active session with a bogus $secret. But without a correct $secret it is not
         * possible to destroy()... at least not on the server side
         * TODO need to implement a valid_secret() test on client side
         */
        if (empty($key) or empty($secret)) {
            //Malformed $key or $secret -- return an error.
            $this->last_client_code = 5002;
            return false;
        }
        $this->clear_errors();
        $response = $this->is_session_active();
        if($response >= 200 and $response < 300) {
            //We already got an active session -- return an error.
            $this->last_client_code = 5001;
            return false;
        } elseif ($response >= 5090) {
            //An internal error-code was returned -- return an error.
            $this->last_client_code = $response;
            return false;
        } elseif ($response >= 100) {
            //An non-internal error-code was returned -- return an error.
            $this->last_client_code = 5004;
            return false;
        } else {
            $body_content = array(
                $this->keywords->STRING_API_KEY => $key
            );
            $url = $this->keywords->API_HOST.$this->keywords->API_SESSIONS;
            $response_code = $this->http_post($body_content, $url);
            if ($response_code >= 200 and $response_code < 300) {
                //Success -- initialize
                $this->api_key = $key;
                $this->api_secret = $secret;
                return true;
            } elseif ($response_code >= 5090 ) {
                //exception caught
                $this->last_client_code = $response_code;
                return false;
            } else {
                //Something went wrong -- return an error.
                $this->last_client_code = 5003;
                return false;
            }
        }
    }

    /**
     * Sign in based on $credentials
     *
     * @param array $credentials    Array containing [$STRING_USER_EMAIL => "user@mail.xx", $STRING_USER_PASSWORD => "xxxxxxx"]
     * @return bool                 Returns FALSE in case failure. TRUE in case of success.
     */
    public function signIn($credentials)
    {
        /*
         * TODO sometimes the PUT response is 200 even though we don't PUT a legitimate json. Eg. if we send a "mail":null.
         * Seems like the API handles malformed json as just another request to renew session.
         * TODO Should probably come up with a scheme to check $credentials before packing them into a PUT request.
         */
        $this->clear_errors();
        if (empty($credentials[$this->keywords->STRING_USER_EMAIL]) or empty($credentials[$this->keywords->STRING_USER_PASSWORD])) {
            //$credentials empty -- give error
            $this->last_client_code = 5011;
            return false;
        }
        $response = $this->is_session_active();
        if (!($response >= 200 and $response < 300)) {
            //We do not have an active session -- give an error.
            if($response >= 5090) {
                //Exception caught -- keep internal error code.
                $this->last_client_code = $response;
                return false;
            }  else {
                //Give error.
                $this->last_client_code = 5010;
                return false;
            }
        } else {
            //Green Light -- SignIn
            $put_data = array(
                $this->keywords->STRING_USER_EMAIL => $credentials[$this->keywords->STRING_USER_EMAIL],
                $this->keywords->STRING_USER_PASSWORD => $credentials[$this->keywords->STRING_USER_PASSWORD]
            );
            $url = $this->keywords->API_HOST.$this->keywords->API_SESSIONS;
            $response_code = $this->http_put($put_data, $url);
            if ($response_code >= 200 and $response_code < 300) {
                //Success -- User signed in.
                $this->user_email = $credentials[$this->keywords->STRING_USER_EMAIL];
                $this->user_password = $credentials[$this->keywords->STRING_USER_PASSWORD];
                return true;
            } elseif ($response_code >= 5090 ) {
                //exception caught
                $this->last_client_code = $response_code;
                return false;
            } else {
                //Something went wrong -- return an error.
                $this->last_client_code = 5012;
                return false;
            }
        }
    }

    /**
     * signOut() signs the current user out.
     *
     * @return bool     Returns FALSE in case failure. TRUE in case of success.
     */
    public function signOut()
    {
        /*
         * TODO It is possible to signOut from the API server even though not user are signed in. Guess it falls back to
         * a default of no user signed in when receiving { "email":""}
         * Guess we'll do the same then.
         */
        $this->clear_errors();
        $response = $this->is_session_active();
        if(!($response >= 200 and $response < 300) ) {
            //We do not have an active session -- give an error.
            if($response >= 5090) {
                //Exception caught -- keep internal error code.
                $this->last_client_code = $response;
                return false;
            }  else {
                //Give error.
                $this->last_client_code = 5020;
                return false;
            }
        } else {
            $put_data = array(
                $this->keywords->STRING_USER_EMAIL => ""
            );
            $url = $this->keywords->API_HOST.$this->keywords->API_SESSIONS;
            $response_code = $this->http_put($put_data, $url);
            if ($response_code >= 200 and $response_code < 300) {
                //Success -- User signed out.
                $this->user_email = null;
                $this->user_password = null;
                return true;
            } elseif ($response_code >= 5090 ) {
                //exception caught
                $this->last_client_code = $response_code;
                return false;
            } else {
                //Something went wrong -- return an error.
                $this->last_client_code = 5021;
                return false;
            }
        }
    }

    /**
     * destroy() cleanup function. Destroys session.
     *
     * @return bool     Returns FALSE in case failure. TRUE in case of success.
     */
    public function destroy()
    {
        $this->clear_errors();
        $response = $this->is_session_active();
        if($response == false) {
            //We do not have an active session -- return an error.
            $this->last_client_code = 5040;
            return false;
        } elseif ($response >= 5090) {
            //An exception-code was returned -- return an error.
            $this->last_client_code = $response;
            return false;
        } elseif ($response < 200 or $response >= 300) {
            //An non-internal error-code was returned -- return an error.
            $this->last_client_code = 5041;
            $this->last_response_code = $response;
            return false;
        } else {
            //Green Light -- destroy
            $url = $this->keywords->API_HOST.$this->keywords->API_SESSIONS;
            $response_code = $this->http_delete(null, $url);
            if ($response_code >= 200 and $response_code < 300) {
                //Success -- destroyed
                $this->reset_attributes();
                return true;
            } elseif ($response_code >= 5090 ) {
                //exception caught
                $this->last_client_code = $response_code;
                return false;
            } else {
                //Something went wrong -- return an error.
                $this->last_client_code = 5042;
                return false;
            }
        }
    }

    /**
     * getCatalogList takes an ARRAY of option according to the syntax below.
     *
     * $options[]
     *      [$LATITUDE]         (Mandatory) Latitude for your search origin.
     *      [$LONGITUDE]        (Mandatory) Longitude for your search origin.
     *      [$RADIUS]           (Mandatory) Radius in meters from your search origin.
     *      [$CATALOG_IDS[]]    (Optional)  Array of catalog ids to filter by. Ex. Array("xyz123", "yzx231").
     *      [$DEALER_IDS[]]     (Optional)  Array of dealer ids to filter by.
     *      [$STORE_IDS[]]      (Optional)  Array of store ids to filter by.
     *      [$ORDER_BY[]]       (Optional)  Array of options. Valid options:
     *                                      "popularity", "dealer", "created", "expiration_date", "publication_date",
     *                                      "distance".
     *                                      Option with - prepended gives the reverse order.
     *
     * @return mixed    FALSE in case of error. Array of json in case of success.
     */
    public function getCatalogList($options)
    {
        $this->clear_errors();
        //Test input and generate query array
        if (empty($options[$this->keywords->LATITUDE])
            or empty($options[$this->keywords->LONGITUDE])
            or empty($options[$this->keywords->RADIUS])) {
            $this->last_client_code = 5054;
            return false;
        }
        //Mandatory options
        $query = array(
            $this->keywords->LATITUDE => $options[$this->keywords->LATITUDE],
            $this->keywords->LONGITUDE => $options[$this->keywords->LONGITUDE],
            $this->keywords->RADIUS => $options[$this->keywords->RADIUS]
        );
        //Optional options
        $optional_array = array (
            $this->keywords->CATALOG_IDS,
            $this->keywords->DEALER_IDS,
            $this->keywords->STORE_IDS,
            $this->keywords->ORDER_BY,
        );
        foreach ($optional_array as &$var_option) {
            if(!empty($options[$var_option])) {
                $query = $this->add_optional($query, $var_option, $options[$var_option]);
            }
        }
        $response = $this->is_session_active();
        if($response == false) {
            //We do not have an active session -- return an error.
            $this->last_client_code = 5050;
            return false;
        } elseif ($response >= 5090) {
            //An exception was returned -- return an error.
            $this->last_client_code = $response;
            return false;
        } elseif ($response < 200 or $response >= 300) {
            //An non-internal error-code was returned -- return an error.
            $this->last_client_code = 5051;
            $this->last_response_code = $response;
            return false;
        } elseif (false) {
            //TODO HTTP response code 500... get that a lot with &catalog_ids=a4hrb,az2ra,ik6s4
            //TODO HTTP response code 404... probably should handle this?
            return false;
        } else {
            //Green Light -- destroy
            $url = $this->keywords->API_HOST.$this->keywords->API_CATALOGS;
            $response_code = $this->http_get($url, $query);
            if ($response_code >= 200 and $response_code < 300) {
                //Success -- harvest response body.

                return json_decode($this->response_body);
            } elseif ($response_code >= 5090 ) {
                //exception caught
                $this->last_client_code = $response_code;
                return false;
            } else {
                //Something went wrong -- return an error.
                $this->last_client_code = 5052;
                return false;
            }
        }
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

    /**
     * Will handle all POST request and turn $content into json before submitting.
     *
     * @param array     $content    Array of attributes that needs to go into json.
     * @param string    $url        Target url of REST-service.
     * @return int                  Returns either a HTTP response code (xxx) or internal exception error code (509x)
     */
    private function http_post($content, $url) {
        $req_http = new HttpRequest($url, HttpRequest::METH_POST);
        if(!empty($content)) {
            $req_http->setContentType("application/json");
            $req_http->setBody(json_encode($content));
        }
        if(!empty($this->api_token)) {
            //Sign POST request
            $req_http->setHeaders($this->create_signed_headers());
        }
        try {
            $req_http->send();
            $this->update_vars($req_http);
            $response_code = $req_http->getResponseCode();
            return $response_code;
        } catch (Exception $e) {
            $this->last_exception_error = $e;
            return 5090;
        }
    }

    /**
     * Will handle all GET request and turn $content into json before submitting.
     *
     * @param string $url           Target url of REST-service.
     * @param array $query          Array of queries.
     * @return int                  Returns either a HTTP response code (xxx) or internal exception error code (509x)
     */
    private function http_get($url, $query) {
        $req_http = new HttpRequest($url, HttpRequest::METH_GET);
        if(!empty($this->api_token)) {
            //Sign POST request
            $req_http->setHeaders($this->create_signed_headers());
        }
        $req_http->setQueryData($query);
        try {
            $req_http->send();
            $this->update_vars($req_http);
            $response_code = $req_http->getResponseCode();
            $this->response_body = $req_http->getResponseBody();
            return $response_code;
        } catch (Exception $e) {
            $this->last_exception_error = $e;
            return 5093;
        }
    }


    /**
     * Will handle all PUT request and turn $content into json before submitting.
     *
     * @param array     $content    Array of attributes that needs to go into json.
     * @param string    $url        Target url of REST-service.
     * @return int                  Returns either a HTTP response code (xxx) or internal exception error code (509x)
     */
    private function http_put($content, $url) {
        $req_http = new HttpRequest($url, HttpRequest::METH_PUT);
        if(!empty($content)) {
            $req_http->setContentType("application/json");
            $req_http->setPutData(json_encode($content));
        }
        if(!empty($this->api_token)) {
            //Sign PUT request
            $req_http->setHeaders($this->create_signed_headers());
        }
        try {
            $req_http->send();
            $this->update_vars($req_http);
            return $req_http->getResponseCode();
        } catch (Exception $e) {
            $this->last_exception_error = $e;
            return 5091;
        }
    }

    /**
     * Will handle all DELETE request and turn $content into json before submitting.
     *
     * @param   array     $content  Array of attributes that needs to go into json.
     * @param   string    $url      Target url of REST service.
     * @return  int                 Returns either a HTTP response code (xxx) or internal exception error code (509x)
     */
    private function http_delete($content, $url) {
        $req_http = new HttpRequest($url, HttpRequest::METH_DELETE);
        if(!empty($content)) {
            $req_http->setContentType("application/json");
            $req_http->setPutData(json_encode($content));
        }
        if(!empty($this->api_token)) {
            //Sign DELETE request
            $req_http->setHeaders($this->create_signed_headers());
        }
        try {
            $req_http->send();
            $this->update_vars($req_http);
            return $req_http->getResponseCode();
        } catch (Exception $e) {
            $this->last_exception_error = $e;
            return 5092;
        }
    }

    /**
     * create_signed_headers() creates the necessary HTTP headers for signing any requests.
     *
     * @return array
     */
    private function create_signed_headers()
    {
        return array(
            "X-Token" => $this->api_token,
            "X-Signature" => hash("sha256",$this->api_secret.$this->api_token)
        );
    }

    /**
     * error() returns the last error in an array
     *
     * $return_message[]
     *      [$CLIENT_CODE]      int     Last internal error code (5xxx)
     *      [$HTTP_CODE]        int     Last HTTP response code (xxx)
     *      [$API_CODE]         int     Last API error code (xxxx) according to
     *                                  http://engineering.etilbudsavis.dk/eta-api/pages/help/error-codes.html
     *                                  Null if there were no API errors (eg. internal/exception/server)
     *      [$API_ID]           string  API error ID provided by etilbudsavis.dk.
     *                                  Null if there were no API errors (eg. internal/exception/server)
     *      [$API_MESSAGE]      string  Short API error message.
     *                                  Null if there were no API errors (eg. internal/exception/server)
     *      [$API_DETAILS]      string  Detailed API error message.
     *                                  Null if there were no API errors (eg. internal/exception/server)
     * Internal Error Codes:
     *
     *  code 5001    initialize() failed. Session already active.
     *  code 5002    initialize() failed. Malformed $key and/or $secret.
     *  code 5003    initialize() failed. Consult error() for details.
     *  code 5004    initialize() failed. Consult error() for details.
     *
     *  code 5010    signIn() failed. No active session.
     *  code 5011    signIn() failed. Malformed $credentials.
     *  code 5012    signIn() failed. Consult error() for details.
     *
     *  code 5020    signOut() failed. No active session.
     *  code 5021    signOut() failed. Consult error() for details.
     *
     *  code 5030    getCatalogList() failed. No active session.
     *  code 5031    getCatalogList() failed. Consult error() for details.
     *  code 5032    getCatalogList() failed. Consult error() for details.
     *
     *  code 5040    destroy() failed. No active session.
     *  code 5041    destroy() failed. Consult error() for details.
     *  code 5042    destroy() failed. Consult error() for details.
     *
     *  code 5050    getCatalogList() failed. No active session.
     *  code 5051    getCatalogList() failed. Consult error() for details.
     *  code 5052    getCatalogList() failed. Consult error() for details.
     *  code 5054    getCatalogList() failed. Malformed \$options.
     *
     *  code 5090    POST failed. Exception error. Consult error_exception() for details.
     *  code 5091    PUT failed. Exception error. Consult error_exception() for details.
     *  code 5092    DELETE failed. Exception error. Consult error_exception() for details.
     *
     * @return array    Returns an array according to above. Values may be null if no error have been registered.
     */
    public function error()
    {
        if(!empty($this->last_response_error)) {
            //TODO non HTTP 400 error creates other error messages.
            return array(
                $this->keywords->CLIENT_CODE => $this->last_client_code,
                $this->keywords->HTTP_CODE => $this->last_response_code,
                $this->keywords->API_CODE => $this->last_response_error[$this->keywords->API_CODE],
                $this->keywords->API_ID => $this->last_response_error[$this->keywords->API_ID],
                $this->keywords->API_MESSAGE => $this->last_response_error[$this->keywords->API_MESSAGE],
                $this->keywords->API_DETAILS => $this->last_response_error[$this->keywords->API_DETAILS]
            );
        } else {
            return array(
                $this->keywords->CLIENT_CODE => $this->last_client_code,
                $this->keywords->HTTP_CODE => $this->last_response_code
            );
        }
    }

    /**
     * error_exception() returns the last caught exception error.
     *
     * @return mixed    The last exception error.
     */
    public function error_exception() {
        return $this->last_exception_error;
    }


    /**
     * update_vars() handles the update of $api_token, $api_token_expires in case of successful request
     * & updates $last_response_error in case of unsuccessful request.
     *
     * @param HttpRequest   $req_http   The active HttpRequest post send()
     */
    private function update_vars($req_http)
    {
        $response_code = $req_http->getResponseCode();
        $this->last_response_code = $response_code; //
        if ($response_code >= 200 and $response_code < 300 ) {
            $this->api_token = $req_http->getResponseHeader("X-Token");
            $this->api_token_expires = $req_http->getResponseHeader("X-Token-Expires");
            //TODO Unsure if this is wise?
            $this->last_response_error = null;
        } else {
            $this->last_response_error = json_decode($req_http->getResponseBody(), true);
        }
        return;
    }

    /**
     *  is_session_active() determines if there exist an active session.
     *
     * @return mixed    Returns FALSE if no active session or
     *                  Returns HTTP response code 2xx if successful (aka active session)
     *                  Returns Any other HTTP response code in case of errors or internal exception code 509x
     */
    private function is_session_active()
    {
        if(empty($this->api_token)) {
            //No token, so definite no active session.
            return false;
        } else {
            $response_code = $this->http_put(null, $this->keywords->API_HOST.$this->keywords->API_SESSIONS);
            if ($response_code >=200 and $response_code <300) {
                //We have an active session
                return $response_code;
            } elseif ($response_code == 400) {
                //We goofed up -- error.
                $error_array = $this->error();
                if ($error_array[$this->keywords->API_CODE] == 1001) {
                    //Token have expired -- we COULD handle this
                    //      But for now we de-initialize.
                    //TODO Policy: Token expired reset variables and prepare for re-initialize.
                    //TODO Better way could be just to reinitialize here and now.
                    $this->reset_attributes();
                } else {
                    //TODO COULD handle any other error here
                }
                return $response_code;
            } else {
                //Something else went wrong -- return error.
                return $response_code;
            }
        }
    }

    /**
     * reset_attributes() resets all attributes that are used in statements.
     *
     * @return void
     */
    private function reset_attributes()
    {
        $this->api_token = null;
        $this->api_token_expires = null;
        $this->api_key = null;
        $this->api_secret = null;
        $this->user_email = null;
        $this->user_password = null;
    }

    /**
     * clear_errors() clears any leftover error messages at the beginning of every outside method calls.
     */
    private function clear_errors()
    {
        $this->last_client_code = null;
        $this->last_exception_error = null;
        $this->last_response_error = null;
        $this->last_response_code = null;
    }

    /**
     * add_optional adds optional queries to $query
     *
     * @param   array   $query          The current query array
     * @param   string  $array_name     Option name
     * @param   array   $array          Array of options
     * @return  mixed   $query          Returns modified $query if queries added else return the original $query
     */
    private function add_optional($query, $array_name, $array)
    {
        if (!empty($array)) {
            $string = "";
            foreach($array as &$sub_string) {
                $string = $string . $sub_string . ",";
            }
            //Remove trailing comma
            if (substr($string, -1) == ",") {
                $string = substr($string, 0, -1);
            }
            return $query = $query + array( $array_name => $string);
        };
        return $query;
    }


}