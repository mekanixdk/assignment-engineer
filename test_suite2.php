<?php
/**
 * Created by PhpStorm.
 * User: mekanix
 * Date: 01/10/14
 * Time: 10:38
 */

include "myIClient.php";

/**
 * privatestuff.php holds $api_key, $api_secret, $user_name, $user_password
 *
 * NOT to be uploaded to github.
 *
 * For testing only
 */
include "privatestuff.php";
include "keywords.php";

$iclient = new myIClient();

/*
 * Bogus calls
 */
$ret_message = $iclient->initialize("xx","yy");
mEcho("Bogus 1", $ret_message);

$ret_message = $iclient->signIn("");
mEcho("Bogus 2", $ret_message);

$ret_message = $iclient->signIn(array(
    "user_name" => "xx",
    "user_password" => "yy"));
mEcho("Bogus 3", $ret_message);

//TODO TEST active client illegal user.

$ret_message = $iclient->signOut();
mEcho("Bogus 4", $ret_message);

$ret_message = $iclient->destroy();
mEcho("Bogus 5", $ret_message);




/*
 * Legitimate calls
 */

$ret_message = $iclient->initialize($api_key,$api_secret);
mEcho("Initialize Client", $ret_message);

$ret_message = $iclient->signIn(array(
    "user_name" => "xxy",
    "user_password" => "yy"));
mEcho("Bogus 6", $ret_message);

$ret_message = $iclient->signIn(array(
    "user_name" => $user_name,
    "user_password" => $user_password));
mEcho("Sign in user", $ret_message);

$ret_message = $iclient->signOut();
mEcho("Sign Out", $ret_message);


$ret_message = $iclient->destroy();
mEcho("Destroy Client", $ret_message);


function mEcho($title, $ret_message) {
    global $SUCCESS,$MESSAGE,$ID,$DETAILS,$CODE,$RESPONSE_CODE;
    echo ("\n\n**** ".$title." ****\n");
    echo ("\nSuccess: ".($ret_message[$SUCCESS] ? "true" : "false"));
    if (!empty($ret_message[$CODE])) {
        echo("\nAPI Code: " . $ret_message[$CODE]);
    }
    if (!empty($ret_message[$RESPONSE_CODE])) {
        echo("\nResponse Code: " . $ret_message[$RESPONSE_CODE]);
    }
    if (!empty($ret_message[$MESSAGE])) {
        echo("\nMessage: " . $ret_message[$MESSAGE]);
    }
    if (!empty($ret_message[$DETAILS])) {
        echo ("\nDetails: ".$ret_message[$DETAILS]);
    }
    if (!empty($ret_message[$ID])) {
        echo("\nID: " . $ret_message[$ID]);
    }
}

?>
