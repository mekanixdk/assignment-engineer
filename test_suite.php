<?php
/**
 * Created by PhpStorm.
 * User: mekanix
 * Date: 01/10/14
 * Time: 10:38
 */

/**
 * API Client
 */
include "mIClient.php";
$iclient = new mIClient();
$keywords = $iclient->keywords;

/**
 * privatestuff.php holds $api_key, $api_secret, $user_name, $user_password
 *
 * NOT to be uploaded to github.
 *
 * For testing only
 */
include "privatestuff.php";


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

run_test("getCatalogTest() -- with order by", $return_array=$iclient->getCatalogList( array(
    $keywords->LATITUDE => 55.55,
    $keywords->LONGITUDE => 12.12,
    $keywords->RADIUS => 10000,
    $keywords->ORDER_BY => array("distance","name")
    )));
echo "\nRETURN JSON: ".json_encode($return_array) ; //return it to a json again for easier readability


run_test("getCatalogTest() -- with catalog_id=", $return_array=$iclient->getCatalogList( array(
    $keywords->LATITUDE => 55.55,
    $keywords->LONGITUDE => 12.12,
    $keywords->RADIUS => 10000,
    $keywords->CATALOG_IDS => array("a4hrb","az2ra","ik6s4")
)));
echo "\nRETURN JSON: ". json_encode($return_array);


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
    //Setting keywords
    $keywords = new keywords();
//    var_dump($keywords->CLIENT_MESSAGE);
    global $iclient;
    echo("\n\n*** ".$title." ***\n");
    if(!$result) {
        echo("\nMessage from error()");
        $error = $iclient->error();
        echo ("\nCLIENT CODE:       " . $error[$keywords->CLIENT_CODE]);
        echo ("\nCLIENT MESSAGE:    ". $keywords->CLIENT_MESSAGE[$error[$keywords->CLIENT_CODE]]);
        echo ("\nHTTP RESPONSES:    " . $error[$keywords->HTTP_CODE]);
        if(!empty($error[$keywords->API_CODE])) {
            echo ("\nAPI CODE:          " . $error[$keywords->API_CODE]);
            echo ("\nAPI ID:            " . $error[$keywords->API_ID]);
            echo ("\nAPI MESSAGE:       " . $error[$keywords->API_MESSAGE]);
            echo ("\nAPI DETAILS:       " . $error[$keywords->API_DETAILS]);
        }
         echo("\nException():" .$iclient->error_exception());
    } else {
        echo("\nSuccess!\n");

    }
}

?>
