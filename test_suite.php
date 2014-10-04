<?php
/**
 * Created by PhpStorm.
 * User: mekanix
 * Date: 01/10/14
 * Time: 10:38
 */

include "mIClient.php";


/**
 * privatestuff.php holds $api_key, $api_secret, $user_name, $user_password
 *
 * NOT to be uploaded to github.
 *
 * For testing only
 */
include "privatestuff.php";
include "keywords.php";

//$iclient = new myIClient();

$iclient = new mIClient();


run_test("signOut() No user signed in, client no initialized", $iclient->signOut());

run_test("signIn() -- Correct usage, but client not initialized", $iclient->signIn(array(
    "email" => $user_name,
    "password" => $user_password)));

run_test("initialize() Bogus api_key", $iclient->initialize("xx", $api_secret));

run_test("initialize() -- Correct usage", $iclient->initialize($api_key, $api_secret));

run_test("signOut() No user signed in", $iclient->signOut());

run_test("signIn() Bogus user", $iclient->signIn(array(
    "email" => "xx",
    "password" => "yy")));

run_test("signIn() Malformed array", $iclient->signIn(array(
    "mail" => "xx",
    "password" => "yy")));

run_test("signIn() -- Correct usage", $iclient->signIn(array(
    "email" => $user_name,
    "password" => $user_password)));

run_test("signOut() -- Correct usage", $iclient->signOut());

run_test("destroy() -- Correct usage", $iclient->destroy());

//Make test with bogus secret
run_test("initialize() Correct api_key, Bogus api_secret", $iclient->initialize($api_key, "xxx"));
run_test("signIn() -- Correct usage, bogus api_secret", $iclient->signIn(array(
    "email" => $user_name,
    "password" => $user_password)));
run_test("signOut() -- Correct usage, bogus api_secret", $iclient->signOut());
run_test("destroy() -- Correct usage, bogus api_secret", $iclient->destroy());


function run_test($title,  $result) {
    //Constants
    $API_ID = "id";
    $API_MESSAGE = "message";
    $API_DETAILS = "details";
    $API_CODE = "code";
    $HTTP_CODE = "http_code";
    $CLIENT_CODE = "client_code";
    global $iclient;
    echo("\n\n*** ".$title." ***\n");
    if(!$result) {
        echo("\nMessage from error()");
        $error = $iclient->error();
        echo ("\nCLIENT CODE:   " . $error[$CLIENT_CODE]);
        echo ("\nHTTP RESPONES: " . $error[$HTTP_CODE]);
        if(!empty($error["code"])) {
            echo ("\nAPI CODE:      " . $error[$API_CODE]);
            echo ("\nAPI ID:        " . $error[$API_ID]);
            echo ("\nAPI MESSAGE:   " . $error[$API_MESSAGE]);
            echo ("\nAPI DETAILS:   " . $error[$API_DETAILS]);
        }
         echo("\nException():" .$iclient->error_exception());
    } else {
        echo("\nSuccess!\n");

    }
}

?>
