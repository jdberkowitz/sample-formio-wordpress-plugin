 <?php
/*
* Plugin Name: Sample Display Tables
* Description: Adds a display of Form.io submissions to the page by a shortcode
* Version: 1.0
* Author: JB Design
* Author URI: https://www.joshuaberkowitz.us
*/


include 'Formio.php';

function display_formio_sample_inquiry_entries(){

    /*
    *Create a new instance from the project URL and the resource
    */
    $formio = new Formio('https://hqmmszjedyurvoc.form.io', array(
        'resource' => 'admin', // Formio resource against which to authenticate
        'login' => 'admin/login' // Formio login endpoint
    ));

    /*
    * Call login() to retreive a jwt-token for authentication
    * Replace {{username}} and {{password}}
    */

    $tables = $formio->login('info@joshuaberkowitz.us', 'Joshua42');
    
    /* 
    * jwt token is now stored in the class and can be used for get() and post() calls
    * the get() method takes only the endpoint (or filtered endpoint) and returns a reponse object containing form details and array of submission objects
    */

    $productInquiryTables = $formio->get('sampleinquiryform/submission');

    //The response body contains a "body" property which holds all the response information
    $formEntries = $productInquiryTables["body"];

    /* 
     * VERY VERY SIMPLE Table implementation for illustration purposes only
     * $tableTop contains the table styles and header/heading, this is static
     */
      
    $tableTop = '<style type="text/css">
    .tg  {border-collapse:collapse;border-spacing:0;}
    .tg td{font-family:Arial, sans-serif;font-size:14px;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:black;}
    .tg th{font-family:Arial, sans-serif;font-size:14px;font-weight:normal;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:black;}
    .tg .tg-0pky{border-color:inherit;text-align:left;vertical-align:top}
    .tg .tg-0lax{text-align:left;vertical-align:top}
    </style>
    <h3>Product Inquiry Submissions</h3>
    <table class="tg">
      <tr>        
        <th class="tg-0pky">Date</th>
        <th class="tg-0lax">Name</th>
        <th class="tg-0lax">Email</th>
        <th class="tg-0lax">Phone</th>
      </tr>';

      echo $tableTop;

      /*
      * Simply loop through each of the objects to display the properties you need in bracket notation
      * Note the resopnse body on the formio docs is the $productInquiryTables["body"] variable
      */

    foreach($formEntries as $entry){

        //Making the date a date type to format in the table
        $date = date_create($entry["created"]);

        echo '<tr>';
        echo '<td class="tg-0pky">' . date_format($date, "m/d/y") . '</td>'; //Output the date at 01/01/19
        echo '<td class="tg-0lax">' . $entry["data"]["fullName"] . '</td>';  // List the full name field entries
        echo '<td class="tg-0lax">' . $entry["data"]["email"] . '</td>';     // List the email address field enteries
        echo '<td class="tg-0lax">' . $entry["data"]["phoneNumber"] . '</td>'; // List the phone number field enteries
        echo '</tr>';


    }


    echo '</table>'; //Close our table
    


}
//Add  a custom shortcode code that calls the function to display the table above
add_shortcode('formio-inquiry', 'display_formio_sample_inquiry_entries');


?>