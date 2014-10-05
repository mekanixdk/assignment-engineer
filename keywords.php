<?php
/**
 * Created by PhpStorm.
 * User: mekanix
 * Date: 04/10/14
 * Time: 17:35
 */

class keywords {

    public $API_HOST = "https://api.etilbudsavis.dk";
    public $API_SESSIONS = "/v2/sessions";
    public $API_CATALOGS = "/v2/catalogs";

    public $API_ID = "id";
    public $API_MESSAGE = "message";
    public $API_DETAILS = "details";
    public $API_CODE = "code";
    public $HTTP_CODE = "http_code";
    public $CLIENT_CODE = "client_code";

    public $STRING_API_KEY = "api_key";
    public $STRING_USER_EMAIL = "email";
    public $STRING_USER_PASSWORD = "password";

    public $LATITUDE = "r_lat";
    public $LONGITUDE = "r_lng";
    public $RADIUS = "r_radius";
    public $CATALOG_IDS = "catalog_ids";
    public $DEALER_IDS = "dealer_ids";
    public $STORE_IDS = "store_ids";
    public $ORDER_BY = "order_by";
    public $FILTER_BY = "filter_by"; //TODO not sure if this is the correct one?
                                    //TODO FILTER BY KEYWORDS
    public $POPULARITY = "popularity";
    public $DEALER = "dealer";
    public $CREATED = "created";
    public $EXPIRATION_DATE = "expiration_date";
    public $PUBLICATION_DATE = "publication_date";
    public $DISTANCE = "distance";

    function __construct() {
        $this->CLIENT_MESSAGE = array(
            5001 => "initialize() failed. Session already active.",
            5002 => "initialize() failed. Malformed \$key and/or \$secret.",
            5003 => "initialize() failed. Consult error() for details.",
            5004 => "initialize() failed. Consult error() for details.",
            5010 => "signIn() failed. No active session.",
            5011 => "signIn() failed. Malformed \$credentials.",
            5012 => "signIn() failed. Consult error() for details.",
            5020 => "signOut() failed. No active session.",
            5021 => "signOut() failed. Consult error() for details.",
            5030 => "getCatalogList() failed. No active session.",
            5031 => "getCatalogList() failed. Consult error() for details.",
            5032 => "getCatalogList() failed. Consult error() for details.",
            5040 => "destroy() failed. No active session.",
            5041 => "destroy() failed. Consult error() for details.",
            5042 => "destroy() failed. Consult error() for details.",
            5050 => "getCatalogList() failed. No active session.",
            5051 => "getCatalogList() failed. Consult error() for details.",
            5052 => "getCatalogList() failed. Consult error() for details.",
            5054 => "getCatalogList() failed. Malformed \$options.",
            5090 => "POST failed. Exception error. Consult error_exception() for details.",
            5091 => "PUT failed. Exception error. Consult error_exception() for details.",
            5092 => "DELETE failed. Exception error. Consult error_exception() for details.",
            5093 => "GET failed. Exception error. Consult error_exception() for details.",
        );
    }


} 