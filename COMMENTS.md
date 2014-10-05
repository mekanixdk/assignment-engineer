Introduction
------------
I have had a blast these couple of days doing this assignment. It has been fun getting reacquainted to PHP and knocking 
10+ years of rust off the bones.

And I have learned at lot of new tricks these past days. And wrapping my head around PHPs slightly different variable 
scopes and loose approach to data types, which also makes PHPStorm choke at times. :-)

At times I love the "sloppiness" of PHP because it enables you to do weird stuff, but given enough rope... At times I 
miss the strictness of eg. Java. :-)

General comments
----------------

I have implemented `initialize()`, `signIn()`, `signOut()`, `destroy()` and `getCatalogList()`.

I have written PHPDoc to every function in mIClient that I hope conveys the purpose and inner workings of it. And I have 
commented parts of the code where thought it would be beneficial.

I have not performed any unit-testing, as I have never done it before and did not have the time read up on PHPUnit. But 
looking back it may have been wise anyway given the time I have spend debugging the last couple of days. :-)

I suspect Unit-testing and code coverage are closely knit. Code coverage I have not done.

Throughout the code I have written small TODOs with issues I would like to address - but the code works as is. 

I "may" have gone over board with error-code/messages. This is in a great part down to me not knowing eg. PHPUnit, so I use 
the error-code as means to pinpoint bugs. I would greatly reduce them and possible handle all error logic in a separate 
class. 

On the other hand there are several errors that could be handled by the mIClient gracefully without the user ever knowing.
Eg. by caught exceptions you could do a retry -- it may just have been a fluke.

Assumptions
-----------
My approach have been to take the interface as-it-is and work with that.

Eg. when SignOut() have no parameters I take it that only one user can be attached to a session at a time. 

Files
-----

### mIClient.php

My implementation of the IClient.php interface.

### keywords.php

To avoid hardcoded strings into the code I use constants. 

I still have some lingering hardcoded strings (error codes) I would like to get rid of. 

### privatestuff.php

Not supplied but contains private information not for publication. It looks like this:

```php
<?php
/**
 * Created by PhpStorm.
 * User: mekanix
 * Date: 01/10/14
 * Time: 13:55
 */

$api_key="******************";
$api_secret="*******************";
$user_name="mail@domain.xx";
$user_password="*****";
?>
```
###test_suite.php

This is for testing the mIClient. 

In the absence of unit-testing I have tried to come up with various stress test to test for robustness of the code. 
Trying to reach all corners of my code.


Postman
-------
In the API documentation you encourage the use of Postman. I can easily get a token but making any further requests
requires that I sign every request. I thought this wasn't necessary when using a browser? Some session cookies getting
lost?

Encountered errors
------------------

### HTTP Error 500 : Internal Server Error.

I have managed to trigger a Internal Server Error a couple of times. One is this.

URL:https://api.etilbudsavis.dk/v2/catalogs?r_lat=55.55&r_lng=12.12&r_radius=10000&catalog_ids=a4hrb,az2ra,ik6s4

CLIENT CODE:       5032
CLIENT MESSAGE:    getCatalogList() failed. Consult error() for details.
HTTP RESPONSES:    500
API CODE:          2000
API ID:            00i0vcm5424ddh699ie32xfwl4vyyx0g
API MESSAGE:
API DETAILS:       internal <NotFoundException>
Exception():

My guess it may chokes on non-existing ids?

### Wrong $secret

It is possible to create a session - but destroying it cannot be done with a faulty $secret. At least not on the server side.

